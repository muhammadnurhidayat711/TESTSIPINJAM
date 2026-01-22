<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification extends CI_Controller {

    public function saveToken()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? null;

        if (!$token) {
            echo json_encode(['success' => false]);
            return;
        }

        // Ambil user dari session CI
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $this->load->model('User_model');
        $this->User_model->saveFcmToken($user_id, $token);

        echo json_encode(['success' => true]);
    }
}
