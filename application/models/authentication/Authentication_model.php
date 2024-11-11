<?php
class Authentication_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }
  #-----------------  secret key ----------------
  public function secretkey()
  {
    $ciphertext = $this->db->where("tblsetting.key='jwt_secret_key'")->get('tblsetting')->row();
    return $ciphertext->value;
  }
  public function jwtDecoden($x_auth_token)
  {
    $key = $this->Auth->secretkey();
    try {
      $jwt = $x_auth_token;
      $token = $jwt['x-auth-token'];
      $decoded = JWT::decode($token, $key, ['HS256']);
      $exp = $decoded->exp;
      $date = new DateTime();
      $check_time = $date->getTimestamp();

      if ($check_time >= $exp) {
        return ['status' => 'false', 'message' => 'Access Denied!'];
      } else {
        return ['status' => 'true', 'message' => 'There is still time', 'data' => $decoded];
      }
    } catch (Exception $e) {
      return ['status' => 'false', 'message' => 'Error decoding token: ' . $e->getMessage()];
    }
  }
  public function getSetting($type)
  {
    $ciphertext = $this->db->where("tblsetting.key", $type)->get('tblsetting')->row();
    return $ciphertext->value;
  }
  #================== check email =================
  function check_email($email)
  {
    $email_registered = false;
    $email_message = '';
    // Check if the email is already registered
    $email_user = $this->db->where(array('email' => $email))->get('tbluser');
    if ($email_user->num_rows() > 0) {
      $email_registered = true;
      $data = $email_user->row();
      $email_message = 'Email already registered!';
    }

    // If only the email is registered
    if ($email_registered) {
      return ['status' => 'false', 'message' => $email_message];
    }

    // If both email and phone number are not registered
    return ['status' => 'true', 'message' => 'Email and phone number are not registered'];
  }
}
