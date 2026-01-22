<?php
class User_model extends CI_Model {

    public function saveFcmToken($user_id, $token)
    {
        $this->db->where('id', $user_id);
        $this->db->update('users', [
            'fcm_token' => $token
        ]);
    }
}
