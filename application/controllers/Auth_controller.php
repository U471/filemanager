<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-auth-token,Content-Type, Content-Length, Accept-Encoding");
header("Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT");
header("Access-Control-Allow-Headers: x-auth-token,Origin, X-Requested-With, Content-Type, Accept, Authorization");
class Auth_controller extends CI_Controller
{
    public  function __construct()
    {
        parent::__construct();
    }

    // #----------- user sign up ------------
    public function signUp()
    {
        if ($this->input->method(true) == 'POST') {
            $_POST = json_decode(file_get_contents("php://input"), true);
            $user = $this->Auth->check_email($this->input->post('email'));

            if ($user['status'] == 'true') {
                $this->form_validation->set_rules('email', "email", 'trim|required');
                $this->form_validation->set_rules('password', "password", 'trim|required');

                if ($this->form_validation->run()) {
                    $signup = $this->Login->sign_up(array(
                        'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                        'email'    => $this->input->post('email'),
                    ));

                    if ($signup['status'] == 'true') {
                        $token = $this->getJwt($signup['data']);
                        return $this->output
                            ->set_content_type('application/json')
                            ->set_status_header(200)
                            ->set_output(json_encode(
                                array(
                                    'status'  => $signup['status'],
                                    'token' => $token,
                                    'message' => $signup['message'],
                                )
                            ));
                    } elseif ($signup['status'] == 'verify') {
                        return $this->output
                            ->set_content_type('application/json')
                            ->set_status_header(200)
                            ->set_output(json_encode(
                                array(
                                    'status'  => $signup['status'],
                                    'message' => $signup['message'],
                                )
                            ));
                    } else {
                        return $this->output
                            ->set_content_type('application/json')
                            ->set_status_header(400)
                            ->set_output(json_encode(
                                array(
                                    'status'  => $signup['status'],
                                    'message' => $signup['message'],
                                )
                            ));
                    }
                } else {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status'  => false,
                                'data'    => [],
                                'message' => $this->form_validation->error_string(" ", " ")
                            )
                        ));
                }
            } else {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(
                        array(
                            'status'  => false,
                            'data'    => '',
                            'message' =>  $user['message'],
                        )
                    ));
            }
        }
    }

    // public function verify_email()
    // {
    //     if ($this->input->method(true) == 'GET') {
    //         $token = $this->input->get('token');
    //         $verification_result = $this->Login->verify_email_token($token);

    //         if ($verification_result['status'] == 'true') {
    //             $token = $this->getJwt($verification_result['data']);
    //             return $this->output
    //                 ->set_content_type('application/json')
    //                 ->set_status_header(200)
    //                 ->set_output(json_encode(
    //                     array(
    //                         'status'  => $verification_result['status'],
    //                         'token' => $token,
    //                         'message' => $verification_result['message'],
    //                     )
    //                 ));
    //         } else {
    //             return $this->output
    //                 ->set_content_type('application/json')
    //                 ->set_status_header(400)
    //                 ->set_output(json_encode(
    //                     array(
    //                         'status'  => $verification_result['status'],
    //                         'message' => $verification_result['message'],
    //                     )
    //                 ));
    //         }
    //     }
    // }
    //============ Login api =========== 
    public function login()
    {
        log_message('debug', 'user login api call!');
        if ($this->input->method(TRUE) == 'POST') {
            $_POST = json_decode(file_get_contents("php://input"), true);
            $email = $this->input->post('email');
            $password = $this->input->post('password');
            if ($email !== null && is_string($email)) {
                $data = $this->Login->verify($this->input->post('email'), $password);
                if ($data['status'] == 'true') {
                    // $token = $this->getJwt($data['data']);
                    log_message('debug', "social login" . $data['status']);
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(200)
                        ->set_output(json_encode(
                            array(
                                'status' => $data['status'],
                                'message' => $data['message'],
                            )
                        ));
                } elseif ($data['status'] == 'verify') {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(200)
                        ->set_output(json_encode(
                            array(
                                'status'  => $data['status'],
                                'message' => $data['message'],
                            )
                        ));
                } else {
                    log_message('error', 'invalid credential');
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status' =>  $data['status'],
                                'message' => $data['message'],
                            )
                        ));
                }
            } else {
                log_message('error', 'The phone field is required.');
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(
                        array(
                            'status' => "false",
                            'data' => '',
                            'message' => 'The email field is required.'
                        )
                    ));
            }
        }
    }
    //-------------- otp disable ------------
    function otpVerify()
    {
        log_message('debug', "user otpVerify api call");
        if ($this->input->method(TRUE) == 'POST') {
            $_POST = json_decode(file_get_contents("php://input"), true);
            $this->form_validation->set_rules('email', "email", "trim|required");
            $this->form_validation->set_rules('token', 'otp Pin', 'trim|required|exact_length[6]');
            if ($this->form_validation->run() == true) {
                // $data = $this->Login->verifyUser($_POST);
                $data = $this->Login->verifyUser($this->input->post('email'), $this->input->post('token'));
                if ($data['status'] == 'true' || $data['status'] == 'profile_update') {
                    $token = $this->getJwt($data['data']);
                    log_message('debug', $data['message']);
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(200)
                        ->set_output(json_encode(
                            array(
                                'status' => $data['status'],
                                'token' => $token,
                                'message' => $data['message'],
                            )
                        ));
                } else {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status' => "false",
                                'data' => '',
                                'message' => $data['message'],
                            )
                        ));
                }
            } else {
                return $this->Response->validationErrorResponse();
            }
        }
    }
    function getJwt($data)
    {
        $jwt = new JWT();
        $JwtSecretKey = $this->Auth->secretkey();
        $date = date('Y-m-d H:i:s');
        $issuedAt   = new DateTimeImmutable($date);
        $expire     = $issuedAt->modify('+50000000 minutes')->getTimestamp();
        $data = array('id' => $data->id, 'email' => $data->email, 'exp' => $expire, 'issued_at' => $issuedAt);
        $token = $jwt->encode($data, $JwtSecretKey, 'HS256');
        return $token;
    }
}
?>