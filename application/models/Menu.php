<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 * ----------------------------------------------------------------------------
 *
 *
 *                               MENU MODEL 
 *
 *
 * ----------------------------------------------------------------------------
 */

class Menu extends CI_Model
{
	// Members
	public $items; // array
	// each menu item has:	name;			// string (array key)
	//       				url;			// string (array value)

	 public function __construct()
        {
                parent::__construct();
                // Your own constructor code

				// Load the Active Record database model
				$CI =& get_instance();
		        $CI->load->database();
		        $this->items = array();
    	}
    
    public function load($defaultURL = "verify") // show the 'verify' option as selected if no argument
    {
    	$CI =& get_instance();

        $html = "<ul class='tabrow'>";
    	// Add the menu from the local 'intel' DB info
		
		$query = $this->db->query("SELECT name,url from menu order by url");
		$data = array();
		
		foreach ($query->result() as $row)
		{
			$selected = ($row->url == $defaultURL) ? " selected"	: "";
	        $html .= "<li class=\"menu-item$selected\"><a href='/".$row->url."'>";
	        $html .= $row->name."</a>";
	        $html .= "</li>\n";
	        // Set the member variable values
	        $this->items[$row->name] = $row->url;
		}
		$html .= "</ul>";
		return $html;
    }
}
