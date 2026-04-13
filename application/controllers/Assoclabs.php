<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assoclabs extends CI_Controller {

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
		$menu = $this->Menu->load('assoclabs');


		if ($date == "") $date = $this->maxDate();
		$month = date('Y-m', strtotime($date) );
		$niceDate = date('M Y', strtotime($date) );
		$startDate = date('Y-m-01', strtotime($date) );
		$endDateInternal = new DateTime("last day of ".$date);
		$endDate = $endDateInternal->format('Y-m-d');

		$html = "<h3 id='monthName'>$niceDate</h3>";
		// Pull data from OpenDental
		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.

		// Get Associate's Collections

		$assocID = 216;
		//Associate's Patient collections 
		$query = $opendentaldb->query("SELECT SUM(ps.SplitAmt) AS pattotal FROM paysplit ps ".
				" WHERE ps.ProvNum=$assocID AND ps.DatePay BETWEEN '$startDate' AND '$endDate'");
		$jfpRow = $query->row();
		$jfPat = '$'.number_format($jfpRow->pattotal, 2);

		// Next get Associate's Ins collections 
		$query = $opendentaldb->query("SELECT SUM(cp.InsPayAmt) AS instotal FROM claimpayment cy ".
				" INNER JOIN claimproc cp ON cp.ClaimPaymentNum=cy.ClaimPaymentNum ".
			 	" WHERE cp.ProvNum=$assocID AND cy.CheckDate  BETWEEN '$startDate' AND '$endDate'");
		$jfiRow = $query->row();
		$jfIns = '$'.number_format($jfiRow->instotal, 2);
		
		// Calc Associate's total collections
		$jfTotal = $jfpRow->pattotal + $jfiRow->instotal;
		$prettyjfTotal = '$'.number_format( $jfTotal, 2 );
		$html .= "<div id='jerrytotal'><span class='provname'>Dr. Thanh's Collections:</span><span class='accounting'>$prettyjfTotal</span></div>";
		$thirtythree = $jfTotal * 0.35;
		$pretty33 = '$'.number_format( $thirtythree, 2);
		$html .= "<div id='jerry33' class='row'><span class='provname'>35%:</span><span class='accounting'>$pretty33</span></div>";
		// Calculate total Lab fees
		/* SELECT l.DateTimeRecd,CONCAT(p.FName,' ',p.LName) as Name,lab.Description,l.LabFee,a.Note from labcase l
			LEFT JOIN patient p ON p.Patnum=l.PatNum
			LEFT JOIN laboratory lab ON lab.LaboratoryNum = l.LaboratoryNum
			LEFT JOIN appointment a ON a.AptNum = l.AptNum
			WHERE datetimerecd BETWEEN '2020-07-01' AND '2020-07-31'
		*/
		$labCaseCols = array("DateTimeRecd" => "Received",
				"Name" => "Name",
				"Description" => "Lab",
				"LabFee" => "Fee",
				"Note" => "Note");
		$query = $opendentaldb->query("SELECT l.DateTimeRecd,CONCAT(p.FName,' ',p.LName) as Name,".
				" lab.Description,l.LabFee,a.Note from labcase l ".
				" LEFT JOIN patient p ON p.Patnum=l.PatNum ".
				" LEFT JOIN laboratory lab ON lab.LaboratoryNum = l.LaboratoryNum ".
				" LEFT JOIN appointment a ON a.AptNum = l.AptNum ".
				" WHERE l.ProvNum=$assocID AND datetimerecd BETWEEN '$startDate' AND '$endDate' ORDER BY datetimerecd");
		$labCases = array();
		// show header columns
		$labhtml = "<hr /><div class='labcase row header'>";
		foreach ($labCaseCols as $key => $label) {
			$labhtml .= "<span class='labcol $key'>$label</span>\n";
		}
		$labhtml .= "</div>\n";
		$totalFees = 0;
		foreach ($query->result() as $row)
		{
			//array_push($labCases, $row);
			$labhtml .= "<div class='labcase row'>";
			foreach ($labCaseCols as $key => $value) {
				// If showing Lab Fee, format to 2 digits, else show normally
				$display = ($key == "LabFee") ? '$'.number_format($row->$key,2) : $row->$key;
				
				$labhtml .= "<span class='labcol $key'>" . $display . "</span>\n";
			}
			$labhtml .= "</div>";
			$totalFees += $row->LabFee;
		}
		$labTotal = '$'.number_format( $totalFees, 2);
		$twenty5 = $totalFees * 0.30;
		$labPercent = '$'.number_format( $twenty5 , 2);
		$html .= "<div id='labtotal'><span class='provname'>Lab Fees:</span><span class='accounting'>$labTotal</span></div>";
		$html .= "<div id='lab25'><span class='provname'>30%:</span><span class='accounting'>$labPercent</span></div>";

		// Now calculate Associate's paycheck
		$roundedThirty3 = round( $thirtythree , 2);
		$roundedTwenty5 = round( $twenty5 , 2 );
		$jerryCheck = $roundedThirty3 - $roundedTwenty5;
		//$jerryCheck = $thirtythree - $twenty5;
		$prettyCheck = '$'.number_format( $jerryCheck , 2);
		$html .= "<div id='jerrycheck'><span class='provname'>Paycheck:</span><span class='accounting'>$prettyCheck</span></div>";

		// Now list lab details
		$html .= $labhtml;

		// Get total crown and bridge units (D2740, D6058 (implant crowns), D6740 (bridge retainer) + D6245 (pontics)
		$query = $opendentaldb->query("SELECT COUNT(*) AS 'Total' ".
			" FROM procedurelog pl INNER JOIN procedurecode pc ON pc.CodeNum=pl.CodeNum".
			" WHERE pl.ProvNum=$assocID AND pl.ProcDate BETWEEN '$startDate' AND '$endDate'".
			" AND pl.ProcStatus = 2 AND pc.ProcCode IN ('D2740', 'D6058', 'D6740', 'D6245')"  );
		$crownRow = $query->row();
		$crownTotal = $crownRow->Total;
		$html .= "<hr />   <div id='crowns'><span class='provname'>Total Crown Units:</span><span class='accounting'>$crownTotal</span></div>";

		

		// List lab cases

		$data = array(
			'html' => $html,
			'month' => $month
		);
		$subview = $this->load->view('labfees', $data, TRUE);
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
