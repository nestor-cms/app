<?php
/*
Theme Name: Default
*/

function footer() {
	//	application page-build-timer end
	// 	global $starttime;
	// 	$mtime = microtime(); 
	// 	$mtime = explode(" ",$mtime); 
	// 	$mtime = $mtime[1] + $mtime[0]; 
	// 	$endtime = $mtime; 
	// 	$totaltime = ($endtime - $starttime);
	// 	echo '<div class="copy">&copy; ' . date("Y") . ' University of Washington. All rights reserved</div>';
	// 	echo round($totaltime,3) . " @ " . round(memory_get_usage()/1024,2);
}

global $vce;

//add javascript for theme specific things
$vce->site->add_script($vce->site->theme_path . '/js/scripts.js','jquery');

//add stylesheet
$vce->site->add_style($vce->site->theme_path . '/css/style.css', 'ccce-theme-style');
