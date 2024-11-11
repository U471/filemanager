<?php
class Response_model extends CI_Model
{
    function Response($response)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($response["status_code"])
            ->set_output(json_encode([
                'status' => $response["status"],
                'message' => $response["message"],
                'error' => ''
            ]));
    }
    function validationErrorResponse()
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => "false",
                'data' => [],
                'error' => $this->form_validation->error_string(' ', ' ')
            ]));
    }
    function errorResponse($status, $message)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(400)
            ->set_output(json_encode([
                'status' => $status,
                'message' => $message
            ]));
    }
}