<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div id="monthnav">
	<button id='prevmonth'><img src='/images/icons/page-prev-large.png' alt='Prev' /></button>
	<button id='curmonth'>Current Month</button>
	<button id='nextmonth'><img src='/images/icons/page-next-large.png' alt='Next' /></button>
	<input id='date' type="hidden" value='<?= $month ?>' />
</div>
<div id="labfees">
<?= $html ?>
</div>
