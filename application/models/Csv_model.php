<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Csv_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        // Load database
        $this->load->database();
    }

    // Save CSV data to the database
    public function save_csv_datas($data)
    {
        $dataToInsert = [];
        foreach ($data as $value) {
            $dataToInsert[] = [
                'ip_address' => $value['ip_address'],
                'google_id' => $value['google_id'],
                'first_name' => $value['first_name'],
                'last_name' => $value['last_name'],
                'email' => $value['email'],
                'google_keyword' => $value['google_keyword'],
                'geo' => $value['geo']
            ];
        }

        if (!empty($dataToInsert)) {
            // Insert batch data into the database
            $this->db->insert_batch('tblgoogle_data', $dataToInsert);
            return true;
        }
        return false;
    }

     // Save CSV data in chunks to handle large data sets
     public function save_csv_data($data)
     {
         if (!empty($data)) {
             // Insert batch data into the database
             $this->db->insert_batch('tblgoogle_data', $data);
             return true;
         }
         return false;
     }
}
