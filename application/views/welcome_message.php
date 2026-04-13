<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Practice Dashboard: Faist & Koops Family Dentistry</title>
	<link rel="shortcut icon" href="/images/faist-favicon.png" />
	<link rel='stylesheet' href='/css/ci.css' type='text/css' media='all' />
</head>
<body>

<div id="container">
	<h1>Practice Dashboard</h1>

	<div id="body">
		<p>Menu: <?= $menu ?></p>
		<br />
		<h3>Last Day's Payments:</h3>
		<?= $html ?>
	</div>

	<p class="footer"><?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
</div>

</body>
</html>