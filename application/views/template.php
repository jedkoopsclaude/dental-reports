<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Summary</title>
	<link rel="shortcut icon" href="/images/faist-favicon.png" />
	<link rel='stylesheet' href='/css/ci.css' type='text/css' media='all' />
	<link rel='stylesheet' href='/css/roundtabs.css' type='text/css' media='all' />
	<script type="text/javascript" src="/js/jquery-1.4.2.min.js"></script>
	<!-- script type='text/javascript' src='/js/jquery.address-1.5.min.js'></script -->
	<script src="/js/local.js"></script>
	<script>
		$(function() {
			$("li").click(function(e) {
			  //e.preventDefault();
			  $("li").removeClass("selected");
			  $(this).addClass("selected");
			});
		});
	</script>
</head>
<body>
	<div id="sidebar"> 
		<div id="monthly">
			<a href="/monthly">
				<img src="/images/icons/october.png" border="0" alt="Monthly Reports" title="Monthly Reports" />
			</a>
		</div>
		<div id="logo"> 
			<a href="/perf">
				<img src="/images/odometer.png" border="0" alt="Practice Dashboard" title="Practice Dashboard" />
			</a>
		</div>
		<div id="verify">
			<a href="/verify">
				<img src="/images/icons/doc-icon.png" border="0" alt="Verify Daysheets" title="Verify Daysheets" />
			</a>
		</div>
		
	</div>
	<div id="menu"><!-- Menu -->
		<?= $menu ?>
	</div>

	<div id="container">

		<div id="body">
			
			<!-- Body -->
			<?= $html ?>
		</div>

		<p class="footer"><?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
	</div>

</body>
</html>