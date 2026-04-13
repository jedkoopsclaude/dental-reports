<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verify extends CI_Controller {

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
	public function index($date = '')
	{
		

		// Load Menu
		$menu = $this->Menu->load('verify');


		if ($date == "") $date = $this->maxDate();
		$niceDate = date('m/d/Y', strtotime($date) );

		$datePicker = $this->datePicker($niceDate);

		$html = "<h3>Date: $datePicker</h3><p>NiceDate=$niceDate, date=$date</p>";

		// Pull data from OpenDental
		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.

		// Left Column: Production items for this date grouped by Provider, sorted by scheduled time (like daysheet)
		$query = $opendentaldb->query("SELECT pl.PatNum,p.FName,p.LName,pc.CodeNum,pc.AbbrDesc,pl.ProcFee*(pl.UnitQty+pl.BaseUnits) AS 'ProcFee',".
			" pl.ProvNum,concat(pr.FName,' ',pr.LName) as provName".
			" FROM procedurelog pl ".
			"LEFT JOIN procedurecode pc ON pl.CodeNum=pc.CodeNum". 
			" LEFT JOIN provider pr ON pl.ProvNum=pr.ProvNum".
			" LEFT JOIN patient p ON p.PatNum=pl.PatNum".
			" WHERE pl.ProcDate='$date'".
			" AND pl.ProcStatus = 2 ".
			" AND pc.CodeNum IS NOT NULL ".
			" ORDER BY pr.IsSecondary DESC,pl.ProvNum,p.LName,p.FName,pc.AbbrDesc");
		$daysheet = array();
		// Specify which columns to display
		$daysheetCols = array("AbbrDesc","FName","LName","provName","ProcFee");
		foreach ($query->result() as $row)
		{
			// add to array
			array_push($daysheet,$row);
		}

		// Right Column: Scheduled items grouped by Provider, sorted by scheduled time (like op schedule)
		$query = $opendentaldb->query("SELECT a.AptNum,a.AptDateTime,p.PatNum,p.FName,p.LName,pc.CodeNum,".
			" pc.AbbrDesc,pl.ProcFee*(pl.UnitQty+pl.BaseUnits) AS 'ProcFee',pl.ProvNum,concat(pr.FName,' ',pr.LName) as provName,".
			" pr.Specialty,pr.IsSecondary as Hygienist".
			" FROM appointment a ".
			" INNER JOIN patient p ON a.PatNum=p.PatNum ".
			" LEFT JOIN procedurelog pl ON pl.AptNum=a.AptNum".
			" LEFT JOIN procedurecode pc ON pl.CodeNum=pc.CodeNum". 
			" LEFT JOIN provider pr ON pl.ProvNum=pr.ProvNum".
			" WHERE DATE(a.AptDateTime)='$date'".
			" AND a.AptStatus='2'".
			" AND pc.CodeNum IS NOT NULL".
			" ORDER BY pr.IsSecondary DESC,pl.ProvNum,p.LName,p.FName,pc.AbbrDesc");
		$schedule = array();
		$scheduleCols = array("AptDateTime","FName","LName","provName","ProcFee");
		foreach ($query->result() as $row)
		{
			// add to array
			array_push($schedule, $row);
		}
		$data = array(
			'html' => $html,
			'daysheet' => $daysheet,
			'daysheetCols' => $daysheetCols,
			'schedule' => $schedule,
			'scheduleCols' => $scheduleCols
		);
		$subview = $this->load->view('verify', $data, TRUE);
		//$subview = $html;

		// reset Data array for outer template
		$data = array(
			"html" => $subview,
			"menu" => $menu
		);
		$this->load->view('template',$data);
	}

	public function maxDate()
	{
		// Look up latest procedure date (last day office was open)
		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.

		$query = $opendentaldb->query("select max(ProcDate) as maxDate from procedurelog where procstatus=2");
		$row = $query->row();
		return $row->maxDate;

	}

	public function datePicker($date = "")
	{
		$html = "<input id='date' type='text' name='date' size='10' value='$date' /> <button id='go'>Go</button>";
		return $html;
	}
	/**
	 * View Page for this controller. NOT NEEDED
	 *
	 */
	public function view($date = '')
	{
		

		// Load Menu
		$menu = $this->Menu->load();

		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.

		$query = $opendentaldb->query("SELECT count(*) from Payment");

		$data = array('menu' => $menu);

		$html = "<h3>Verify View: $date</h3>";
		

		$data['html'] = $html;
		$this->load->view('template',$data);
	}

}
