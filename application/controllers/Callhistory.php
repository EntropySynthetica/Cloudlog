<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Callhistory extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->model('user_model');
        if (!$this->user_model->authorize(2)) {
            $this->session->set_flashdata('notice', 'You\'re not allowed to do that!');
            redirect('dashboard');
        }

        $this->load->model('callhistory_model');
    }

    public function index()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $data['page_title'] = 'Call History';
        $data['files'] = $this->callhistory_model->get_all_for_user($user_id);

        $this->load->view('interface_assets/header', $data);
        $this->load->view('callhistory/index', $data);
        $this->load->view('interface_assets/footer');
    }

    public function upload()
    {
        if (strtolower($this->input->method()) !== 'post') {
            redirect('callhistory');
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        $upload_dir = FCPATH . 'uploads/callhistory/' . $user_id . '/';

        if (!is_dir($upload_dir)) {
            if (!@mkdir($upload_dir, 0755, TRUE)) {
                $this->session->set_flashdata('notice', 'Unable to create upload directory.');
                redirect('callhistory');
                return;
            }
        }

        if (!isset($_FILES['history_file']) || empty($_FILES['history_file']['name'])) {
            $this->session->set_flashdata('notice', 'Please select a call history file to upload.');
            redirect('callhistory');
            return;
        }

        $config = array(
            'upload_path' => $upload_dir,
            'allowed_types' => 'txt|csv|TXT|CSV',
            'max_size' => 5120,
            'encrypt_name' => TRUE,
            'detect_mime' => TRUE,
            'mod_mime_fix' => TRUE,
        );

        $this->load->library('upload');
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('history_file')) {
            $this->session->set_flashdata('notice', trim(strip_tags($this->upload->display_errors('', ''))));
            redirect('callhistory');
            return;
        }

        $upload_data = $this->upload->data();
        $original_filename = $upload_data['client_name'] !== '' ? $upload_data['client_name'] : $upload_data['orig_name'];
        $organization_label = strtoupper(trim((string)$this->input->post('organization_label', TRUE)));

        $data = array(
            'user_id' => $user_id,
            'file_label' => trim((string)$this->input->post('file_label', TRUE)),
            'organization_label' => $organization_label,
            'stored_filename' => $upload_data['file_name'],
            'original_filename' => $original_filename,
            'mime_type' => isset($upload_data['file_type']) ? $upload_data['file_type'] : null,
            'file_size' => isset($upload_data['file_size']) ? (int)round($upload_data['file_size'] * 1024) : null,
            'checksum' => @hash_file('sha256', $upload_data['full_path']),
            'is_active' => 1,
            'priority' => (int)$this->input->post('priority', TRUE),
            'parser_status' => 'ready',
        );

        $this->callhistory_model->create_file($data);

        $this->session->set_flashdata('notice', 'Call history file uploaded successfully.');
        redirect('callhistory');
    }

    public function set_active()
    {
        if (strtolower($this->input->method()) !== 'post') {
            redirect('callhistory');
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        $id = (int)$this->input->post('id', TRUE);
        $is_active = $this->input->post('is_active', TRUE) ? 1 : 0;

        $file = $this->callhistory_model->get_for_user_by_id($user_id, $id);
        if (!$file) {
            $this->session->set_flashdata('notice', 'Call history file not found.');
            redirect('callhistory');
            return;
        }

        $this->callhistory_model->update_active($user_id, $id, $is_active);
        redirect('callhistory');
    }

    public function set_priority()
    {
        if (strtolower($this->input->method()) !== 'post') {
            redirect('callhistory');
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        $id = (int)$this->input->post('id', TRUE);
        $priority = (int)$this->input->post('priority', TRUE);

        $file = $this->callhistory_model->get_for_user_by_id($user_id, $id);
        if (!$file) {
            $this->session->set_flashdata('notice', 'Call history file not found.');
            redirect('callhistory');
            return;
        }

        $this->callhistory_model->update_priority($user_id, $id, $priority);
        redirect('callhistory');
    }

    public function delete()
    {
        if (strtolower($this->input->method()) !== 'post') {
            redirect('callhistory');
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        $id = (int)$this->input->post('id', TRUE);

        $file = $this->callhistory_model->get_for_user_by_id($user_id, $id);
        if (!$file) {
            $this->session->set_flashdata('notice', 'Call history file not found.');
            redirect('callhistory');
            return;
        }

        $path = FCPATH . 'uploads/callhistory/' . $user_id . '/' . $file->stored_filename;
        if (is_file($path)) {
            @unlink($path);
        }

        $this->callhistory_model->delete_file($user_id, $id);

        $this->session->set_flashdata('notice', 'Call history file deleted.');
        redirect('callhistory');
    }

    public function lookup()
    {
        if (strtolower($this->input->method()) !== 'post') {
            show_404();
            return;
        }

        $callsign = strtoupper(trim((string)$this->input->post('callsign', TRUE)));
        $normalized_callsign = $this->normalize_callsign($callsign);

        $response = array(
            'status' => 'ok',
            'callsign' => $normalized_callsign,
            'matches' => array(),
            'count' => 0,
        );

        if (strlen($normalized_callsign) < 3) {
            header('Content-Type: application/json');
            echo json_encode($response);
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        $files = $this->callhistory_model->get_active_for_user($user_id);

        $all_matches = array();
        foreach ($files as $file) {
            $path = FCPATH . 'uploads/callhistory/' . $user_id . '/' . $file->stored_filename;
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $matches = $this->find_matches_in_file($path, $normalized_callsign, $file);
            if (!empty($matches)) {
                $all_matches = array_merge($all_matches, $matches);
            }
        }

        $response['matches'] = $all_matches;
        $response['count'] = count($all_matches);

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    private function find_matches_in_file($file_path, $callsign, $file)
    {
        $matches = array();
        $header_map = null;

        if (($handle = fopen($file_path, 'r')) === FALSE) {
            return $matches;
        }

        while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
            if (empty($row)) {
                continue;
            }

            $row = array_map('trim', $row);
            if (count($row) === 1 && $row[0] === '') {
                continue;
            }

            if ($this->is_comment_row($row)) {
                continue;
            }

            if ($this->looks_like_header($row)) {
                $header_map = $this->build_header_map($row);
                continue;
            }

            $row_callsign = $this->extract_by_map_or_index($row, $header_map, array('call', 'callsign', 'callsigns'), 0);
            $row_callsign = $this->normalize_callsign($row_callsign);

            if ($row_callsign === '' || $row_callsign !== $callsign) {
                continue;
            }

            $name = $this->extract_name($row, $header_map);
            $exch1 = $this->extract_by_map_or_index($row, $header_map, array('exch1', 'exchange1', 'exchange', 'member', 'membership'), 8);

            if ($exch1 === '' && is_null($header_map)) {
                $exch1 = $this->guess_exch1_without_header($row);
            }

            $matches[] = array(
                'file_id' => (int)$file->id,
                'file_label' => $file->file_label,
                'organization_label' => $file->organization_label,
                'name' => $name,
                'exch1' => $exch1,
                'sig' => $file->organization_label,
                'sig_info' => $exch1,
            );
        }

        fclose($handle);
        return $matches;
    }

    private function is_comment_row($row)
    {
        foreach ($row as $column) {
            $value = trim((string)$column);
            if ($value === '') {
                continue;
            }

            return strpos(ltrim($value, "\xEF\xBB\xBF"), '#') === 0;
        }

        return FALSE;
    }

    private function looks_like_header($row)
    {
        foreach ($row as $column) {
            $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$column));
            if (in_array($normalized, array('call', 'callsign', 'name', 'exch1', 'exchange1'), TRUE)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function build_header_map($row)
    {
        $map = array();
        foreach ($row as $index => $column) {
            $key = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$column));
            if ($key !== '') {
                $map[$key] = $index;
            }
        }
        return $map;
    }

    private function extract_by_map_or_index($row, $header_map, $candidate_keys, $default_index)
    {
        if (is_array($header_map)) {
            foreach ($candidate_keys as $key) {
                if (array_key_exists($key, $header_map)) {
                    $idx = $header_map[$key];
                    return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                }
            }
        }

        return isset($row[$default_index]) ? trim((string)$row[$default_index]) : '';
    }

    private function extract_name($row, $header_map)
    {
        $name = $this->extract_by_map_or_index($row, $header_map, array('name', 'operator', 'opname'), 1);
        if ($name !== '' || is_array($header_map)) {
            return $name;
        }

        if (isset($row[1], $row[2])) {
            $second_column = trim((string)$row[1]);
            $third_column = trim((string)$row[2]);

            if ($this->looks_like_exchange_value($second_column) && $this->looks_like_name_value($third_column)) {
                return $third_column;
            }
        }

        return $name;
    }

    private function guess_exch1_without_header($row)
    {
        if (isset($row[1], $row[2])) {
            $second_column = trim((string)$row[1]);
            $third_column = trim((string)$row[2]);

            if ($this->looks_like_exchange_value($second_column) && $this->looks_like_name_value($third_column)) {
                return $second_column;
            }

            if ($this->looks_like_name_value($second_column) && $this->looks_like_exchange_value($third_column)) {
                return $third_column;
            }
        }

        $indexes = array(8, 7, 6, 5, 4, 3, 2);
        foreach ($indexes as $index) {
            if (isset($row[$index]) && trim((string)$row[$index]) !== '') {
                return trim((string)$row[$index]);
            }
        }

        return '';
    }

    private function looks_like_name_value($value)
    {
        $value = trim((string)$value);
        if ($value === '' || preg_match('/\d/', $value)) {
            return FALSE;
        }

        if (preg_match('/[a-z]/', $value)) {
            return TRUE;
        }

        return strlen($value) > 4;
    }

    private function looks_like_exchange_value($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return FALSE;
        }

        return !$this->looks_like_name_value($value);
    }

    private function normalize_callsign($callsign)
    {
        $callsign = strtoupper(trim((string)$callsign));
        $callsign = str_replace('Ø', '0', $callsign);
        return $callsign;
    }
}
