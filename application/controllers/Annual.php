<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
 *					Annual controller (Annual Analytics)
 *
 *
*/
class Annual extends CI_Controller {

 	public function __construct()
        {
                parent::__construct();

                $CI =& get_instance();
				// Load models used throughout this controller
				$CI->load->model("Menu");
        }
	/**
	 * Index Page for this controller.
	 *
	 */
	public function index($year = 2025)
	{
		

		// Load Menu
		$menu = $this->Menu->load('annual');

		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.

		// $query = $opendentaldb->query("SELECT PayDate,PayType,SUM(PayAmt) AS PaySum FROM payment WHERE PayDate = '2020-03-31' GROUP BY PayType ORDER BY PayAmt");
		$query = $opendentaldb->query("SELECT PayDate,PayType,SUM(PayAmt) AS PaySum FROM payment WHERE PayDate = '2020-03-31' GROUP BY PayType ORDER BY PayAmt");

		$data = array('menu' => $menu);
		//$category = "ortho";
		$html = "<br /><h3>Annual Performance: $year</h3>";
		foreach ($query->result() as $row)
		{
		        $html .= "<div>Date: ".$row->PayDate;
		        $html .= ", Payment Type=".$row->PayType;
		        $html .= ", Total=\$".$row->PaySum;
		        $html .= "</div>";
		}

		$data['html'] = $html;
		$this->load->view('template',$data);
	}



}
