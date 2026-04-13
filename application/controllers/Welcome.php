<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$CI =& get_instance();
		// Load menu
		$CI->load->model("Menu");
		$menu = $CI->Menu->load();	

		// Load some MySQL info here
		//$this->load->database(); // this loads the local INTEL database


		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.

		/*$query = $mysqli->query("SELECT PayDate,PayType,SUM(PayAmt) FROM payment WHERE PayDate = '2020-03-31' GROUP BY PayType ORDER BY PayAmt");

  		$html = "Number of rows: $query->num_rows<br /><br />";

		while ($row = mysqli_fetch_array($query))
		{
			//var_dump($row);
		    $html .= "Date: ". $row['PayDate'] . " Type:" . $row['PayType'] . " Total: \$" . $row[2] . " "  . "<br/>";
		}*/
		$query = $opendentaldb->query("SELECT PayDate,PayType,SUM(PayAmt) AS PaySum FROM payment WHERE PayDate = '2020-03-31' GROUP BY PayType ORDER BY PayAmt");

		$data = array("menu" => $menu);
		$html = "";

		foreach ($query->result() as $row)
		{
		        $html .= "<div>Date: ".$row->PayDate;
		        $html .= ", Payment Type=".$row->PayType;
		        $html .= ", Total=\$".$row->PaySum;
		        $html .= "</div>";
		}

		$data['html'] = $html;
		$this->load->view('welcome_message',$data);
	}
}
