<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div id="yearnav">
	<button id='prevyear'><img src='/images/icons/page-prev-large.png' alt='Prev' /></button>
	<button id='curyear'>Current Year</button>
	<button id='nextyear'><img src='/images/icons/page-next-large.png' alt='Next' /></button>
	<input id='date' type="hidden" value='<?= $year ?>' />
</div>
<?= $html ?>
<!-- Annual Report sub-template -->
<div id="statsdata">
	
</div>
