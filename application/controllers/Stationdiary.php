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
		$renderVersion = 'public_diary_render_v3';
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

		$data['callsign'] = $cleanCallsign;
		$data['entries'] = array($entry);
		$data['pagination_links'] = '';
		$data['page_title'] = $entry->title . ' - Station Diary - ' . $cleanCallsign;
		$data['rss_url'] = site_url('station-diary/' . rawurlencode($cleanCallsign) . '/rss');
		$data['qso_datetime_format'] = $this->get_public_qso_datetime_format($resolution['user_date_format'] ?? NULL);
		$data['is_single_entry'] = true;
		$data['current_entry_permalink'] = site_url('station-diary/' . rawurlencode($cleanCallsign) . '/entry/' . (int)$entry->id);

		$html = $this->load->view('station_diary/public_index', $data, TRUE);
		$this->cache->save($cacheKey, $html, 86400);
		$this->output->set_output($html);
	}
}
