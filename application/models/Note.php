<?php

class Note extends CI_Model {

	private function is_public_station_diary_enabled() {
		$CI =& get_instance();
		$configValue = $CI->config->item('public_station_diary_enabled');
		if ($configValue === NULL) {
			$configValue = TRUE;
		}

		if (isset($CI->optionslib)) {
			$optionValue = $CI->optionslib->get_option('public_station_diary_enabled');
			if ($optionValue !== NULL && $optionValue !== '') {
				return ($optionValue === '1' || $optionValue === 'true' || $optionValue === 1 || $optionValue === TRUE);
			}
		}

		return (bool)$configValue;
	}

	private function touch_public_diary_cache_version($user_id) {
		$this->load->helper('file');
		$cacheVersionFile = APPPATH . 'cache/station_diary_' . (int)$user_id . '_version.txt';
		@write_file($cacheVersionFile, (string)microtime(TRUE));
	}

	public function get_public_diary_cache_version($user_id) {
		$this->load->helper('file');
		$cacheVersionFile = APPPATH . 'cache/station_diary_' . (int)$user_id . '_version.txt';
		if (!file_exists($cacheVersionFile)) {
			return '1';
		}

		$value = @file_get_contents($cacheVersionFile);
		if ($value === FALSE || trim($value) === '') {
			return '1';
		}

		return trim($value);
	}

	private function is_station_diary_category($category) {
		return strtoupper(trim((string)$category)) === 'STATION DIARY';
	}

	function list_all($api_key = null, $filters = array()) {
        if ($api_key == null) {
			$user_id = $this->session->userdata('user_id');
		} else {
			$CI =& get_instance();
			$CI->load->model('api_model');
			if (strpos($this->api_model->access($api_key), 'r') !== false) {
				$this->api_model->update_last_used($api_key);
				$user_id = $this->api_model->key_userid($api_key);
			}
		}
		
		$this->db->where('user_id', $user_id);

		$category = isset($filters['category']) ? trim($filters['category']) : '';
		$search = isset($filters['search']) ? trim($filters['search']) : '';
		$date_from = isset($filters['date_from']) ? trim($filters['date_from']) : '';
		$date_to = isset($filters['date_to']) ? trim($filters['date_to']) : '';

		if ($category !== '') {
			$this->db->where('cat', xss_clean($category));
		}

		if ($search !== '') {
			$search_clean = xss_clean($search);
			$this->db->group_start();
			$this->db->like('title', $search_clean);
			$this->db->or_like('note', $search_clean);
			$this->db->group_end();
		}

		if ($date_from !== '') {
			$date_from_clean = xss_clean($date_from);
			$this->db->where('DATE(created_at) >=', $date_from_clean);
		}

		if ($date_to !== '') {
			$date_to_clean = xss_clean($date_to);
			$this->db->where('DATE(created_at) <=', $date_to_clean);
		}

		$this->db->order_by('created_at', 'DESC');
		return $this->db->get('notes');
	}

	function list_categories() {
		$user_id = $this->session->userdata('user_id');
		$this->db->distinct();
		$this->db->select('cat');
		$this->db->where('user_id', $user_id);
		$this->db->where('cat IS NOT NULL');
		$this->db->where('cat !=', '');
		$this->db->order_by('cat', 'ASC');
		$query = $this->db->get('notes');
		return array_map(function($row) { return $row->cat; }, $query->result());
	}

	function add() {
 		$chosen_category = trim($this->input->post('new_category')); 
 		if ($chosen_category === '') {
 			$chosen_category = $this->input->post('category');
 		}
 		$chosen_category = $chosen_category === '' ? 'General' : $chosen_category;
		$isStationDiary = $this->is_station_diary_category($chosen_category);

		$is_public = $this->input->post('is_public') ? 1 : 0;
		$include_qso_summary = $this->input->post('include_qso_summary') ? 1 : 0;
		$logbook_id = $this->input->post('logbook_id');

		if (!$this->is_public_station_diary_enabled() || !$isStationDiary) {
			$is_public = 0;
			$include_qso_summary = 0;
		}

 		$data = array(
			'cat' => xss_clean($chosen_category),
			'title' => xss_clean($this->input->post('title')),
			'note' => xss_clean($this->input->post('content')),
			'user_id' => $this->session->userdata('user_id'),
			'is_public' => $is_public,
			'include_qso_summary' => $include_qso_summary,
			'logbook_id' => $logbook_id ? xss_clean($logbook_id) : NULL,
			'qso_date_start' => $this->input->post('qso_date_start') ? xss_clean($this->input->post('qso_date_start')) : NULL,
			'qso_date_end' => $this->input->post('qso_date_end') ? xss_clean($this->input->post('qso_date_end')) : NULL,
			'qso_satellite_only' => $this->input->post('qso_satellite_only') ? 1 : 0,
		);

		$this->db->insert('notes', $data);
		$note_id = $this->db->insert_id();

		if ($isStationDiary && $is_public == 1) {
			$this->touch_public_diary_cache_version($this->session->userdata('user_id'));
		}

		return $note_id;
	}

	function edit() {
		$chosen_category = trim($this->input->post('new_category'));
		if ($chosen_category === '') {
			$chosen_category = $this->input->post('category');
		}
		$chosen_category = $chosen_category === '' ? 'General' : $chosen_category;
		$isStationDiary = $this->is_station_diary_category($chosen_category);

		$note_id = xss_clean($this->input->post('id'));
		$user_id = $this->session->userdata('user_id');

		$existing = $this->db->get_where('notes', array('id' => $note_id, 'user_id' => $user_id))->row();

		$is_public = $this->input->post('is_public') ? 1 : 0;
		$include_qso_summary = $this->input->post('include_qso_summary') ? 1 : 0;
		$logbook_id = $this->input->post('logbook_id');

		if (!$this->is_public_station_diary_enabled() || !$isStationDiary) {
			$is_public = 0;
			$include_qso_summary = 0;
		}

		$data = array(
			'cat' => xss_clean($chosen_category),
			'title' => xss_clean($this->input->post('title')),
			'note' => xss_clean($this->input->post('content')),
			'is_public' => $is_public,
			'include_qso_summary' => $include_qso_summary,
			'logbook_id' => $logbook_id ? xss_clean($logbook_id) : NULL,
			'qso_date_start' => $this->input->post('qso_date_start') ? xss_clean($this->input->post('qso_date_start')) : NULL,
			'qso_date_end' => $this->input->post('qso_date_end') ? xss_clean($this->input->post('qso_date_end')) : NULL,
			'qso_satellite_only' => $this->input->post('qso_satellite_only') ? 1 : 0,
		);

		$created_at = trim($this->input->post('created_at'));
		if ($created_at !== '') {
			$data['created_at'] = xss_clean($created_at);
		}

		$this->db->where('id', $note_id);
		$this->db->where('user_id', $user_id);
		$this->db->update('notes', $data);

		$wasPublicDiary = ($existing && $this->is_station_diary_category($existing->cat) && (int)$existing->is_public === 1);
		$isPublicDiary = ($isStationDiary && (int)$is_public === 1);

		if ($wasPublicDiary || $isPublicDiary) {
			$this->touch_public_diary_cache_version($user_id);
		}

		return $note_id;
	}

	function delete($id) {
		$clean_id = xss_clean($id);
		$user_id = $this->session->userdata('user_id');

		$existing = $this->db->get_where('notes', array('id' => $clean_id, 'user_id' => $user_id))->row();

		$this->delete_diary_images_for_entry($clean_id, $user_id);
		$this->db->delete('notes', array('id' => $clean_id, 'user_id' => $user_id));

		if ($existing && $this->is_station_diary_category($existing->cat) && (int)$existing->is_public === 1) {
			$this->touch_public_diary_cache_version($user_id);
		}
	}

	public function add_diary_images($diary_id, $images = array()) {
		if (empty($images)) {
			return;
		}

		$existingCount = $this->db->where('diary_id', (int)$diary_id)->count_all_results('diary_images');
		$batch = array();
		$sort = (int)$existingCount;

		foreach ($images as $image) {
			if (!isset($image['filename'])) {
				continue;
			}

			$batch[] = array(
				'diary_id' => (int)$diary_id,
				'filename' => xss_clean($image['filename']),
				'caption' => isset($image['caption']) ? xss_clean($image['caption']) : NULL,
				'sort_order' => $sort,
			);
			$sort++;
		}

		if (!empty($batch)) {
			$this->db->insert_batch('diary_images', $batch);
		}
	}

	public function get_diary_images($diary_ids = array()) {
		if (empty($diary_ids)) {
			return array();
		}

		$this->db->where_in('diary_id', $diary_ids);
		$this->db->order_by('sort_order', 'ASC');
		$this->db->order_by('id', 'ASC');
		$query = $this->db->get('diary_images');

		$mapped = array();
		foreach ($query->result() as $row) {
			if (!isset($mapped[$row->diary_id])) {
				$mapped[$row->diary_id] = array();
			}
			$mapped[$row->diary_id][] = $row;
		}

		return $mapped;
	}

	public function delete_diary_image_by_id($image_id, $user_id) {
		// Get image info and verify ownership through the note
		$this->db->select('diary_images.id, diary_images.filename, diary_images.diary_id');
		$this->db->from('diary_images');
		$this->db->join('notes', 'notes.id = diary_images.diary_id');
		$this->db->where('diary_images.id', (int)$image_id);
		$this->db->where('notes.user_id', (int)$user_id);
		$image = $this->db->get()->row();

		if (!$image) {
			return false;
		}

		// Delete physical file
		$filePath = FCPATH . ltrim($image->filename, '/');
		if (file_exists($filePath)) {
			@unlink($filePath);
		}

		// Delete database record
		$this->db->where('id', (int)$image_id);
		$this->db->delete('diary_images');

		// Touch cache if this is a public diary entry
		$this->db->select('notes.is_public, notes.cat, notes.user_id');
		$this->db->from('notes');
		$this->db->where('notes.id', (int)$image->diary_id);
		$note = $this->db->get()->row();

		if ($note && $this->is_station_diary_category($note->cat) && (int)$note->is_public === 1) {
			$this->touch_public_diary_cache_version($note->user_id);
		}

		return true;
	}

	public function delete_diary_images_for_entry($diary_id, $user_id = NULL) {
		$this->db->select('diary_images.id, diary_images.filename');
		$this->db->from('diary_images');
		$this->db->join('notes', 'notes.id = diary_images.diary_id');
		$this->db->where('diary_images.diary_id', (int)$diary_id);
		if ($user_id !== NULL) {
			$this->db->where('notes.user_id', (int)$user_id);
		}
		$images = $this->db->get()->result();

		foreach ($images as $image) {
			$filePath = FCPATH . ltrim($image->filename, '/');
			if (file_exists($filePath)) {
				@unlink($filePath);
			}
		}

		$this->db->where('diary_id', (int)$diary_id);
		$this->db->delete('diary_images');
	}

	public function resolve_public_user_by_callsign($callsign) {
		if (!$this->is_public_station_diary_enabled()) {
			return array('status' => 'disabled');
		}

		$clean_callsign = strtoupper(trim($this->security->xss_clean($callsign)));
		if ($clean_callsign === '') {
			return array('status' => 'not_found');
		}

		$this->db->select('user_id, user_callsign');
		$this->db->from($this->config->item('auth_table'));
		$this->db->where('UPPER(user_callsign)', $clean_callsign);
		$query = $this->db->get();

		if ($query->num_rows() === 0) {
			return array('status' => 'not_found');
		}

		if ($query->num_rows() > 1) {
			return array('status' => 'duplicate');
		}

		$user = $query->row();
		return array(
			'status' => 'ok',
			'user_id' => (int)$user->user_id,
			'callsign' => $user->user_callsign,
		);
	}

	public function count_public_station_diary_entries($user_id) {
		$this->db->from('notes');
		$this->db->where('user_id', (int)$user_id);
		$this->db->where('cat', 'Station Diary');
		$this->db->where('is_public', 1);
		return (int)$this->db->count_all_results();
	}

	public function get_public_station_diary_entries($user_id, $limit = 10, $offset = 0) {
		$this->db->from('notes');
		$this->db->where('user_id', (int)$user_id);
		$this->db->where('cat', 'Station Diary');
		$this->db->where('is_public', 1);
		$this->db->order_by('created_at', 'DESC');
		$this->db->order_by('id', 'DESC');
		$this->db->limit((int)$limit, (int)$offset);

		$query = $this->db->get();
		$entries = $query->result();

		$ids = array();
		foreach ($entries as $entry) {
			$ids[] = (int)$entry->id;
		}

		$imagesMap = $this->get_diary_images($ids);
		foreach ($entries as $entry) {
			$entry->images = isset($imagesMap[$entry->id]) ? $imagesMap[$entry->id] : array();
			$entry->qso_summary = null;
			$entry->qso_list = array();
			if ((int)$entry->include_qso_summary === 1) {
				$entryDate = date('Y-m-d', strtotime($entry->created_at));
				
				// Determine date range for filtering
				$dateStart = !empty($entry->qso_date_start) ? $entry->qso_date_start : $entryDate;
				$dateEnd = !empty($entry->qso_date_end) ? $entry->qso_date_end : $entryDate;
				$satOnly = (int)$entry->qso_satellite_only === 1;
				
				// Use date range filtering if dates are set, otherwise fall back to single-day filtering
				if (!empty($entry->qso_date_start) || !empty($entry->qso_date_end)) {
					$entry->qso_summary = $this->get_qso_summary_for_date_range($user_id, $dateStart, $dateEnd, $entry->logbook_id, $satOnly);
					$entry->qso_list = $this->get_qso_list_for_date_range($user_id, $dateStart, $dateEnd, $entry->logbook_id, $satOnly);
				} else {
					$entry->qso_summary = $this->get_qso_summary_for_date($user_id, $entryDate, $entry->logbook_id);
					$entry->qso_list = $this->get_qso_list_for_date($user_id, $entryDate, $entry->logbook_id);
				}
			}
		}

		return $entries;
	}

	private function get_user_station_ids($user_id) {
		$this->db->select('station_id');
		$this->db->from('station_profile');
		$this->db->where('user_id', (int)$user_id);
		$query = $this->db->get();

		$station_ids = array();
		foreach ($query->result() as $row) {
			$station_ids[] = (int)$row->station_id;
		}

		return $station_ids;
	}

	private function get_station_ids_for_summary($user_id, $logbook_id = NULL) {
		if ($logbook_id === NULL || $logbook_id === '' || $logbook_id === 0 || $logbook_id === '0') {
			// No logbook specified, use all user stations
			return $this->get_user_station_ids($user_id);
		}

		// Get stations associated with the specified logbook
		$this->db->select('station_location_id');
		$this->db->from('station_logbooks_relationship');
		$this->db->where('station_logbook_id', (int)$logbook_id);
		$query = $this->db->get();

		$station_ids = array();
		foreach ($query->result() as $row) {
			$station_ids[] = (int)$row->station_location_id;
		}

		// Verify at least one station belongs to user for security
		if (!empty($station_ids)) {
			$this->db->select('station_id');
			$this->db->from('station_profile');
			$this->db->where('user_id', (int)$user_id);
			$this->db->where_in('station_id', $station_ids);
			$verify_query = $this->db->get();

			$verified_ids = array();
			foreach ($verify_query->result() as $row) {
				$verified_ids[] = (int)$row->station_id;
			}
			return $verified_ids;
		}

		return $station_ids;
	}

	public function get_qso_list_for_date($user_id, $date, $logbook_id = NULL) {
		$station_ids = $this->get_station_ids_for_summary($user_id, $logbook_id);
		if (empty($station_ids)) {
			return array();
		}

		$table = $this->config->item('table_name');

		$this->db->select('COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_SUBMODE, COL_COUNTRY, COL_GRIDSQUARE, COL_RST_SENT, COL_RST_RCVD, COL_FREQ, COL_DXCC, COL_DISTANCE');
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON)', $date);
		$this->db->order_by('COL_TIME_ON', 'ASC');
		$this->db->limit(100); // Reasonable limit for public display

		$query = $this->db->get();
		return $query->result();
	}

	public function get_qso_summary_for_date($user_id, $date, $logbook_id = NULL) {
		$station_ids = $this->get_station_ids_for_summary($user_id, $logbook_id);
		if (empty($station_ids)) {
			return null;
		}

		$table = $this->config->item('table_name');

		$this->db->select('COUNT(*) AS total_qsos, COUNT(DISTINCT COL_DXCC) AS dxcc_worked');
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON)', $date);
		$overview = $this->db->get()->row();

		$this->db->distinct();
	$this->db->select('LOWER(COL_BAND) AS band, COL_BAND+0 AS band_num', FALSE);
	$this->db->from($table);
	$this->db->where_in('station_id', $station_ids);
	$this->db->where('DATE(COL_TIME_ON)', $date);
	$this->db->where('COL_BAND IS NOT NULL', null, FALSE);
	$this->db->where('COL_BAND !=', '');
	$this->db->order_by('band_num', 'ASC');
		$bandsResult = $this->db->get()->result();

		$this->db->distinct();
		$this->db->select('(CASE WHEN COL_SUBMODE IS NOT NULL AND COL_SUBMODE != "" THEN UPPER(COL_SUBMODE) ELSE UPPER(COL_MODE) END) AS mode_label', FALSE);
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON)', $date);
		$this->db->where('COL_MODE IS NOT NULL', null, FALSE);
		$this->db->where('COL_MODE !=', '');
		$this->db->order_by('mode_label', 'ASC');
		$modesResult = $this->db->get()->result();

		$this->db->select('COL_CALL, COL_COUNTRY, COL_DISTANCE');
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON)', $date);
		$this->db->where('COL_DISTANCE IS NOT NULL', null, FALSE);
		$this->db->where('COL_DISTANCE >', 0);
		$this->db->order_by('COL_DISTANCE+0', 'DESC', FALSE);
		$this->db->limit(1);
		$highlight = $this->db->get()->row();

		$bands = array();
		foreach ($bandsResult as $band) {
			$bands[] = $band->band;
		}

		$modes = array();
		foreach ($modesResult as $mode) {
			$modes[] = $mode->mode_label;
		}

		// Don't show summary if there are no QSOs
		$total_qsos = (int)($overview->total_qsos ?? 0);
		if ($total_qsos === 0) {
			return null;
		}

		return array(
			'total_qsos' => $total_qsos,
			'dxcc_worked' => (int)($overview->dxcc_worked ?? 0),
			'bands' => $bands,
			'modes' => $modes,
			'highlight_dx' => $highlight,
		);
	}

	function view($id) {
		// Get Note
		$this->db->where('id', xss_clean($id));
		$this->db->where('user_id', $this->session->userdata('user_id'));
		return $this->db->get('notes');
	}

	function ClaimAllNotes($id = NULL) {
		// if $id is empty then use session user_id
		if (empty($id)) {
			// Get the first USER ID from user table in the database
			$id = $this->db->get("users")->row()->user_id;
		}

		$data = array(
				'user_id' => $id,
		);
			
		$this->db->update('notes', $data);
	}

	function CountAllNotes() {
		// count all notes
		$this->db->where('user_id =', NULL);
		$query = $this->db->get('notes');
		return $query->num_rows();
	}

	function replace_category($from, $to) {
		$user_id = $this->session->userdata('user_id');
		$from_clean = xss_clean(trim($from));
		$to_clean = xss_clean(trim($to));
		if ($from_clean === '') {
			return 0;
		}
		if ($to_clean === '') {
			$to_clean = 'General';
		}
		$this->db->where('user_id', $user_id);
		$this->db->where('cat', $from_clean);
		$this->db->update('notes', array('cat' => $to_clean));
		return $this->db->affected_rows();
	}

	public function get_qso_list_for_date_range($user_id, $start_date, $end_date, $logbook_id = NULL, $sat_only = FALSE) {
		$station_ids = $this->get_station_ids_for_summary($user_id, $logbook_id);
		if (empty($station_ids)) {
			return array();
		}

		$table = $this->config->item('table_name');

		$this->db->select('COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_SUBMODE, COL_COUNTRY, COL_GRIDSQUARE, COL_RST_SENT, COL_RST_RCVD, COL_FREQ, COL_DXCC, COL_DISTANCE, COL_PROP_MODE');
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON) >=', $start_date);
		$this->db->where('DATE(COL_TIME_ON) <=', $end_date);
		
		if ($sat_only) {
			$this->db->where('COL_PROP_MODE', 'SAT');
		}

		$this->db->order_by('COL_TIME_ON', 'ASC');
		$this->db->limit(100);

		$query = $this->db->get();
		return $query->result();
	}

	public function get_qso_summary_for_date_range($user_id, $start_date, $end_date, $logbook_id = NULL, $sat_only = FALSE) {
		$station_ids = $this->get_station_ids_for_summary($user_id, $logbook_id);
		if (empty($station_ids)) {
			return null;
		}

		$table = $this->config->item('table_name');

		$this->db->select('COUNT(*) AS total_qsos, COUNT(DISTINCT COL_DXCC) AS dxcc_worked');
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON) >=', $start_date);
		$this->db->where('DATE(COL_TIME_ON) <=', $end_date);
		
		if ($sat_only) {
			$this->db->where('COL_PROP_MODE', 'SAT');
		}

		$overview = $this->db->get()->row();

		// Get bands with date range
		$this->db->distinct();
		$this->db->select('LOWER(COL_BAND) AS band, COL_BAND+0 AS band_num', FALSE);
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON) >=', $start_date);
		$this->db->where('DATE(COL_TIME_ON) <=', $end_date);
		$this->db->where('COL_BAND IS NOT NULL', null, FALSE);
		$this->db->where('COL_BAND !=', '');
		
		if ($sat_only) {
			$this->db->where('COL_PROP_MODE', 'SAT');
		}
		
		$this->db->order_by('band_num', 'ASC');
		$bandsResult = $this->db->get()->result();

		// Get modes with date range
		$this->db->distinct();
		$this->db->select('(CASE WHEN COL_SUBMODE IS NOT NULL AND COL_SUBMODE != "" THEN UPPER(COL_SUBMODE) ELSE UPPER(COL_MODE) END) AS mode_label', FALSE);
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON) >=', $start_date);
		$this->db->where('DATE(COL_TIME_ON) <=', $end_date);
		$this->db->where('COL_MODE IS NOT NULL', null, FALSE);
		$this->db->where('COL_MODE !=', '');
		
		if ($sat_only) {
			$this->db->where('COL_PROP_MODE', 'SAT');
		}
		
		$this->db->order_by('mode_label', 'ASC');
		$modesResult = $this->db->get()->result();

		// Get highlight DX
		$this->db->select('COL_CALL, COL_COUNTRY, COL_DISTANCE');
		$this->db->from($table);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where('DATE(COL_TIME_ON) >=', $start_date);
		$this->db->where('DATE(COL_TIME_ON) <=', $end_date);
		
		if ($sat_only) {
			$this->db->where('COL_PROP_MODE', 'SAT');
		}

		$this->db->where('COL_DISTANCE IS NOT NULL', null, FALSE);
		$this->db->where('COL_DISTANCE >', 0);
		$this->db->order_by('COL_DISTANCE+0', 'DESC', FALSE);
		$this->db->limit(1);
		$highlight = $this->db->get()->row();

		$bands = array();
		foreach ($bandsResult as $band) {
			$bands[] = $band->band;
		}

		$modes = array();
		foreach ($modesResult as $mode) {
			$modes[] = $mode->mode_label;
		}

		return array(
			'total_qsos' => (int)($overview->total_qsos ?? 0),
			'dxcc_worked' => (int)($overview->dxcc_worked ?? 0),
			'bands' => $bands,
			'modes' => $modes,
			'highlight_dx' => $highlight
		);
	}

/**
 * Process image shortcodes in diary note content
 * Supports: [image:ID], [image:caption], [image:ID:modifier], [image:ID:modifier:modifier]
 * Modifiers: left, right, center, small, medium, large
 * 
 * @param string $content Note content with potential shortcodes
 * @param array $images Array of image objects for this entry
 * @return array ['content' => processed HTML, 'used_image_ids' => array of IDs used inline]
 */
public function process_image_shortcodes($content, $images = array()) {
	if (empty($images) || empty($content)) {
		return array('content' => $content, 'used_image_ids' => array());
	}
	
	$usedImageIds = array();
	
	// Match [image:identifier] or [image:identifier:modifier[:modifier...]]
	$pattern = '/\[image:([^\]:]+)(?::([^\]]+))?\]/i';
	
	$content = preg_replace_callback($pattern, function($matches) use ($images, &$usedImageIds) {
		$identifier = trim($matches[1]);
		$modifierString = isset($matches[2]) ? trim($matches[2]) : '';
		
		// Find the image by ID or caption
		$image = null;
		if (is_numeric($identifier)) {
			// Lookup by ID
			foreach ($images as $img) {
				if ((int)$img->id === (int)$identifier) {
					$image = $img;
					break;
				}
			}
		} else {
			// Lookup by caption (case-insensitive)
			foreach ($images as $img) {
				if (!empty($img->caption) && strcasecmp($img->caption, $identifier) === 0) {
					$image = $img;
					break;
				}
			}
		}
		
		// If image not found, return empty string (silent fail)
		if (!$image) {
			return '';
		}
		
		// Track this image as used
		$usedImageIds[] = (int)$image->id;
		
		// Determine CSS classes based on modifiers
		$wrapperClass = 'diary-inline-image mb-3';
		$imgClass = 'img-fluid rounded';
		$style = '';
		
		if (!empty($modifierString)) {
			$modifiers = array_filter(array_map('trim', explode(':', strtolower($modifierString))));
			
			foreach ($modifiers as $mod) {
				if ($mod === 'left') {
					$wrapperClass .= ' float-start me-3';
					if (empty($style)) {
						$style = 'max-width: 400px;';
					}
				} elseif ($mod === 'right') {
					$wrapperClass .= ' float-end ms-3';
					if (empty($style)) {
						$style = 'max-width: 400px;';
					}
				} elseif ($mod === 'center') {
					$wrapperClass .= ' text-center mx-auto';
				}
			}

			foreach ($modifiers as $mod) {
				if ($mod === 'small') {
					$style = 'max-width: 300px;';
				} elseif ($mod === 'medium') {
					$style = 'max-width: 500px;';
				} elseif ($mod === 'large') {
					$style = 'max-width: 800px;';
				}
			}
		}
		
		// Build the HTML
		$html = '<div class="' . $wrapperClass . '"' . (!empty($style) ? ' style="' . $style . '"' : '') . '>';
		$html .= '<img src="' . base_url() . ltrim($image->filename, '/') . '" alt="' . htmlspecialchars($image->caption ?? 'Diary image', ENT_QUOTES) . '" class="' . $imgClass . '">';
		if (!empty($image->caption)) {
			$html .= '<div class="small text-muted mt-1">' . htmlspecialchars($image->caption, ENT_QUOTES) . '</div>';
		}
		$html .= '</div>';
		
		return $html;
	}, $content);
	
	return array(
		'content' => $content,
		'used_image_ids' => array_unique($usedImageIds)
	);
}

}

