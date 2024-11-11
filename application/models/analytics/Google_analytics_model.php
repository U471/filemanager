<?php
class Google_analytics_model extends CI_Model
{
    public  function __construct()
    {
        parent::__construct();
    }

    // Function to get leads by google_keyword and geo
    public function get_leads_by_keyword_and_geo($google_keyword, $geo)
    {
        // Split the input string by spaces, slashes, or other delimiters into individual keywords
        $keywords = preg_split('/[\/\s]+/', strtolower(trim($google_keyword)));

        // Build the query
        $this->db->select('*');
        $this->db->from('tblgoogle_data');

        // Add the exact match condition for 'geo'
        $this->db->where('geo', $geo);

        // Add WHERE clause for the google_keyword to check partial matches with OR conditions
        $this->db->group_start();  // Open group to apply OR condition for multiple keywords
        foreach ($keywords as $word) {
            $this->db->or_like('LOWER(google_keyword)', $word);  // Add a LIKE condition for each keyword
        }
        $this->db->group_end();  // Close group for OR condition

        // Execute the query and fetch the result
        $query = $this->db->get();
        return $query->result_array();
    }
}
