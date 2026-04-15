<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Monthly extends CI_Controller {

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
		$menu = $this->Menu->load('monthly');


		if ($date == "") $date = $this->maxDate();
		$month = date('Y-m', strtotime($date) );
		$niceDate = date('M Y', strtotime($date) );
		$startDate = date('Y-m-01', strtotime($date) );
		$endDateInternal = new DateTime("last day of ".$date);
		$endDate = $endDateInternal->format('Y-m-d');

		$html = "<h3 id='monthName'>$niceDate</h3>";
		// Pull data from OpenDental
		$opendentaldb = $this->load->database('opendental', TRUE); // the TRUE paramater tells CI that you'd like to return the database object.
		// Get list of providers who produced something this month
		/* Jed, Jerry, Janet, Steph, Ashley, Temp Hygienist */
		$query = $opendentaldb->query("SELECT  pl.ProvNum as id,concat(pr.FName,' ',pr.LName) as provName,pr.IsSecondary as isHygienist, ".
			" SUM(pl.ProcFee*(pl.UnitQty+pl.BaseUnits)) AS fees ".
			" FROM procedurelog pl ".
			" LEFT JOIN provider pr ON pl.ProvNum=pr.ProvNum".
			" WHERE pl.ProcDate BETWEEN '$startDate' AND '$endDate'".
			" AND pl.ProcStatus = 2 ".
			" GROUP BY pr.IsSecondary DESC,pr.LName,pr.FName");

		$providers = array();
		// Specify which columns to display
		$daysheetCols = array("AbbrDesc","FName","LName","provName","ProcFee");
		foreach ($query->result() as $row)
		{
			// add to array
			array_push($providers,$row);
		}
		$html .= "<div><h3>Production</h3></div>\n";
		// Loop through providers and save their totals
		$hygiene = "";
		$hygieneTotal = 0;
		$restorative = "";
		$restorativeTotal = 0;
		foreach ($providers as $row)
		{
			if ($row->fees == 0) continue;
			$total = '$'.number_format($row->fees, 2);
			$line = "<div class='row'><span class='provname'>".$row->provName.":</span> <span class='accounting'>$total</span></div>";
			if ($row->isHygienist == 0)
			{	
				$restorative .= $line;
				$restorativeTotal += $row->fees;
			}
			else
			{
				$hygiene .= $line;
				$hygieneTotal += $row->fees;
			}
		}
		$grandTotal = $restorativeTotal + $hygieneTotal;
		$restorativeTotal = '$'.number_format($restorativeTotal, 2);
		$prettyHygieneTotal = '$'.number_format($hygieneTotal, 2); 
		$hygienePercent = number_format( ($hygieneTotal / $grandTotal * 100), 2) ."%";
		$prettyGT = '$'.number_format($grandTotal, 2);

		$html .= $restorative;
		$html .= "<div id='restorativeTotal'><span class='label'>Total Restorative:</span> <span class='accounting'>$restorativeTotal</span></div>\n";
		$html .= $hygiene;
		$html .= "<div id='hygieneTotal'><span class='label'>Total Hygiene:</span> <span class='accounting'>$prettyHygieneTotal ($hygienePercent)</span></div>\n";

		$html .= "<div id='grandTotal' class='row'><span class='provname'>Gross Production:</span> <span class='accounting'>$prettyGT</span></div>\n";

		// Get adjustments to production
		$query = $opendentaldb->query("SELECT SUM(AdjAmt) as totaladj FROM adjustment ".
			"WHERE AdjDate BETWEEN '$startDate' AND '$endDate'");
		$aRow = $query->row();
		$adjustments = '$'.number_format($aRow->totaladj, 2);

		$html .= "<div id='totalAdj' class='row'><span class='provname'>Adjustments:</span><span class='accounting'>$adjustments</span></div>";

		// Get Insurance Writeoffs
		/* SELECT SUM(cp.Writeoff) FROM claimproc cp WHERE cp.status IN (1,4,5,7) AND cp.DateCp='2020-08-04' */
		/* ClaimProc Status = received, supplemental, CapClaim or CapComplete*/
		$query = $opendentaldb->query("SELECT SUM(-cp.Writeoff) AS totalw FROM claimproc cp ".
			" WHERE cp.status IN (1,4,5,7) AND cp.DateCp BETWEEN '$startDate' AND '$endDate'");
		$wRow = $query->row();
		$writeoffs = '$'.number_format($wRow->totalw, 2);

		$html .= "<div id='totalWriteOffs' class='row'><span class='provname'>Writeoffs:</span><span class='accounting'>$writeoffs</span></div>";

		// Calculate Net Production
		$netProd = $grandTotal + $aRow->totaladj + $wRow->totalw;
		$prettyNP = '$'.number_format($netProd, 2);

		$html .= "<div id='netProduction' class='row'><span class='provname'>Net Production:</span><span class='accounting'>$prettyNP</span></div>";

		$html .= "<div id='collections'><h3>Collections</h3></div>";
		
		/* SELECT SUM(cp.InsPayAmt) FROM claimpayment cy INNER JOIN claimproc cp ON cp.ClaimPaymentNum=cy.ClaimPaymentNum
			 /*this join isn't needed for the date or amount, it is included to make sure there was a check made*/
		/* WHERE cp.ProvNum=44 AND (cy.CheckDate BETWEEN @FromDate AND @ToDate)
		*/
		$assocID = 216;
		// Next get Associate's Patient collections 
		$query = $opendentaldb->query("SELECT SUM(ps.SplitAmt) AS pattotal FROM paysplit ps ".
				" WHERE ps.ProvNum=$assocID AND ps.DatePay BETWEEN '$startDate' AND '$endDate'");
		$jfpRow = $query->row();
		$jfPat = '$'.number_format($jfpRow->pattotal, 2);

		$html .= "<div id='jerrypat' class='row'><span class='provname'>Assoc Pt Pay:</span><span class='accounting'>$jfPat</span></div>";

		// Next get Jerry's Ins collections 
		$query = $opendentaldb->query("SELECT SUM(cp.InsPayAmt) AS instotal FROM claimpayment cy ".
				" INNER JOIN claimproc cp ON cp.ClaimPaymentNum=cy.ClaimPaymentNum ".
			 	" WHERE cp.ProvNum=$assocID AND cy.CheckDate  BETWEEN '$startDate' AND '$endDate'");
		$jfiRow = $query->row();
		$jfIns = '$'.number_format($jfiRow->instotal, 2);
		$html .= "<div id='jerryins' class='row'><span class='provname'>Assoc Ins:</span><span class='accounting'>$jfIns</span></div>";

		// Calc Jerry total
		$jfTotal = $jfpRow->pattotal + $jfiRow->instotal;
		$prettyjfTotal = '$'.number_format( $jfTotal, 2 );
		$html .= "<div id='jerrytotal' class='row'><span class='provname'>Assoc Total Collections:</span><span class='accounting'>$prettyjfTotal</span></div>";

		// Office Patient payments:
		$query = $opendentaldb->query("SELECT SUM(ps.SplitAmt) AS pattotal FROM paysplit ps ".
				" WHERE ps.DatePay BETWEEN '$startDate' AND '$endDate'");
		$opRow = $query->row();
		$oPat = '$'.number_format($opRow->pattotal, 2);

		// Calc Jed's patient payments by subtracting Jerry's from office
		$jkPat = $opRow->pattotal - $jfpRow->pattotal;
		$prettyJKpat = '$'.number_format( $jkPat, 2);
		$html .= "<div id='jkpat' class='row'><span class='provname'>Jed's Pt Pay:</span><span class='accounting'>$prettyJKpat</span></div>";

		

		// Office Insurance payments:
			/* SELECT SUM(cp.InsPayAmt) FROM claimpayment cy
			INNER JOIN claimproc cp ON cp.ClaimPaymentNum=cy.ClaimPaymentNum
 			WHERE cp.ProvNum=pr.ProvNum AND (cy.CheckDate BETWEEN @FromDate AND @ToDate)
			*/
		$query = $opendentaldb->query("SELECT SUM(cp.InsPayAmt) AS instotal FROM claimpayment cy ".
				" INNER JOIN claimproc cp ON cp.ClaimPaymentNum=cy.ClaimPaymentNum ".
			 	" WHERE cy.CheckDate  BETWEEN '$startDate' AND '$endDate'");
		$oiRow = $query->row();
		$oIns = '$'.number_format($oiRow->instotal, 2);
		// Calc Jed's INS payments by subtracting Jerry's from office
		$jkIns = $oiRow->instotal - $jfiRow->instotal;
		$prettyJKins = '$'.number_format( $jkIns, 2);
		$html .= "<div id='jkins' class='row'><span class='provname'>Jed's Ins Pay:</span><span class='accounting'>$prettyJKins</span></div>";
		// Calc Jed total
		$jkTotal = $jkPat + $jkIns;
		$prettyjkTotal = '$'.number_format( $jkTotal, 2 );
		$html .= "<div id='jedtotal' class='row'><span class='provname'>Jed Total Collections:</span><span class='accounting'>$prettyjkTotal</span></div>";

		// Calc Office total
		$oTotal = $opRow->pattotal + $oiRow->instotal;
		$oInsPercent = number_format($oiRow->instotal / $oTotal * 100, 2) . "%";
		$oPatPercent = number_format($opRow->pattotal / $oTotal * 100, 2) . "%";
		$html .= "<div id='opat' class='row'><span class='provname'>Office Pt Pay:</span><span class='accounting'>$oPat</span>".
			"<span class='percent'>($oPatPercent)</span></div>";
		$html .= "<div id='officeins' class='row'><span class='provname'>Office Ins:</span><span class='accounting'>$oIns</span>".
			"<span class='percent'>($oInsPercent)</span></div>";
		$prettyoTotal = '$'.number_format( $oTotal, 2 );
		$html .= "<div id='ototal' class='row'><span class='provname'>Office Total Coll:</span><span class='accounting'>$prettyoTotal</span></div>";

		$html .= "<div id='misc'>\n";
		
		// Get total patient visits
		$query = $opendentaldb->query("SELECT COUNT(apt.AptNum) AS 'Total' ".
			" FROM patient p ".
			" INNER JOIN appointment apt ON apt.PatNum=p.PatNum ".
				" AND apt.AptStatus=2 ".
				" AND apt.AptDateTime BETWEEN '$startDate' AND '$endDate'+INTERVAL 1 DAY" );
			/*" GROUP BY DATE_FORMAT(DATE(apt.AptDateTime),'%M %Y')
			" ORDER BY YEAR(DATE(apt.AptDateTime)),MONTH(DATE(apt.AptDateTime)) " ); */
		$pvRow = $query->row();
		$pvTotal = $pvRow->Total;
		$html .= "   <div id='patientvisits'><span class='provname'>Total Pt Visits:</span><span class='accounting'>$pvTotal</span></div>";

		// Get New patient count
		/* SELECT DATE_FORMAT(DATE(firstApt.FirstVisit),'%M %Y') AS 'MONTH',
			COUNT(p.PatNum) AS 'Value'
			FROM patient p
			INNER JOIN (
				SELECT Q.PatNum,Q.FirstVisit AS FirstVisit
				FROM(
					SELECT apt.PatNum,MIN(apt.AptDateTime) AS FirstVisit
					FROM appointment apt
					WHERE apt.AptStatus IN(1,2,4)
					GROUP BY apt.PatNum
				)Q
				WHERE DATE(Q.FirstVisit) BETWEEN '2020-08-01' AND '2020-08-31'
			) firstApt ON firstApt.PatNum=p.PatNum
			GROUP BY DATE_FORMAT(DATE(firstApt.FirstVisit),'%M %Y')
			ORDER BY YEAR(DATE(firstApt.FirstVisit)),MONTH(DATE(firstApt.FirstVisit)) */
		$query = $opendentaldb->query("SELECT COUNT(p.PatNum) AS 'Total' FROM patient p INNER JOIN ( ".
			"	SELECT Q.PatNum,Q.FirstVisit AS FirstVisit ".
			"	FROM( ".
			" 		SELECT apt.PatNum,MIN(apt.AptDateTime) AS FirstVisit ".
			"		FROM appointment apt ".
			"		WHERE apt.AptStatus IN(1,2,4) ".
			"		GROUP BY apt.PatNum ".
			"	)Q ".
			"	WHERE DATE(Q.FirstVisit) BETWEEN '$startDate' AND '$endDate' ".
			" ) firstApt ON firstApt.PatNum=p.PatNum ".
			" GROUP BY DATE_FORMAT(DATE(firstApt.FirstVisit),'%M %Y') ".
			" ORDER BY YEAR(DATE(firstApt.FirstVisit)),MONTH(DATE(firstApt.FirstVisit))" );
		$npRow = $query->row();
		$npTotal = $npRow->Total;
		$html .= "   <div id='newpatients'><span class='provname'>New Pts:</span><span class='accounting'>$npTotal</span></div>";

		// Accounts Receivable
		$html .= "<div id='ar'><h3>Hours/Days Worked</h3></div>";
		// Calculate Jed's total days worked
 		// Doctor Hours worked
		$query = $opendentaldb->query("SELECT SUM(CHAR_LENGTH(REPLACE(ap.Pattern, '/',''))*5/60) as Total".
			" FROM provider pr LEFT JOIN appointment ap ON pr.ProvNum=ap.ProvNum AND ap.AptStatus=2 ".
			" AND (DATE(ap.AptDateTime) BETWEEN '$startDate' and '$endDate') WHERE pr.ProvNum=43 ");
		$jedHoursRow = $query->row();
		$jedHoursTotal = number_format($jedHoursRow->Total,2);
		// Hours do not seem to be accurate so leaving that off for now
		//$html .= "   <div id='jedhours'><span class='provname'>Jed Hours:</span><span class='accounting'>$jedHoursTotal</span></div>";

		// Calc Jed Days worked
		$query = $opendentaldb->query("SELECT COUNT(DISTINCT pl.ProcDate) AS Total FROM procedurelog pl ".
			 " LEFT JOIN provider pr ON pl.ProvNum=pr.ProvNum ".
			 " WHERE pl.ProcDate BETWEEN '$startDate' and '$endDate' ".
             " AND pl.ProvNum=43 ".
             " AND pl.ProcFee*(pl.UnitQty+pl.BaseUnits) > 0 ".
			 " AND pl.ProcStatus = 2");
		$jedDaysRow = $query->row();
		$jedDaysTotal = $jedDaysRow->Total;
		$html .= "   <div id='jeddays'><span class='provname'>Jed Days:</span><span class='accounting'>$jedDaysTotal</span></div>";

		// Calculate Jerry's total hours worked
		$query = $opendentaldb->query("SELECT SUM(CHAR_LENGTH(REPLACE(ap.Pattern, '/',''))*5/60) as Total".
			" FROM provider pr LEFT JOIN appointment ap ON pr.ProvNum=ap.ProvNum AND ap.AptStatus=2 ".
			" AND (DATE(ap.AptDateTime) BETWEEN '$startDate' and '$endDate') WHERE pr.ProvNum=44 ");
		$jerryHoursRow = $query->row();
		$jerryHoursTotal = number_format($jerryHoursRow->Total,2);
		// Hours do not seem to be accurate so leaving that off for now
		//$html .= "   <div id='jerryhours'><span class='provname'>Jerry Hours:</span><span class='accounting'>$jerryHoursTotal</span></div>";

		// Calc Associate Days worked
		$query = $opendentaldb->query("SELECT COUNT(DISTINCT pl.ProcDate) AS Total FROM procedurelog pl ".
			 " LEFT JOIN provider pr ON pl.ProvNum=pr.ProvNum ".
			 " WHERE pl.ProcDate BETWEEN '$startDate' and '$endDate' ".
             " AND pl.ProvNum=$assocID ".
             " AND pl.ProcFee*(pl.UnitQty+pl.BaseUnits) > 0 ".
			 " AND pl.ProcStatus = 2");
		$jerryDaysRow = $query->row();
		$jerryDaysTotal = $jerryDaysRow->Total;
		$html .= "   <div id='jerrydays'><span class='provname'>Associate Days:</span><span class='accounting'>$jerryDaysTotal</span></div>";

		// List Jed's crowns and Thanh's crowns (D2740, D6058 (implant crowns), D6740 (bridge retainer) + D6245 (pontics), D6548 (bridge retainer)
		$query = $opendentaldb->query("SELECT COUNT(*) AS 'Total' ".
			" FROM procedurelog pl INNER JOIN procedurecode pc ON pc.CodeNum=pl.CodeNum".
			" WHERE pl.ProvNum=43 AND pl.ProcDate BETWEEN '$startDate' AND '$endDate'".
			" AND pl.ProcStatus = 2 AND pc.ProcCode IN ('D2740', 'D6058', 'D6740', 'D6245', 'D6548')"  );
		$crownRow = $query->row();
		$grandTotal = 0;
		$crownTotal = $crownRow->Total;
		$grandTotal += $crownTotal;
		$html .= "<hr />   <div id='crowns'><span class='provname'>Jed's Total Crown Units:</span><span class='accounting'>$crownTotal</span></div>";

		$query = $opendentaldb->query("SELECT COUNT(*) AS 'Total' ".
			" FROM procedurelog pl INNER JOIN procedurecode pc ON pc.CodeNum=pl.CodeNum".
			" WHERE pl.ProvNum=$assocID AND pl.ProcDate BETWEEN '$startDate' AND '$endDate'".
			" AND pl.ProcStatus = 2 AND pc.ProcCode IN ('D2740', 'D6058', 'D6740', 'D6245', 'D6548')"  );
		$crownRow = $query->row();
		$crownTotal = $crownRow->Total;
		$grandTotal += $crownTotal;
		$html .= "<div id='tcrowns'><span class='provname'>Thanh's Total Crown Units:</span><span class='accounting'>$crownTotal</span></div>";
		$html .= "<div style='font-weight:bold'><span class='provname'>Grand Total Crown Units:</span><span class='accounting'>$grandTotal</span></div>";

		
		$html .= "</div>";
		$data = array(
			'html' => $html,
			//'stats' => $stats,
			'daysheetCols' => $daysheetCols,
			'month' => $month
		);
		$subview = $this->load->view('monthly', $data, TRUE);
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
