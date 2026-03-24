<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Callhistory_model extends CI_Model {

    public function get_all_for_user($user_id)
    {
        $this->db->from('callhistory_files');
        $this->db->where('user_id', (int)$user_id);
        $this->db->order_by('priority', 'ASC');
        $this->db->order_by('uploaded_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_active_for_user($user_id)
    {
        $this->db->from('callhistory_files');
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('is_active', 1);
        $this->db->order_by('priority', 'ASC');
        $this->db->order_by('uploaded_at', 'DESC');
        return $this->db->get()->result();
    }

    public function create_file($data)
    {
        $this->db->insert('callhistory_files', $data);
        return $this->db->insert_id();
    }

    public function get_for_user_by_id($user_id, $id)
    {
        $this->db->from('callhistory_files');
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('id', (int)$id);
        return $this->db->get()->row();
    }

    public function update_active($user_id, $id, $is_active)
    {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('id', (int)$id);
        return $this->db->update('callhistory_files', array('is_active' => (int)$is_active));
    }

    public function update_priority($user_id, $id, $priority)
    {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('id', (int)$id);
        return $this->db->update('callhistory_files', array('priority' => (int)$priority));
    }

    public function delete_file($user_id, $id)
    {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('id', (int)$id);
        return $this->db->delete('callhistory_files');
    }
}
