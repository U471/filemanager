<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-auth-token,Content-Type, Content-Length, Accept-Encoding");
header("Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT");
header("Access-Control-Allow-Headers: x-auth-token,Origin, X-Requested-With, Content-Type, Accept, Authorization");
class UserController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    public function store()
    {
        try {
            // Manually load necessary models and libraries
            require_once(APPPATH . 'models/UseCases/CreateUser.php');
            // Get form data from POST request
            $name = $this->input->post('name');
            $email = $this->input->post('email');
            $password = $this->input->post('password');

            // Create an instance of the UserGateway
            $userGateway = new UserGateway();

            // Now pass the UserGateway instance to CreateUser
            $createUserUseCase = new CreateUser($userGateway);

            // Execute the use case to create the user
            $userId =   $createUserUseCase->execute($name, $email, $password);

            echo "User successfully created! User ID: " . $userId;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // API to get files and folders for a specific user_id
    // public function get_user_files($user_id)
    // {
    //     // Validate the user_id
    //     if (!$user_id) {
    //         $response = array('status' => false, 'message' => 'User ID is required');
    //         echo json_encode($response);
    //         return;
    //     }
    //     $upload_path = '/public/uploads/' . $user_id . '/';
    //     // Define the directory path based on the user_id
    //     $directory_path = FCPATH . $upload_path;

    //     // Check if the directory exists
    //     if (is_dir($directory_path)) {
    //         // Get directory map (list of files and folders)
    //         $directory_map = directory_map($directory_path);

    //         if (!empty($directory_map)) {
    //             $response = array('status' => true, 'data' => $directory_map);
    //         } else {
    //             $response = array('status' => false, 'message' => 'No files or folders found');
    //         }
    //     } else {
    //         // If directory doesn't exist, return an error
    //         $response = array('status' => false, 'message' => 'Directory not found for the provided user ID');
    //     }
    //     return $this->output
    //         ->set_content_type('application/json')
    //         ->set_status_header(200)
    //         ->set_output(json_encode(array(
    //             'status' => "true",
    //             'data' =>  $response,
    //         )));
    //     // Return response as JSON
    //     echo json_encode($response);
    // }
    public function image_upload()
    {
        if ($this->input->method(true) == 'POST') {
            $tokenData = $this->Auth->jwtDecoden($this->input->request_headers('x-auth-token'));
            if ($tokenData['status'] == 'true') {
                if (isset($_FILES['file'])) {

                    // Get user_id and directory from POST data
                    $user_id = $tokenData['data']->id;  // Assuming user_id is being passed with the request
                    $directory = $this->input->post('directory');  // Directory to upload the file to (optional)

                    // Validate user_id
                    if (!$user_id) {
                        return $this->output
                            ->set_content_type('application/json')
                            ->set_status_header(400)
                            ->set_output(json_encode(
                                array(
                                    'status' => "false",
                                    'message' => 'User ID is required',
                                    'data' => []
                                )
                            ));
                    }

                    // Define the dynamic upload path based on the user_id and directory
                    $upload_path = './public/uploads/' . $user_id . '/';

                    // If a directory is provided, append it to the path
                    if (!empty($directory)) {
                        $upload_path .= $directory . '/';
                    }

                    // Check if the directory exists, if not create it
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0777, true); // Create directory with appropriate permissions
                    }

                    // Upload configuration
                    $config['upload_path']          = $upload_path;
                    $config['allowed_types']        = '*';  // You can specify allowed file types (e.g., 'jpg|png|jpeg')
                    $config['max_size']             = 500000; // Max file size in KB
                    $config['file_name']            = time() . $_FILES['file']['name']; // Ensure unique file names
                    $config['file_name']            = $this->security->sanitize_filename($config['file_name']); // Sanitize file name

                    $this->upload->initialize($config);

                    // Attempt to upload the file
                    if (!$this->upload->do_upload('file')) {
                        return $this->output
                            ->set_content_type('application/json')
                            ->set_status_header(200)
                            ->set_output(json_encode(
                                array(
                                    'status' => "false",
                                    'data' => [],
                                    'error' => $this->upload->display_errors()
                                )
                            ));
                    }

                    // Get the file name after upload
                    $file_name = $this->upload->data('file_name');

                    // Return success response with the file path (relative to the document root)
                    $file_path = FCPATH . '/public/uploads/' . $user_id . '/' . (empty($directory) ? '' : $directory . '/') . $file_name;

                    // Return success response with the file URL
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(200)
                        ->set_output(json_encode(
                            array(
                                'status' => "true",
                                'data' => $file_name,
                                'message' => 'Successfully added',
                                'error' => ''
                            )
                        ));
                }
            } else {
                return $this->Response->errorResponse($tokenData['status'], $tokenData['message']);
            }
        }
    }

    public function get_user_files()
    {
        if ($this->input->method(true) == 'POST') {
            $tokenData = $this->Auth->jwtDecoden($this->input->request_headers('x-auth-token'));
            if ($tokenData['status'] == 'true') {
                // Get user_id and directory from POST data
                $user_id = $tokenData['data']->id;
                $directory = $this->input->post('directory');  // Optional directory path to list contents

                // Validate the user_id
                if (!$user_id) {
                    $response = array('status' => false, 'message' => 'User ID is required');
                    echo json_encode($response);
                    return;
                }
                // Define the base upload path for the user    . $user_id .
                $base_upload_path = FCPATH . '/' . 'public/uploads/' . $user_id;
                // If directory is provided, append it to the base path, otherwise list the base path
                $directory_path = !empty($directory) ? realpath($base_upload_path . '/' . $directory) : $base_upload_path;
                // Check if the directory exists
                if (is_dir($directory_path)) {
                    // Initialize an array to store the response data
                    $data = array();

                    // Use directory_map to get the folder structure
                    $directory_map = directory_map($directory_path, 1);  // Only get files and folders at this level

                    // If directory map is not empty
                    if (!empty($directory_map)) {
                        foreach ($directory_map as $item) {
                            // Get the full path of the item
                            $item_path = realpath($directory_path . '/' . $item);

                            // Determine if the item is a file or a folder
                            if (is_dir($item_path)) {
                                // It's a folder, so we add it as type "folder"
                                $data[] = array(
                                    'folder_name' => basename($item),  // Folder name
                                    'directory_path' => str_replace(FCPATH, '', $item_path),  // Relative path
                                    'type' => 'folder'
                                );
                            } elseif (is_file($item_path)) {
                                // It's a file, so we add it as type "file"
                                $data[] = array(
                                    'id' => pathinfo($item_path, PATHINFO_FILENAME),  // File name without extension
                                    'name' => basename($item),  // File name with extension
                                    'directory_path' => str_replace(FCPATH, '', $item_path),  // Relative path
                                    'type' => 'file',
                                    'extension' => '.' . pathinfo($item_path, PATHINFO_EXTENSION)  // File extension
                                );
                            }
                        }

                        // Return success response with the data
                        $response = array('status' => true, 'data' => $data);
                    } else {
                        // If no files or folders found
                        $response = array('status' => false, 'message' => 'No files or folders found');
                    }
                } else {
                    // If the directory doesn't exist
                    $response = array('status' => false, 'message' => 'Directory not found for the provided user ID');
                }

                // Return response as JSON
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            } else {
                return $this->Response->errorResponse($tokenData['status'], $tokenData['message']);
            }
        }
    }

    // API to create a folder in a specified directory
    public function create_folder()
    {
        if ($this->input->method(true) == 'POST') {
            $tokenData = $this->Auth->jwtDecoden($this->input->request_headers('x-auth-token'));
            if ($tokenData['status'] == 'true') {
                $user_id = $tokenData['data']->id;
                $directory = $this->input->post('directory');  // User-defined directory
                $folder_name = $this->input->post('folder_name');  // Folder name to be created
                // Validate inputs
                if (!$user_id || !$folder_name) {
                    $response = array('status' => false, 'message' => 'UserId and Folder Name are required');
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status' => $response['status'],
                                'message' => $response['message'],
                            )
                        ));
                }
                // Define the base path
                $base_path = FCPATH . '/public/uploads/';  // Fixed base path
                // Check if directory is provided, and build the full path accordingly
                if (!empty($directory)) {
                    // If directory is provided, include it in the path
                    $full_path = rtrim($base_path, '/') . '/' . $user_id . '/' . rtrim($directory, '/') . '/' . $folder_name . '/';
                } else {
                    // If no directory is provided, create the folder directly under user_id
                    $full_path = rtrim($base_path, '/') . '/' . $user_id . '/' . $folder_name . '/';
                }

                // Security check: Ensure the generated path starts with the base path to avoid unsafe directory creation
                $real_base_path = realpath($base_path);
                $real_full_path = realpath(dirname($full_path));


                if ($real_full_path && strpos($real_full_path, $real_base_path) !== 0) {
                    // If the generated path is outside the allowed base path, return an error
                    $response = array('status' => false, 'message' => 'Invalid directory path. Operation not allowed.');
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status' => $response['status'],
                                'message' => $response['message'],
                            )
                        ));
                }

                // Check if the directory already exists
                if (!is_dir($full_path)) {
                    // Attempt to create the directory with proper permissions (0755)
                    if (mkdir($full_path, 0755, true)) {
                        $response = array('status' => true, 'message' => 'Folder created successfully', 'path' => $full_path);
                    } else {
                        $response = array('status' => false, 'message' => 'Failed to create folder');
                    }
                } else {
                    // If the folder already exists
                    $response = array('status' => false, 'message' => 'Folder already exists');
                }

                // Return response as JSON
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode(
                        array(
                            'status' => $response['status'],
                            'data' => isset($response['path']) ? $response['path'] : '',
                            'message' => $response['message'],
                        )
                    ));
            } else {
                return $this->Response->errorResponse($tokenData['status'], $tokenData['message']);
            }
        }
    }

    // API to delete a folder or file
    public function delete_folder_or_file()
    {
        if ($this->input->method(true) == 'POST') {
            $tokenData = $this->Auth->jwtDecoden($this->input->request_headers('x-auth-token'));
            if ($tokenData['status'] == 'true') {
                // Get user_id, directory, and name (file or folder) from POST data
                $user_id = $tokenData['data']->id;
                $directory = $this->input->post('directory');  // Optional user-defined directory
                $name = $this->input->post('name');  // File or folder name to be deleted

                // Validate inputs
                if (!$user_id || !$name) {
                    $response = array('status' => false, 'message' => 'User ID and name are required');
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status' => $response['status'],
                                'message' => $response['message'],
                            )
                        ));
                }

                // Define the base path
                $base_path = FCPATH . '/public/uploads/';  // Fixed base path

                // Build the full path based on the directory and user_id
                $full_path = rtrim($base_path, '/') . '/' . $user_id . '/' . (empty($directory) ? '' : rtrim($directory, '/') . '/') . $name;

                // Security check: Ensure the generated path starts with the base path to avoid unsafe deletion
                $real_base_path = realpath($base_path);
                $real_full_path = realpath($full_path);

                if (!$real_full_path || strpos($real_full_path, $real_base_path) !== 0) {
                    // If the generated path is outside the allowed base path, return an error
                    $response = array('status' => false, 'message' => 'Invalid directory or file path. Operation not allowed.');
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(400)
                        ->set_output(json_encode(
                            array(
                                'status' => $response['status'],
                                'message' => $response['message'],
                            )
                        ));
                }

                // Check if the path is a file or a directory
                if (is_file($full_path)) {
                    // If it's a file, attempt to delete it
                    if (unlink($full_path)) {
                        $response = array('status' => true, 'message' => 'File deleted successfully');
                    } else {
                        $response = array('status' => false, 'message' => 'Failed to delete file');
                    }
                } elseif (is_dir($full_path)) {
                    // If it's a directory, attempt to delete it recursively
                    if ($this->delete_directory($full_path)) {
                        $response = array('status' => true, 'message' => 'Folder deleted successfully');
                    } else {
                        $response = array('status' => false, 'message' => 'Failed to delete folder. Ensure the folder is empty.');
                    }
                } else {
                    // If the file or folder does not exist
                    $response = array('status' => false, 'message' => 'File or folder does not exist');
                }

                // Return response as JSON
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode(
                        array(
                            'status' => $response['status'],
                            'message' => $response['message'],
                        )
                    ));
            } else {
                return $this->Response->errorResponse($tokenData['status'], $tokenData['message']);
            }
        }
    }

    // Helper function to recursively delete a directory and its contents
    private function delete_directory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        // Get all contents of the directory
        $items = array_diff(scandir($dir), array('.', '..'));

        foreach ($items as $item) {
            $item_path = $dir . '/' . $item;
            // If it's a directory, recursively delete it
            if (is_dir($item_path)) {
                $this->delete_directory($item_path);
            } else {
                // If it's a file, delete it
                unlink($item_path);
            }
        }

        // Remove the directory itself
        return rmdir($dir);
    }
}
