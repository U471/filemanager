<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-auth-token,Content-Type, Content-Length, Accept-Encoding");
header("Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT");
header("Access-Control-Allow-Headers: x-auth-token,Origin, X-Requested-With, Content-Type, Accept, Authorization");
class Google_analytics_controller extends CI_Controller
{
    public  function __construct()
    {
        parent::__construct();
    }

    public function get_data()
    {
        if ($this->input->method(true) == 'GET') {
            // Decode JWT token from the request header
            $tokenData = $this->Auth->jwtDecoden($this->input->request_headers('x-auth-token'));

            // Check if token is valid
            if ($tokenData['status'] == 'true') {
                // Extract the google_keyword and geo from the query string
                $google_keyword = $this->input->get('google_keyword');
                $geo = $this->input->get('geo');

                // Check if the required parameters are provided
                if ($google_keyword && $geo) {
                    // Get leads based on google_keyword and geo for the authenticated user
                    $leads = $this->GoogleAnalyticsModel->get_leads_by_keyword_and_geo($google_keyword, $geo);

                    if (!empty($leads)) {
                        // Return the leads with their content
                        return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(200)
                        ->set_output(json_encode(
                            array(
                                'status'  => 'true',
                                'data' => $leads,
                            )
                        ));
                    } else {
                        return $this->Response->errorResponse('false', 'No leads found for the given criteria');
                    }
                } else {
                    return $this->Response->errorResponse('false', 'Missing google_keyword or geo in the request');
                }
            } else {
                return $this->Response->errorResponse($tokenData['status'], $tokenData['message']);
            }
        } else {
            return $this->Response->errorResponse('false', 'Invalid request method');
        }
    }
}
