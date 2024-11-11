<?php
date_default_timezone_set('UTC');
class Login_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }
    function sign_up($data)
    {
        // $data['verification_token'] = bin2hex(random_bytes(16)); // Generate a random token
        $this->db->insert('tbluser', $data);
        $id = $this->db->insert_id();
        $user = $this->db->where('tbluser.id', $id)->get('tbluser')->row();
        return ['status' => 'true', 'message' => 'successfully verified', 'data' => $user];
        // if ($this->email($user->email, "Verifiation email", $user->verification_token, $user->firstName)) {
        //     return ['status' => 'verify', 'message' => 'Verification code sent to your Email'];
        // } else {
        //     return ['status' => 'false', 'message' => 'Error send verification otp'];
        // }
    }
    function verify($email, $password)
    {
        $user = $this->db->where('email', $email)
            ->get('tbluser')
            ->row();

        // If user is not found by either email or phone
        if (!$user) {
            return ['status_code' => 400, 'status' => 'false', 'message' => 'User not registered'];
        }

        // If email doesn't match
        if ($user->email !== $email) {
            return ['status_code' => 400, 'status' => 'false', 'message' => 'Email not registered with this account.'];
        }

        // Verify the password using password_verify
        if (!password_verify($password, $user->password)) {
            return ['status_code' => 400, 'status' => 'false', 'message' => 'Password invalid.'];
        }

        // Generate unique token
        do {
            $token = rand(100000, 999999);
            $check = $this->db->where('token', $token)->get('tbluser');
        } while ($check->num_rows() > 0);
        // Update user with token and last login
        $this->db->where('id', $user->id)
        ->update('tbluser', [
            'token' =>  $token,
        ]);
        // Send verification email
        if ($this->email($user->email, "Verification mail", "verification otp " . $token)) {
            return ['status_code' => 200, 'status' => 'verify', 'message' => 'Verification code sent to your Email'];
        } else {
            return ['status_code' => 400, 'status' => 'false', 'message' => 'Error sending verification otp'];
        }

        // return ['status' => 'true', 'message' => 'successfully verified', 'data' => $user];
    }
    function verifyUser($email, $token)
    {
        $check = $this->db->where(array('email' => $email))->get('tbluser');
        $user = ($check->num_rows() > 0) ? $check->row() : null;
        $notvalid = "email";
        if (isset($user)) {
            // if ($this->isOtpExpired($user->token_created_at)) {
            //     return ['status' => 'false', 'message' => 'OTP has expired, please request a new one'];
            // }
            if ($user->token == $token) {
                $this->db->where('tbluser.id', $user->id)->update(
                    'tbluser',
                    array('otp_verification' => 1, 'last_login' => date('y-m-d H:i:s', time()))
                );
                if ($this->db->affected_rows() > 0) {
                    return ['status' => 'true', 'message' => 'successfully verify', 'data' => $user];
                    // if ($user->profile_status) {
                    //     return ['status' => 'true', 'message' => 'successfully verify', 'data' => $user];
                    // } else {
                    //     return ['status' => 'profile_update', 'message' => 'successfully verify', 'data' => $user];
                    // }
                }
            } else {
                return ['status' => 'false', 'message' => 'invalid otp, please try again with the correct otp'];
            }
        } else {
            return ['status' => 'false', 'message' => $notvalid . ' not valid'];
        }
    }
    function isOtpExpired($tokenCreatedAt, $expirationMinutes = 10)
    {
        $current_time = time();
        $token_created_at = strtotime($tokenCreatedAt);
        $expiration_time = $token_created_at + ($expirationMinutes * 60); // Token expires after specified minutes
        return $current_time > $expiration_time;
    }
    function email($to, $subject, $email_content)
    {
        // return true;
        // Email configuration
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.gmail.com';
        $config['smtp_port'] = '465';
        $config['smtp_timeout'] = '7';
        $config['smtp_user'] = 'usamavirtualsoftcompany@gmail.com'; // Update with your Gmail email
        $config['smtp_pass'] = 'kwxljbhyhvytlubl'; // Update with your Gmail password
        $config['charset'] = 'utf-8';
        $config['newline'] = "\r\n";
        $config['mailtype'] = 'html'; // Set email format to HTML
        $config['validation'] = true;

        // Initialize email configuration
        $this->email->initialize($config);
        $this->email->from('usamavirtualsoftcompany@gmail.com', 'Virtualsoft'); // Update with your sender email and name
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($email_content);

        // Send email
        if ($this->email->send()) {
            return true;
        } else {
            return false;
        }
    }
    //     function verify_email_token($token)
    //     {
    //         $user = $this->db->where('verification_token', $token)->get('tbluser')->row();
    //         if ($user) {
    //             $this->db->where('id', $user->id)
    //                 ->update('tbluser', [
    //                     'verification_token' => null // Reset the token 
    //                 ]);
    //             return ['status' => 'true', 'message' => 'Email verified successfully', 'data' => $user];
    //         } else {
    //             return ['status' => 'false', 'message' => 'Invalid token'];
    //         }
    //     }
    //     function emailss($to, $subject, $token, $firstName)
    //     {
    //         $confirmationLink = "http://192.168.100.58:3000/#/confirmation/" . $token;
    //         // Email configuration
    //         $config['protocol'] = 'smtp';
    //         $config['smtp_host'] = 'ssl://smtp.gmail.com';
    //         $config['smtp_port'] = '465';
    //         $config['smtp_timeout'] = '7';
    //         $config['smtp_user'] = 'usamavirtualsoftcompany@gmail.com'; // Update with your Gmail email
    //         $config['smtp_pass'] = 'kwxljbhyhvytlubl'; // Update with your Gmail password
    //         $config['charset'] = 'utf-8';
    //         $config['newline'] = "\r\n";
    //         $config['mailtype'] = 'html'; // Set email format to HTML
    //         $config['validation'] = true;

    //         // Initialize email configuration
    //         $this->email->initialize($config);

    //         $emailContent  = "
    // <!DOCTYPE html>
    // <html lang='en'>
    // <head>
    //     <meta charset='UTF-8'>
    //     <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    //     <title>Email Confirmation</title>
    //     <style>
    //         a {
    //             background-color: #3F83F8;
    //             color: white;
    //             padding: 10px 20px;
    //             text-decoration: none;
    //             border-radius: 5px;
    //         }
    //         a:hover {
    //             background-color: #3266c0;
    //         }
    //         body {
    //             margin: 0;
    //             padding: 0;
    //             display: flex;
    //             justify-content: center;
    //             align-items: center;
    //             height: 100vh;
    //             background-color: #f9f9f9;
    //         }
    //         .container {
    //             background-color: white;
    //             border-radius: 10px;
    //             padding: 20px;
    //             box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    //             text-align: center;
    //         }
    //         p {
    //             text-align: left;
    //             margin-bottom: 50px;
    //         }
    //         hr {
    //             margin-top: 50px;
    //         }c
    //     </style>
    // </head>
    // <body>
    //     <div class='container'>
    //         <h1>Welcome , $firstName!</h1>
    //         <p>
    //             Thanks for trying Instantly. We’re thrilled to have you on board. To proceed with your registration, please confirm your email address:
    //         </p>
    //         <a href='$confirmationLink'>Click here to confirm your email</a>
    //         <hr>
    //         <p>
    //             Thanks <br>
    //             Instantly Team
    //         </p>
    //     </div>
    // </body>
    // </html>
    // ";
    //         $this->email->from('usamavirtualsoftcompany@gmail.com', 'Virtualsoft'); // Update with your sender email and name
    //         $this->email->to($to);
    //         $this->email->subject($subject);
    //         $this->email->message($emailContent);

    //         // Send email
    //         if ($this->email->send()) {
    //             return true;
    //         } else {
    //             return false;
    //         }
    //     }


    //     function email($to, $subject, $token, $firstName)
    //     {
    //         $confirmationLink = "http://192.168.100.58:3000/#/confirmation/" . $token;
    //         // Email configuration
    //         $config['protocol'] = 'smtp';
    //         $config['smtp_host'] = 'ssl://smtp.gmail.com';
    //         $config['smtp_port'] = '465';
    //         $config['smtp_timeout'] = '7';
    //         $config['smtp_user'] = 'usamavirtualsoftcompany@gmail.com'; // Update with your Gmail email
    //         $config['smtp_pass'] = 'kwxljbhyhvytlubl'; // Update with your Gmail password
    //         $config['charset'] = 'utf-8';
    //         $config['newline'] = "\r\n";
    //         $config['mailtype'] = 'html'; // Set email format to HTML
    //         $config['validation'] = true;

    //         // Initialize email configuration
    //         $this->email->initialize($config);
    //         $firstName = $firstName;
    //         $projectName = 'Web Development Project';
    //         $email= "<p><strong>Hi {{firstName}},</strong></p><p>I hope this message finds you well!</p><p>I am reaching out to discuss the opportunity for collaboration on our upcoming web development project";
    //         $data = "
    //         <!DOCTYPE html>
    //         <html lang='en'>
    //         <head>
    //             <meta charset='UTF-8'>
    //             <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    //             <title>Email Confirmation</title>
    //         </head>
    //         <body>
    //                $email
    //         </body>
    //         </html>
    //         ";
    //         $emailContent = str_replace(
    //             ['{{firstName}}'], // placeholders
    //             [$firstName],           // values to replace
    //             $data                                 // template content
    //         );
    //         $this->email->from('usamavirtualsoftcompany@gmail.com', 'Virtualsoft'); // Update with your sender email and name
    //         $this->email->to($to);
    //         $this->email->subject($subject);
    //         $this->email->message($emailContent);

    //         // Send email
    //         if ($this->email->send()) {
    //             return true;
    //         } else {
    //             return false;
    //         }
    //     }
}
