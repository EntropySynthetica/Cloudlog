<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Stationdiary extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('note');
		$this->load->library('pagination');
		$this->load->driver('cache', array('adapter' => 'file'));
	}

	private function get_public_qso_datetime_format($userDateFormat = NULL)
	{
		$dateFormat = !empty($userDateFormat) ? trim((string)$userDateFormat) : trim((string)$this->config->item('qso_date_format'));
		if ($dateFormat === '') {
			$dateFormat = 'Y-m-d';
		}

		if (preg_match('/[GgHh]/', $dateFormat) || strpos($dateFormat, 'i') !== FALSE) {
			return $dateFormat;
		}

		return $dateFormat . ' H:i';
	}

	private function get_or_create_public_visitor_token()
	{
		$cookieName = 'cloudlog_diary_visitor';
		$token = $this->input->cookie($cookieName, TRUE);

		if (!empty($token) && preg_match('/^[a-f0-9]{32,64}$/i', $token)) {
			return $token;
		}

		try {
			$token = bin2hex(random_bytes(16));
		} catch (Exception $e) {
			$token = md5(uniqid((string)mt_rand(), TRUE));
		}

		setcookie($cookieName, $token, time() + (365 * 24 * 60 * 60), '/', '', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), TRUE);
		return $token;
	}

	private function build_public_visitor_hash()
	{
		$token = $this->get_or_create_public_visitor_token();
		$ipAddress = (string)$this->input->ip_address();
		$userAgent = substr((string)$this->input->user_agent(), 0, 255);
		$secret = (string)$this->config->item('encryption_key');

		return hash('sha256', $token . '|' . $ipAddress . '|' . $userAgent . '|' . $secret);
	}

	public function index($callsign = NULL, $offset = 0)
	{
		if ($this->security->xss_clean($callsign, TRUE) === FALSE) {
			show_404();
			return;
		}

		$resolution = $this->note->resolve_public_user_by_callsign($callsign);
		if (!isset($resolution['status']) || $resolution['status'] !== 'ok') {
			show_404();
			return;
		}

		$user_id = (int)$resolution['user_id'];
		$cleanCallsign = strtoupper($resolution['callsign']);
		$pageOffset = is_numeric($offset) ? (int)$offset : 0;
		$cacheVersion = $this->note->get_public_diary_cache_version($user_id);
		$renderVersion = 'public_diary_render_v2';
		$cacheKey = 'public_station_diary_' . md5($cleanCallsign . '_' . $pageOffset . '_' . $cacheVersion . '_' . $renderVersion);

		$cachedHtml = $this->cache->get($cacheKey);
		if ($cachedHtml !== FALSE && !empty($cachedHtml)) {
			$this->output->set_output($cachedHtml);
			return;
		}

		$perPage = 10;
		$totalRows = $this->note->count_public_station_diary_entries($user_id);

		$config['base_url'] = site_url('station-diary/' . rawurlencode($cleanCallsign));
		$config['total_rows'] = $totalRows;
		$config['per_page'] = $perPage;
		$config['uri_segment'] = 3;
		$config['num_links'] = 5;
		$config['full_tag_open'] = '<ul class="pagination pagination-sm">';
		$config['full_tag_close'] = '</ul>';
		$config['attributes'] = array('class' => 'page-link');
		$config['first_link'] = FALSE;
		$config['last_link'] = FALSE;
		$config['first_tag_open'] = '<li class="page-item">';
		$config['first_tag_close'] = '</li>';
		$config['prev_link'] = '&laquo';
		$config['prev_tag_open'] = '<li class="page-item">';
		$config['prev_tag_close'] = '</li>';
		$config['next_link'] = '&raquo';
		$config['next_tag_open'] = '<li class="page-item">';
		$config['next_tag_close'] = '</li>';
		$config['last_tag_open'] = '<li class="page-item">';
		$config['last_tag_close'] = '</li>';
		$config['cur_tag_open'] = '<li class="page-item active"><a href="#" class="page-link">';
		$config['cur_tag_close'] = '<span class="visually-hidden">(current)</span></a></li>';
		$config['num_tag_open'] = '<li class="page-item">';
		$config['num_tag_close'] = '</li>';

		$this->pagination->initialize($config);

		$data['callsign'] = $cleanCallsign;
		$data['entries'] = $this->note->get_public_station_diary_entries($user_id, $perPage, $pageOffset);
		$data['pagination_links'] = $this->pagination->create_links();
		$data['page_title'] = 'Station Diary - ' . $cleanCallsign;
		$data['rss_url'] = site_url('station-diary/' . rawurlencode($cleanCallsign) . '/rss');
		$data['qso_datetime_format'] = $this->get_public_qso_datetime_format($resolution['user_date_format'] ?? NULL);
		$data['is_single_entry'] = false;
		$data['current_entry_permalink'] = '';

		$html = $this->load->view('station_diary/public_index', $data, TRUE);
		$this->cache->save($cacheKey, $html, 86400);
		$this->output->set_output($html);
	}

	public function rss($callsign = NULL)
	{
		if ($this->security->xss_clean($callsign, TRUE) === FALSE) {
			show_404();
			return;
		}

		$resolution = $this->note->resolve_public_user_by_callsign($callsign);
		if (!isset($resolution['status']) || $resolution['status'] !== 'ok') {
			show_404();
			return;
		}

		$user_id = (int)$resolution['user_id'];
		$cleanCallsign = strtoupper($resolution['callsign']);
		$entries = $this->note->get_public_station_diary_entries($user_id, 25, 0);

		$this->output->set_content_type('application/rss+xml; charset=UTF-8');
		$this->load->view('station_diary/rss', array(
			'callsign' => $cleanCallsign,
			'entries' => $entries,		'feed_url' => site_url('station-diary/' . rawurlencode($cleanCallsign) . '/rss'),		));
	}

	public function entry($callsign = NULL, $entry_id = 0)
	{
		if ($this->security->xss_clean($callsign, TRUE) === FALSE) {
			show_404();
			return;
		}

		$resolution = $this->note->resolve_public_user_by_callsign($callsign);
		if (!isset($resolution['status']) || $resolution['status'] !== 'ok') {
			show_404();
			return;
		}

		$user_id = (int)$resolution['user_id'];
		$cleanCallsign = strtoupper($resolution['callsign']);
		$entryId = (int)$entry_id;
		if ($entryId <= 0) {
			show_404();
			return;
		}

		$cacheVersion = $this->note->get_public_diary_cache_version($user_id);
		$renderVersion = 'public_diary_render_v4';
		$cacheKey = 'public_station_diary_entry_' . md5($cleanCallsign . '_' . $entryId . '_' . $cacheVersion . '_' . $renderVersion);

		$cachedHtml = $this->cache->get($cacheKey);
		if ($cachedHtml !== FALSE && !empty($cachedHtml)) {
			$this->output->set_output($cachedHtml);
			return;
		}

		$entry = $this->note->get_public_station_diary_entry($user_id, $entryId);
		if (!$entry) {
			show_404();
			return;
		}

		$entryReactionTotals = $this->note->get_station_diary_reaction_totals($entry->id);

		$data['callsign'] = $cleanCallsign;
		$data['entries'] = array($entry);
		$data['pagination_links'] = '';
		$data['page_title'] = $entry->title . ' - Station Diary - ' . $cleanCallsign;
		$data['rss_url'] = site_url('station-diary/' . rawurlencode($cleanCallsign) . '/rss');
		$data['qso_datetime_format'] = $this->get_public_qso_datetime_format($resolution['user_date_format'] ?? NULL);
		$data['is_single_entry'] = true;
		$data['current_entry_permalink'] = site_url('station-diary/' . rawurlencode($cleanCallsign) . '/entry/' . (int)$entry->id);
		$data['entry_reaction_totals'] = $entryReactionTotals;
		$data['visitor_reaction'] = null;

		$html = $this->load->view('station_diary/public_index', $data, TRUE);
		$this->cache->save($cacheKey, $html, 86400);
		$this->output->set_output($html);
	}

	public function react($callsign = NULL, $entry_id = 0)
	{
		header('Content-Type: application/json');

		if (strtolower($this->input->method()) !== 'post') {
			echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
			return;
		}

		if ($this->security->xss_clean($callsign, TRUE) === FALSE) {
			echo json_encode(array('success' => false, 'message' => 'Invalid callsign'));
			return;
		}

		$resolution = $this->note->resolve_public_user_by_callsign($callsign);
		if (!isset($resolution['status']) || $resolution['status'] !== 'ok') {
			echo json_encode(array('success' => false, 'message' => 'Not found'));
			return;
		}

		$user_id = (int)$resolution['user_id'];
		$entryId = (int)$entry_id;
		if ($entryId <= 0) {
			echo json_encode(array('success' => false, 'message' => 'Invalid entry'));
			return;
		}

		$entry = $this->note->get_public_station_diary_entry($user_id, $entryId);
		if (!$entry) {
			echo json_encode(array('success' => false, 'message' => 'Entry not found'));
			return;
		}

		$reaction = strtolower(trim((string)$this->input->post('reaction', TRUE)));
		if (!in_array($reaction, array('like', 'love', 'fire'), TRUE)) {
			echo json_encode(array('success' => false, 'message' => 'Invalid reaction'));
			return;
		}

		$visitorHash = $this->build_public_visitor_hash();
		$saved = $this->note->save_station_diary_reaction($entryId, $reaction, $visitorHash);
		if (!$saved) {
			echo json_encode(array('success' => false, 'message' => 'Unable to save reaction'));
			return;
		}

		$this->note->invalidate_public_diary_cache_for_note($entryId, $user_id);

		$totals = $this->note->get_station_diary_reaction_totals($entryId);
		$visitorReaction = $this->note->get_station_diary_visitor_reaction($entryId, $visitorHash);

		echo json_encode(array(
			'success' => true,
			'totals' => $totals,
			'visitor_reaction' => $visitorReaction,
		));
	}
}
