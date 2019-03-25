<?php

if (file_exists(BASEPATH . 'vce-config.php')) {
	// check if vce-config already was loaded
	if (!defined('SITE_KEY')) {
		// configuration file 
		require_once(BASEPATH . 'vce-config.php');
	}
} else {
	// run installer
	if (file_exists(BASEPATH . 'vce-installer.php')) {
		header('Location: vce-installer.php');
	} else {
		echo "vce-installer.php not found";
	}
	exit;
}

// error reporting
if (defined('VCE_DEBUG')) {
	ini_set('error_reporting', (VCE_DEBUG === false) ? 0 : -1);
}

// require vce
require_once(BASEPATH . 'vce-application/class.vce.php');
$vce = new VCE();

// require database class
require_once(BASEPATH . 'vce-application/class.db.php');
$db = new DB($vce);

// create contents object
require_once(BASEPATH . 'vce-application/class.content.php');
$content = new Content($vce);

// load component class
require_once(BASEPATH . 'vce-application/class.component.php');
// this class does not get instantiated here

// create site object
require_once(BASEPATH . 'vce-application/class.site.php');
$site = new Site($vce);

// create user object
require_once(BASEPATH . 'vce-application/class.user.php');
$user = new User($vce);

// create page object
require_once(BASEPATH . 'vce-application/class.page.php');
$page = new Page($vce);

// unset($page->site,$page->user,$page->content);
// $vce->dump($vce->user, 'efefef');

// output vce errors before theme page outputs
if (isset($vce->errors)) {
	$vce->display_errors($vce);
}

// output page using theme template
require_once($vce->template_file_path);
