<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Csv_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Csv_model');
    }

    // public function upload_csv()
    // {
    //     // Check if a CSV file is uploaded
    //     if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    //         // Parse the CSV file
    //         $csvData = $this->parse_csv($_FILES['csv_file']['tmp_name']);

    //         // Save parsed data to the database
    //         if ($this->Csv_model->save_csv_data($csvData)) {
    //             return $this->Response->Response(array("status_code" => 200, "status" => "true", "message" => "Data saved successfully"));
    //         } else {
    //             return $this->Response->Response(array("status_code" => 400, "status" => "false", "message" => "Failed to save data"));
    //         }
    //     } else {
    //         return $this->Response->Response(array("status_code" => 400, "status" => "false", 'message' => 'No file uploaded or an error occurred during upload'));
    //     }
    // }

    // private function parse_csv($filePath)
    // {
    //     $data = [];
    //     if (($handle = fopen($filePath, 'r')) !== FALSE) {
    //         $header = fgetcsv($handle, 3000, ","); // Skip header row
    //         while (($row = fgetcsv($handle, 3000, ",")) !== FALSE) {
    //             // Map CSV row data to array format for database
    //             $data[] = [
    //                 'ip_address' => $row[0],         // IP Address
    //                 'google_id' => $row[1],          // Google ID
    //                 'first_name' => $row[2],         // First Name
    //                 'last_name' => $row[3],          // Last Name
    //                 'email' => $row[4],              // Email
    //                 'google_keyword' => $row[5],     // Google Keyword
    //                 'geo' => $row[6]                 // GEO
    //             ];
    //         }
    //         fclose($handle);
    //     } else {
    //         // If the file cannot be opened, print an error message
    //         echo "Error opening file: $filePath\n";
    //     }
    //     return $data;
    // }

    // public function upload_csv()
    // {
    //     // Check if a CSV file is uploaded
    //     if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    //         // Parse the CSV file and insert data in chunks
    //         $filePath = $_FILES['csv_file']['tmp_name'];

    //         if ($this->process_csv_in_chunks($filePath)) {
    //             return $this->Response->Response(array("status_code" => 200, "status" => "true", "message" => "Data saved successfully"));
    //         } else {
    //             return $this->Response->Response(array("status_code" => 400, "status" => "false", "message" => "Failed to save data"));
    //         }
    //     } else {
    //         return $this->Response->Response(array("status_code" => 400, "status" => "false", 'message' => 'No file uploaded or an error occurred during upload'));
    //     }
    // }

    public function upload_csv()
    {
        // Check if a CSV file is uploaded
        if (isset($_FILES['csv_file'])) {
            if ($_FILES['csv_file']['error'] == 0) {
                // Parse the CSV file and insert data in chunks
                $filePath = $_FILES['csv_file']['tmp_name'];

                if ($this->process_csv_in_chunks($filePath)) {
                    return $this->Response->Response(array("status_code" => 200, "status" => "true", "message" => "Data saved successfully"));
                } else {
                    return $this->Response->Response(array("status_code" => 400, "status" => "false", "message" => "Failed to save data"));
                }
            } else {
                // If there's an error with the file upload
                return $this->Response->Response(array(
                    "status_code" => 400,
                    "status" => "false",
                    "message" => "File upload error",
                    "error" => $_FILES['csv_file']['error']  // Display the file upload error
                ));
            }
        } else {
            return $this->Response->Response(array("status_code" => 400, "status" => "false", 'message' => 'No file uploaded'));
        }
    }


    // Process CSV file in chunks and insert to database
    private function process_csv_in_chunks($filePath)
    {
        $batchSize = 1000; // Number of rows to process per batch
        $data = [];
        $rowCount = 0;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            fgetcsv($handle); // Skip header row

            while (($row = fgetcsv($handle, 3000, ",")) !== FALSE) {
                // Map CSV row data to array format for database
                $data[] = [
                    'ip_address' => $row[0],         // IP Address
                    'google_id' => $row[1],          // Google ID
                    'first_name' => $row[2],         // First Name
                    'last_name' => $row[3],          // Last Name
                    'email' => $row[4],              // Email
                    'google_keyword' => $row[5],     // Google Keyword
                    'geo' => $row[6]                 // GEO
                ];

                $rowCount++;

                // If the batch size is reached, insert data into the database
                if ($rowCount % $batchSize == 0) {
                    $this->Csv_model->save_csv_data($data); // Insert batch
                    $data = []; // Clear array for next batch
                }
            }

            // Insert any remaining data (if total rows are not a perfect multiple of batch size)
            if (!empty($data)) {
                $this->Csv_model->save_csv_data($data);
            }

            fclose($handle);
            return true;
        }

        return false;
    }
}
