<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div id="monthnav">
	<button id='prevmonth'><img src='/images/icons/page-prev-large.png' alt='Prev' /></button>
	<button id='curmonth'>Current Month</button>
	<button id='nextmonth'><img src='/images/icons/page-next-large.png' alt='Next' /></button>
	<input id='date' type="hidden" value='<?= $month ?>' />
</div>
<?= $html ?>
<!-- Monthly Report sub-template: Loop through data -->
<div id="statsdata">
	<?php //$columns = array_keys((array) $stats[0]);
	/*foreach($stats as $row):?>
			<div class="daysheet">
				<?php foreach ($daysheetCols as $col):?>
					<span class="col"><!-- <?= $col ?>: --><?= $row->$col ?></span>
				<?php endforeach ?>
			</div>
	<?php endforeach */ ?>
</div>
