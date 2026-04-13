<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<?= $html ?>
<!-- Verify sub-template: Loop through left and right columns -->
<div id="leftcol">
	<?php //$columns = array_keys((array) $daysheet[0]);
	foreach($daysheet as $row):?>
			<div class="daysheet">
				<?php foreach ($daysheetCols as $col):?>
					<span class="col"><!-- <?= $col ?>: --><?= $row->$col ?></span>
				<?php endforeach ?>
			</div>
	<?php endforeach ?>
</div>
<div id="rightcol">
	<?php //$columns = array_keys((array)$schedule[0]);
	foreach($schedule as $row):?>
			<div class="schedule">
				<?php foreach ($scheduleCols as $col):?>
					<span class="col"><!-- <?= $col ?>: --><?= $row->$col ?></span>
				<?php endforeach ?>
			</div>
	<?php endforeach ?>
</div>