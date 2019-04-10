<?php

/**
 * vce_installer: guides user through VCE installation process
 * the function "define_sql()" at the end of this file should be the contents of the latest mysql dump of the site, run through a parser to
 * remove comments. If the database defined is `vce` and the prefix is vce_, these will be replaced by this script.
 */
 
/**
 * todo: have installer report if vce-config.php is not writable by PHP and also if htaccess was created.
 */
 
// Report  errors [set to 0 for live site]
ini_set('error_reporting', E_ALL);

// Define BASEPATH as this file's path
define('BASEPATH', dirname(__FILE__) . '/');

// replaces backslashes with forward slashes to make windows paths the same as linux
$replacement = str_replace("\\", "/", BASEPATH);

// Define DOCROOT as this file's directory
define('DOCROOT', $_SERVER['DOCUMENT_ROOT'] . '/');

// Define DOCPATH as the path from the DOCROOT to the file
define('DOCPATH', '/'.str_replace(DOCROOT, '', $replacement));

// This is the variable which will contain all the HTML to display
$GLOBALS['content'] = '';

// require vce
require_once(BASEPATH . 'vce-application/class.vce.php');
$vce = new VCE();

//begin session
if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['installer_page']) && $_SESSION['installer_page'] = 0) {
	session_destroy();
}

if (session_status() == PHP_SESSION_NONE) {
//     start_session();
    session_start();
    //record when the session started
    if (!isset($_SESSION['started'])) {
   		 $_SESSION['started'] = time();
	}
}


if (!isset($_SESSION['installer_page']) || $_SESSION['installer_page'] < 0) {
	$_SESSION['installer_page'] = 0;
}

//for development, sets the page you are on
if (isset($_GET['installer_page'])) {
	$_SESSION['installer_page'] = $_GET['installer_page'];
	$_SESSION['carryon'] = 'continue';
}


//
if (!isset($_SESSION['carryon']) ) {
	$_SESSION['carryon'] = 'continue';
}

//reset to first page if more than an hour has passed
if (isset($_SESSION['started']) && $_SESSION['started'] + 3600  < time()) {
	session_destroy();
	start_session();
	$_SESSION['installer_page'] = 0;
}

//find page to display
set_page();


//write header and pre-content
$inst_css = installer_css();
$inst_js = form_validation_js();

// Page Header and pre-content
$GLOBALS['content'] .= <<<EOF
<!DOCTYPE html>
<html lang="en">
<meta charset="utf-8">
<title>VCE Installer</title>
<link rel="stylesheet" type="text/css" href="vce-application/css/vce.css">
$inst_css
$inst_js
</head>
<body>
<div id="wrapper">
<div id="content">
<div id="header" >
<div id="void" class="inner"><h1>VCE Installer</h1></div>
</div>

<br>
<div class="inner">
EOF;


/**
 * Step-by step installer and content.
 * This is the road-map and output of the installer
 */
 

 
//Welcome to the installer
if (!isset($_SESSION['installer_page']) || $_SESSION['installer_page'] < 1) {
 	$step_title = 'Welcome to the VCE installer.';
 	$step_description = 'This installer will walk you through the few steps necessary to configure your VCE site.</br>If you are ready to begin, please click on &quot;continue&quot;';
	//Main content
	title_description($step_title, $step_description);
	$_SESSION['carryon'] = 'continue';
}


//Do extension check, report server configuration, back/continue
if ($_SESSION['installer_page'] == 1) {
 	$step_title = 'Server Compatibility Check';
 	$step_description = 'The installer is now checking the server version and installed modules';
	//Main content
	title_description($step_title, $step_description);
	//check to see if these modules are active on the server
	// removing the following from the extention_check because they are not needed
	// 'pdo', 
	// 'pdo_mysql', 
	$_SESSION['carryon'] = extension_check(array( 
		'curl',
		'dom', 
		'gd', 
		'hash',
		'iconv',
		'mcrypt',
		'pcre',
		'simplexml'
		)
	);
	//edit .htaccess file
	check_htaccess_file();
	edit_htaccess_file();
	//edit vce-config.php
	edit_vce_config();
	
}


//Enter database information
if ($_SESSION['installer_page'] == 2) {
 	$step_title = 'Enter Database Information';
 	$step_description = 'You need to create a database for VCE to use. When you have the database name, URL, and database admin credentials, enter them here.';
	//Main content
	title_description($step_title, $step_description);
	$_SESSION['carryon'] = check_database();
	 $this_file = '';
	 $dbhost = !empty($_POST['dbhost']) ? $_POST['dbhost'] : 'localhost';
	 $dbname = !empty($_POST['dbname']) ? $_POST['dbname'] : '';
	 $dbprefix = !empty($_POST['dbprefix']) ? $_POST['dbprefix'] : 'vce_';
	 $dbuser= !empty($_POST['dbuser']) ? $_POST['dbuser'] : '';
	 $dbpassword= !empty($_POST['dbpassword']) ? $_POST['dbpassword'] : '';
	 $dbport= !empty($_POST['dbport']) ? $_POST['dbport'] : '3306';

$GLOBALS['content'] .= <<<EOF
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form class="installer-form" action="$this_file" method="post" autocomplete="off">
<input type="hidden" name="pagecheck" value="check">
<label>
<input type="text" name="dbname" tag="required" autocomplete="off" value="$dbname">
<div class="label-text">
<div class="label-message">Database Name</div>
<div class="label-error">Enter Database Name</div>
</div>
</label>
<label>
<input type="text" name="dbuser" tag="required" autocomplete="off" value="$dbuser">
<div class="label-text">
<div class="label-message">Database User</div>
<div class="label-error">Enter Database User</div>
</div>
</label>
<label>
<input type="text" name="dbpassword" tag="required" autocomplete="off" value="$dbpassword">
<div class="label-text">
<div class="label-message">Database User Password</div>
<div class="label-error">Enter Database User Password</div>
</div>
</label>
<div class="clickbar-container add-container">
<div class="clickbar-content">
<label>
<input type="text" name="dbprefix" tag="required" autocomplete="off" value="$dbprefix">
<div class="label-text">
<div class="label-message">Database Prefix (&quot;vce_&quot; is the default)</div>
<div class="label-error">Enter Database Prefix</div>
</div>
</label>
<label>
<input type="text" name="dbhost" tag="required" autocomplete="off" value="$dbhost">
<div class="label-text">
<div class="label-message">Database Host</div>
<div class="label-error">Enter Database Host</div>
</div>
</label>
<label>
<input type="text" name="dbport" tag="required" autocomplete="off" value="$dbport">
<div class="label-text">
<div class="label-message">Database Port</div>
<div class="label-error">Enter Database Port</div>
</div>
</label>
</div>
<div class="clickbar-title clickbar-closed"><span>Advanced Options</span></div>
</div>
<input type="submit" value="Connect to Database">
</form>
</div>
<div class="clickbar-title"><span>Configure Database Connection</span></div>
</div>
EOF;

	
}


if ($_SESSION['installer_page'] == 3) {
	// Now that we have a database connection, link into the site classes for the next steps
	// configuration file 
	include_once(BASEPATH . 'vce-config.php');
	
	// require vce
	require_once(BASEPATH . 'vce-application/class.vce.php');
	$vce = new VCE();
	
	// create DB object
	include_once(BASEPATH . 'vce-application/class.db.php');
	$db = new DB($vce);
	//run .sql file

	$prefix = check_config_value('TABLE_PREFIX');
	$database = check_config_value('DB_NAME');

// 	if (file_exists(BASEPATH . 'database.sql') && !empty($prefix) && !empty($database)) {
// 		$database_sql = file_get_contents(BASEPATH . 'database.sql');
// 		$newfile = fopen("databaseNEW.sql", "w") or die("Unable to open file!");
// 		$newfile_content =str_replace('vce_', $prefix, $database_sql);
// 		$newfile_content2 =str_replace('`vce`', $database, $newfile_content);
// 		fwrite($newfile, $newfile_content2);
// 		fclose($newfile);
// 	}
// 	$database_sql = file_get_contents(BASEPATH . 'databaseNEW.sql');
	
	$database_sql = define_sql();
	$database_sql = str_replace('vce_', $prefix, $database_sql);
	$database_sql = str_replace('`vce`', $database, $database_sql);
	$database_sql = explode(';', $database_sql );
	
	try {
        $dbconnection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? DB_PORT : '3306');
            mysqli_report(MYSQLI_REPORT_STRICT);
            foreach($database_sql as $query) {
//             	echo $query;
//             	echo '<br>';
				if (!empty($query)) {
					$dbconnection->query($query);
				}
			}
			//change site_url in the site meta table
			$site_url = 'http://'.$_SERVER['HTTP_HOST'].rtrim(DOCPATH, "/");
			$query = 'UPDATE '.$prefix.'site_meta SET meta_value = "'.$site_url.'" WHERE meta_key = "site_url" ';	
			$db->query($query);

        } catch (Exception $e) {
        	echo  $e->getMessage();
            die('Database connection failed');
        }
        
      //create site key
    create_site_key();  
    $GLOBALS['site_key'] = check_config_value('SITE_KEY');;
}


//Enter Site Admin information
if ($_SESSION['installer_page'] == 3) {

	$this_file = '';
	//if all fields are present, create admin
	if (isset($_POST['username']) && isset($_POST['pwd1']) && isset($_POST['first_name']) && isset($_POST['last_name'])) {
		$create_admin = site_admin($_POST['username'], $_POST['pwd1'],$_POST['first_name'], $_POST['last_name'], $db);
	}
	if (isset($create_admin['success'])) {
		$_SESSION['site_admin_name'] = $create_admin['site_admin_name'];
		$step_title = 'Site Administrator';
 		$step_description = $create_admin['success'];
		//Main content
		title_description($step_title, $step_description);
		$_SESSION['carryon'] = 'continue';
		// go to next step
		$_SESSION['installer_page'] = 4;
		
		
	}else{

	 	$step_title = 'Site Administrator';
	 	if (isset($create_admin['failure'])) {
			$GLOBALS['content'] .= '<span style="color:red;">'.$create_admin['failure'].'</span">';
		}
 		$step_description = 'Choose the name and password for the site administrator.';
		//Main content
		title_description($step_title, $step_description);
		$_SESSION['carryon'] = 'wait';


$GLOBALS['content'] .= <<<EOF
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form class="installer-form" action="$this_file" method="post" autocomplete="off">
<input type="hidden" name="pagecheck" value="check">
<label>
<input type="text" name="first_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Admin First Name</div>
<div class="label-error">Enter Site Admin First Name</div>
</div>
</label>
<label>
<input type="text" name="last_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Admin Last Name</div>
<div class="label-error">Enter Site Admin Last Name</div>
</div>
</label>
<label>
<input type="email" name="username" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Admin Email</div>
<div class="label-error">Enter A Valid Email Address</div>
</div>
</label>
<label>
<input type="password" name="pwd1" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Password (Use 6 or more characters including a capital letter and a number.)</div>
<div class="label-error">Enter Password</div>
</div>
</label>
<label>
<input type="password" name="pwd2" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Password Confirmation</div>
<div class="label-error">Enter Password Confirmation</div>
</div>
</label>
<input type="submit" value="Create Site Admin">
</form>
</div>
<div class="clickbar-title"><span>Site Admin</span></div>
</div>
EOF;
	}
}




//Sitekey
//
// site_key($superadmin['email']);
if ($_SESSION['installer_page'] == 4) {
 	$step_title = 'Site Key';
 	$step_description = 'This is your site key. It is written into the vce-config file, but it is also important that you write this down and store it off-site.<br>
 	This is the key which is used to encrypt all user data in the system. If it becomes corrupted, you will lose all the user data which is stored in the database.';
	//Main content
	title_description($step_title, $step_description);
	$site_key = check_config_value('SITE_KEY');
	$GLOBALS['content'] .= "Hello ".$_SESSION['site_admin_name'].", your new site key has been created<br>
	It is:<br>
	<span style='color:green;'>".$site_key."</span><br>
	Please keep this in your records.";
	
	//send email
	$msg = "Hello, \n
	This is the VCE SITE_KEY for your site:\n
	".$site_key."\n
	It is stored in the vce-config.php file at the root of your installation.\n
	Please keep it in your records to use in the event of a corruption of that configuration file.\n
	Thank you!";
	mail($_SESSION['site_admin_name'],'VCE Installation',$msg);
	$_SESSION['carryon'] = 'continue';


}

//Personalize the site
if ($_SESSION['installer_page'] == 5) {
	// configuration file 
	include_once(BASEPATH . 'vce-config.php');
	
	// require vce
	require_once(BASEPATH . 'vce-application/class.vce.php');
	$vce = new VCE();
	
	// create DB object
	include_once(BASEPATH . 'vce-application/class.db.php');
	$db = new DB($vce);
	$step_title = 'Personalize Your Installation';
 	$step_description = 'Enter the site\'s name and other specific information.<br>';
	//Main content
	title_description($step_title, $step_description);
	$_SESSION['carryon'] = 'wait';


	//if all fields are present, personalize site
	if (isset($_POST['site_name']) && isset($_POST['site_description'])) {
		$_SESSION['carryon'] = personalize_site($_POST['site_name'], $_POST['site_description'], $db);
		$GLOBALS['content'] .= '<span style="color:green;">Currently, your site name is &quot;'.$_POST['site_name'].'&quot; and your site description is &quot;'.$_POST['site_description'].'&quot;.</span">';
	}
	
	
	$this_file = '';
	$site_name = !empty($_POST['site_name']) ? $_POST['site_name'] : '';
	$site_description = !empty($_POST['site_description']) ? $_POST['site_description'] : '';

$GLOBALS['content'] .= <<<EOF
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form class="installer-form" tag="required" action="$this_file" method="post" autocomplete="off">
<input type="hidden" name="pagecheck" value="check">
<label>
<input type="text" name="site_name" tag="required" autocomplete="off" value="$site_name">
<div class="label-text">
<div class="label-message">Site Name</div>
<div class="label-error">Enter Site Name</div>
</div>
</label>
<label>
<input type="text" name="site_description" tag="required" autocomplete="off" value="$site_description">
<div class="label-text">
<div class="label-message">Site Description</div>
<div class="label-error">Enter Site Description</div>
</div>
</label>
<input type="submit" value="Submit">
</form>
</div>
<div class="clickbar-title"><span>Site Personalization</span></div>
</div>
EOF;

	
}


//Installation Complete
if ($_SESSION['installer_page'] == 6) {
 	$step_title = 'Installation Complete';
 	$step_description = 'Your VCE installation is complete.<br> 
 	Your site admin user name is '.$_SESSION['site_admin'].' and your password is what you entered when you created the site admin.<br>
 	Please press &quot;continue&quot; to log in.';
	//Main content
	title_description($step_title, $step_description);
}


//Redirect to Admin Home Page at the end of the installation
if ($_SESSION['installer_page'] == 7) {
	session_unset();
	session_destroy();
	$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$url = str_replace(basename(__FILE__), '', $url);
	unlink(__FILE__);
	header('Location: '.$url);
}

/**
 * END: Step-by step installer and content.
 */

$copy = '&copy; ' . date("Y");

//write post-content and footer
$GLOBALS['content'] .= <<<EOF
</div>
</div>
<footer id="footer">
<div class="inner" >
<div class="copy">$copy University of Washington. All rights reserved</div>
</div>
</footer>
</div></div>
</body>
</html>
EOF;

//navigation buttons 
back_continue($_SESSION['carryon']);
 
echo $GLOBALS['content'];



/**
 * FUNCTIONS: here are all the functions called in the step-by-step
 */

	/**
	 * session start 
	 */
	function start_session() {

		// SESSION HIJACKING PREVENTION

		// set hash algorithm
		ini_set('session.hash_function', 'sha512');
	
		// send hash
		ini_set('session.hash_bits_per_character', 5);
   
		// set additional entropy
		ini_set('session.entropy_file', '/dev/urandom');
	
   		// set additional entropy	
		ini_set('session.entropy_length', 256);
	
		// prevents session module to use uninitialized session ID
		ini_set('session.use_strict_mode', true);
   
		// SESSION FIXATION PREVENTION
   
		// do not include the identifier in the URL, and not to read the URL for identifiers.
		ini_set('session.use_trans_sid', 0);
	
 		// tells browsers not to store cookie to permanent storage
 		ini_set('session.cookie_lifetime', 0);
 
		// force the session to only use cookies, not URL variables.
		ini_set('session.use_only_cookies', true);
   
		// make sure the session cookie is not accessible via javascript.
		ini_set('session.cookie_httponly', true);
   
		// set to true if using https   
		ini_set('session.cookie_secure', false);

		// chage session name
		session_name('_s');
		
		// set the cache expire to 30 minutes
		session_cache_expire(5);
	
		// start the session
		session_start();
	}
	
function set_page() {
	if (isset($_POST['pagecheck'])) {
		return;
	}

	if (isset($_POST['direction'])) {
		if ($_POST['direction'] == 'back') {
			if (isset($_SESSION['installer_page'])) {
				$_SESSION['installer_page']--;
			}else{
				$_SESSION['installer_page'] = 0;
			}
		}elseif ($_POST['direction'] == 'continue') {
			if (isset($_SESSION['installer_page'])) {
				$_SESSION['installer_page']++;
			}else{
				$_SESSION['installer_page'] = 0;
			}
		}
	}
}

/**
 * Creates the "Back" and "Continue" buttons for each step of the installation
 * @param 
 * @return HTML for the form buttons
 */
function back_continue($carryon) {
 $this_file = '';
$continue_message = ($carryon == 'wait' ? 'Submit Form First' : 'Continue');
$continue_button = <<<EOF
<div class="inner">
<div class="clickbar-container">
<div class="clickbar-content style="display: block;">
<!-- <form class="inline-form asynchronous-form" method="post" action="$this_file">
<input type="hidden" name="direction" value="back">
<input type="submit" value="Back (temporary)">
</form> -->
<form onsubmit="return checkContinueForm(this);" class="inline-form asynchronous-form" method="post" action="$this_file">
<input type="hidden" name="direction" value="$carryon">
<input type="submit" value="$continue_message">
</form>
</div>
</div>
EOF;
$GLOBALS['content'] = str_replace('<div class="inner">', $continue_button, $GLOBALS['content']);
}
 

/**
 * Creates the title and description for each step of the installation
 * @param string $title
 * @param string $description
 * @return HTML for the title and description
 */ 
function title_description($step_title, $step_description){
	$GLOBALS['content'] .= '<h2>'.$step_title.'</h2>';
	$GLOBALS['content'] .= '<p>'.$step_description.'</p>';
}


/**
 * Prepares vce-config.php for the installation
 * @param string $title
 * @param string $description
 * @return HTML for the title and description
 */ 
function edit_vce_config(){
	if(file_exists(BASEPATH.'vce-config')){
		$GLOBALS['content'] .= '<p><strong><span style="color:red;">Caution!</span></strong><span style="color:red;">You have run this installer script previously.
		<br><strong>Running it again will overwrite your site key and erase your user data.</strong>
		<br>You are seeing this message because there is already a configuration file. If you wish to start a new installation, either start anew with newly unzipped contents, or erase the existing config file, upload this installer again from the zip file, and run it.
		<br>This installer has now been deleted to prevent this from happening again in the future.</p></span>';
		unlink(__FILE__);
		return;
	}
// 	if(file_exists(BASEPATH.'vce-config.php')){
// 		$list = glob('vce-config_BAK*.php');
// 		foreach($list as $file){
// 			unlink($file);
// 		}
// 		copy('vce-config.php', 'vce-config_BAK'.date('Y_m_d_h_i_s').'.php');
// 		unlink(BASEPATH.'vce-config.php');
// 	}
	if(!file_exists(BASEPATH.'vce-config.php')){
		touch(BASEPATH.'vce-config.php');
		$newfile = fopen(BASEPATH."vce-config.php", "w") or die("Cannot open vce-config.php file.");
		if(file_exists(BASEPATH.'vce-config-sample.php')){
			$content = file_get_contents(BASEPATH.'vce-config-sample.php');
		}else{
		
$content = <<<EOF
<?php

/* Site key - DO NOT CHANGE THIS */
define('SITE_KEY', 'installer_generated_site_key_here');

/* The name of the database */
define('DB_NAME', 'database_name_here');

/* MySQL database username */
define('DB_USER', 'username_here');

/* MySQL database password */
define('DB_PASSWORD', 'password_here');

/* MySQL hostname */
define('DB_HOST', 'localhost');

/* MySQL table_prefix */
define('TABLE_PREFIX', 'vce_');

/* Enable query string input */
define('QUERY_STRING_INPUT', true);

/* Enable persistent login */
define('PERSISTENT_LOGIN', true);

/* set the path to uploaded files */
define('PATH_TO_UPLOADS', 'vce-content/uploads');

/* display MySQL and PHP errors */
define('VCE_DEBUG', true);

EOF;
		}
		fwrite($newfile, $content);
		fclose($newfile);
	}
	
	$reading = fopen(BASEPATH.'vce-config.php', 'r');
	$writing = fopen(BASEPATH.'configTEMP.php', 'w');

	$replaced = false;

	while (!feof($reading)) {
  		$line = fgets($reading);
  		if (stristr($line, 'SITE_KEY')) {
  			$line = "define('SITE_KEY', '');".PHP_EOL;
   			$replaced = true;
 		}
 		fputs($writing, $line);
	}
	fclose($reading); fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced) 
	{
  		rename('configTEMP.php', 'vce-config.php');
	} else {
 		 unlink('configTEMP.php');
	}


}
 
 
 function site_admin($username, $pwd, $firstname, $lastname, $db) {
 	//clean form input
 		$username = $db->mysqli_escape($username);
 		$pwd = $db->mysqli_escape($pwd);
 		$firstname = $db->mysqli_escape($firstname);
 		$lastname = $db->mysqli_escape($lastname);
 		
 		$prefix = check_config_value('TABLE_PREFIX');
 		$sql = 'DELETE a, b FROM '.$prefix.'users as a, '.$prefix.'users_meta as b WHERE a.role_id = 1';
 		$db->query($sql);
 		
		$return = array();
		
		$lookup = lookup($username);
		
		// check if exists
		$query = "SELECT id FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' and meta_value='" . $lookup . "'";
		$lookup_check = $db->get_data_object($query);
		
		if (!empty($lookup_check)) {
			$return['failure'] = '<span style="color:red;">Email is already in use; this user already exists.</span>';
			return $return;
		}
		
		// call to user class to create_hash function
		$hash = create_hash(strtolower($username),$pwd);
		
		// get a new vector for this user
		$vector = create_vector();
		
		//for use in mailing the SITE_KEY to the new site admin
		$_SESSION['site_admin'] = $username;

		$user_data = array(
		'vector' => $vector, 
		'hash' => $hash,
		'role_id' => 1
		);

		$db->insert( 'users', $user_data );
		$user_id = $db->lastid();
	
				
		// now add meta data

		$records = array();
				
		$lookup = lookup($username);
		
		$records[] = array(
		'user_id' => $user_id,
		'meta_key' => 'lookup',
		'meta_value' => $lookup,
		'minutia' => 'false'
		);
		
		
		$input = array('email'=>$username, 'first_name'=>$firstname, 'last_name'=>$lastname);
		
		foreach ($input as $key => $value) {

			// encode user data			
			$encrypted = encryption($value, $vector);
			
			$records[] = array(
			'user_id' => $user_id,
			'meta_key' => $key,
			'meta_value' => $encrypted,
			'minutia' => null
			);
			
		}		
		
		$db->insert('users_meta', $records);
		
		$return['site_admin_email'] = $username;
		$return['site_admin_name'] = $firstname.' '.$lastname;
		$return['success'] = '<span style="color:green;">Success: your Site Admin user has been created.</span">';
		
		
		return $return;
	

}
	/**
	 * take an email address and return the crypt
	 */
	function lookup($email) {

		// get salt
		$user_salt = substr(hash('md5', str_replace('@', hex2bin($GLOBALS['site_key']), $email)), 0, 22);

		// create lookup
		return crypt($email,'$2y$10$' . $user_salt . '$');
		
	}
	
	function create_hash($email, $password) {
	
		// get salt
		$user_salt = substr(hash('md5', str_replace('@', hex2bin($GLOBALS['site_key']), $email)), 0, 22);

		// combine credentials
		$credentials = $email . $password;

		// new hash value
		return crypt($credentials,'$2y$10$' . $user_salt . '$');
	
	}
	
	function create_vector() {
		if (OPENSSL_VERSION_NUMBER) {
			return base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')));
		} else {
			return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM));
		}
	}
	
	function encryption($encode_text,$vector) {
		if (OPENSSL_VERSION_NUMBER) {
			return base64_encode(openssl_encrypt($encode_text,'aes-256-cbc',hex2bin($GLOBALS['site_key']),OPENSSL_RAW_DATA,base64_decode($vector)));
		} else {
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hex2bin($GLOBALS['site_key']), $encode_text, MCRYPT_MODE_CBC, base64_decode($vector)));
		}
	}


function create_site_key() {
	if (function_exists('random_bytes')) {
		$site_key = bin2hex(random_bytes(32));
	} else {
		$site_key = bin2hex(@mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
	}

	// if random_bytes is not available and mcrypt_create_iv has been depreciated, then do the following
	if (empty($site_key)) {
		die('An error occured when attempting to create a SITE_KEY');
	}
	
	define_config_value('SITE_KEY', $site_key);
}

/**
 * Checks to see if a site key has already been defined.
 * @param $superadmin_email to send the new key to the superadmin
 * @return creates a site key if necessary
 */
function site_key($siteadmin_email) {
		$site_key = check_config_value('SITE_KEY');
		$GLOBALS['content'] .= "Hello ".$siteadmin_email.", your new site key has been created<br>
		It is:<br>
		<span style='color:green;'>".$site_key."</span><br>
		Please keep this in your records.";
		$msg = "Hello, \n
		This is the VCE SITE_KEY for your site:\n
		".$site_key."\n
		It is stored in the vce-config.php file at the root of your installation.\n
		Please keep it in your records to use in the event of a corruption of that configuration file.\n
		Thank you!";
		mail($siteadmin_email,'VCE SITE_KEY',$msg);
		return;
}




function check_database() {
	$return_toggle = FALSE;
	if (isset($_POST['pagecheck']) && $_POST['pagecheck']=='check') {
		if (empty($_POST['dbhost'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database host.</span><br>';
			$return_toggle = TRUE;
		}
		if (empty($_POST['dbname'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database name.</span><br>';
			$return_toggle = TRUE;
		}
		if (empty($_POST['dbuser'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database user.</span><br>';
			$return_toggle = TRUE;
		}
		if (empty($_POST['dbpassword'])) {
			$GLOBALS['content'] .= '<br><span style="color:red;">You have not specified a database user password.</span><br>';
			$return_toggle = TRUE;
		}
		if ($return_toggle == TRUE) {
			return 'wait';
		}
		define_config_value('DB_HOST', $_POST['dbhost']);
		define_config_value('DB_NAME', $_POST['dbname']);
		define_config_value('DB_USER', $_POST['dbuser']);
		define_config_value('DB_PASSWORD', $_POST['dbpassword']);
		define_config_value('TABLE_PREFIX', $_POST['dbprefix']);
		
		include_once(BASEPATH.'vce-config.php');
		try {
            $dbconnection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? DB_PORT : '3306');
//             mysqli_report(MYSQLI_REPORT_STRICT);
    		if ($dbconnection->connect_error) {
        		$GLOBALS['content'] .= '<br><span style="color:red;">The database connection has not been successful.<br>
        		The error: '.$dbconnection->connect_error.'</span><br>';
        		return 'wait';

    		} else {            
    			$GLOBALS['content'] .= '<br><span style="color:green;">You have connected successfully to the database "'.check_config_value('DB_NAME').'". Please click on &quot;continue&quot;.</span><br>';
				return 'continue';
			}
    
        } catch (Exception $e) {
        echo "";
        return 'wait';
//         	$GLOBALS['content'] .= $e->getMessage();
//			die('Database connection failed');
        }


	}
	return 'wait';
}



/**
 * Checks to see if all required modules and services are present on the server.
 * @param array $extensions names of extensions to check for
 * @return prints out success or failure notices for everything checked
 */
function extension_check($extensions) {
  	$fail = '';
	$pass = '';
	
	if (version_compare(phpversion(), '5.3.0', '<')) {
		$fail .= '<li>You need<strong> PHP 5.3.0</strong> (or greater)</li>';
	} else {
		$pass .='<li>Your version of PHP '.phpversion().' is greateer than PHP 5.3.0</li>';
	}
	if (!ini_get('safe_mode')) {
		$pass .='<li>Safe Mode is <strong>off</strong></li>';
// 		preg_match('/[0-9]\.[0-9]+\.[0-9]+/', mysqli_get_server_info(), $version);
// 		
// 		if (version_compare($version[0], '4.1.20', '<')) {
// 			$fail .= '<li>You need<strong> MySQL 4.1.20</strong> (or greater)</li>';
// 		} else {
// 			$pass .='<li>You have<strong> MySQL 4.1.20</strong> (or greater)</li>';
// 		}
	} else {
		$fail .= '<li>Safe Mode is <strong>on</strong></li>';
	}
	
	foreach($extensions as $extension) {
		if (!extension_loaded($extension)) {
			$fail .= '<li> You are missing the <strong>'.$extension.'</strong> extension</li>';
		} else {
			$pass .= '<li>You have the <strong>'.$extension.'</strong> extension</li>';
		}
	}
	
	// adding message about date.timezone
	if (!date_default_timezone_get()) {
		/*
		'Kwajalein',
		'Pacific/Midway',
		'Pacific/Honolulu',
		'America/Anchorage',
		'America/Los_Angeles',
		'America/Denver',
		'America/Tegucigalpa',
		'America/New_York',
		'America/Caracas',
		'America/Halifax',
		'America/St_Johns',
		'America/Argentina/Buenos_Aires',
		'America/Sao_Paulo',
		'Atlantic/South_Georgia',
		'Atlantic/Azores',
		'Europe/Dublin',
		'Europe/Belgrade',
		'Europe/Minsk',
		'Asia/Kuwait',
		'Asia/Tehran',
		'Asia/Muscat',
		'Asia/Yekaterinburg',
		'Asia/Kolkata',
		'Asia/Katmandu',
		'Asia/Dhaka',
		'Asia/Rangoon',
		'Asia/Krasnoyarsk',
		'Asia/Brunei',
		'Asia/Seoul',
		'Australia/Darwin',
		'Australia/Canberra',
		'Asia/Magadan',
		'Pacific/Fiji',
		'Pacific/Tongatapu'
		*/
		$pass .= '<li>date.timezone has not been set in php.ini and will default to America/Los_Angeles</li>';
	}
	
	$pass .= '<li>Your .htaccess file has been successfully updated.</li>';
	
	if ($fail) {
		$GLOBALS['content'] .= '<p><strong>Your server does not meet the following requirements in order to install VCE.</strong>';
		$GLOBALS['content'] .= '<br>The following requirements failed, please contact your hosting provider in order to receive assistance with meeting the system requirements for VCE:';
		$GLOBALS['content'] .= '<ul>'.$fail.'</ul></p>';
		$GLOBALS['content'] .= 'The following requirements were successfully met:';
		$GLOBALS['content'] .= '<ul>'.$pass.'</ul>';
		return 'wait';
	} else {
		$GLOBALS['content'] .= '<p><strong><span style="color:green;">Congratulations!</span></strong><span style="color:green;"> Your server meets the requirements for VCE.</p></span>';
		$GLOBALS['content'] .= '<ul>'.$pass.'</ul>';
		return 'continue';

	}
}

/**
 * Decides to use existing config.php file or create one.
 * Checks to see if there is a config file, creates one if not, and uses the config_sample as a template if exists.
 * @return creates vce-config.php
 */
function check_config_file() {
	if (!file_exists(BASEPATH.'vce-config.php') && file_exists(BASEPATH.'vce-config-sample.php')) {
		$GLOBALS['using_config_sample'] = TRUE;
		touch(BASEPATH.'vce-config.php');
	} elseif (!file_exists(BASEPATH.'vce-config.php') && !file_exists(BASEPATH.'vce-config-sample.php')) {
		touch(BASEPATH.'vce-config.php');
	} else {
		$GLOBALS['config_file_exists'] = TRUE;
	}
}

/**
 * Decides to use existing .htaccess file or create one.
 * @return creates .htaccess
 */
function check_htaccess_file() {
	if (!file_exists(BASEPATH.'.htaccess')) {
		touch(BASEPATH.'.htaccess');
		
	}

}

/**
 * Edits htaccess file.
 * Goes through the .htaccess  file line by line, and replaces target directives with correct (or same) directives
 * or creates them.
 * @return new .htaccess file with corrected directives
 */
function edit_htaccess_file() {
	$reading = file_get_contents(BASEPATH.'.htaccess');
	$writing = fopen(BASEPATH.'.htaccessTEMP', 'w');

	$replaced = false;

$required_content = PHP_EOL.'RewriteEngine On'.PHP_EOL.'
RewriteBase '.DOCPATH.''.PHP_EOL.'
RewriteRule ^index\.php$ - [L]'.PHP_EOL.'
RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL.'
RewriteCond %{REQUEST_FILENAME} !-d'.PHP_EOL.'
RewriteRule . '.DOCPATH.'index.php [L]'.PHP_EOL.'
RedirectMatch 301 '.DOCPATH.'vce-content/uploads/(.*) '.DOCPATH.PHP_EOL;

	preg_match('/.*<IfModule\s*mod_rewrite.c>(.*?)<\/IfModule>/ms', $reading, $matches);
	//to disable the parsing and simply wipe .htacces clean and add new content:
	if (1 == 2) {	
// if (isset($matches[1])) {
// echo '<br>';
// echo $matches[1];
	$matches[1] = str_replace($required_content, '', $matches[1]);
	$matches[1] = str_replace('###vce-directives', '', $matches[1]);
// 	$matches[1] = str_replace(PHP_EOL, '', $matches[1]);
	
// 	echo '<br>';
// echo $matches[1];
	$replacement = '<IfModule mod_rewrite.c>'.$matches[1].PHP_EOL.'###vce-directives'.$required_content.'###vce-directives'.PHP_EOL.'</IfModule>';
	$data = preg_replace('/<IfModule\s*mod_rewrite.c>(.*?)<\/IfModule>/ms', $replacement, $reading);
	$data2 = str_replace($data, '', $reading);
    
	fputs($writing, $data);
	
	$replaced = true;
} else {
	$insertion = '<IfModule mod_rewrite.c>'.PHP_EOL.'###vce-directives'.$required_content.'###vce-directives'.PHP_EOL.'</IfModule>';
	fputs($writing, $insertion);
	$replaced = true;
}

	//fclose($reading);
	 fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced) {
   		rename('.htaccessTEMP', '.htaccess');
	} else {
  		 unlink('.htaccessTEMP');
	}
}

/**
 * Edits constants in the config.php file.
 * Goes through the config.php file line by line, looking for the $constant_name to edit, and replaces
 * that whole line if it finds it. Otherwise writes the same line it has just read. Does NOT create the file
 * if it does not exist.
 * @param string $constant_name
 * @param string $constant_value
 * @return new config.php file with new constant value
 */
function define_config_value($constant_name, $constant_value) {
	$reading = fopen(BASEPATH.'vce-config.php', 'r');
	$writing = fopen(BASEPATH.'vce-configTEMP.php', 'w');
	$replaced = FALSE;

	while (!feof($reading)) {
  			$line = fgets($reading);
  		if (strstr($line, $constant_name)) { 	
  			if ($constant_value == 'true' || $constant_value == 'false') {
   				$line = "define('".$constant_name."', ".$constant_value.");".PHP_EOL;
   				$replaced = true;
 			} else {
 			   	$line = "define('".$constant_name."', '".$constant_value."');".PHP_EOL;
   			 	$replaced = true;  			 	
 			}
 		
 		}
 		fputs($writing, $line);
	}
	if ($replaced == FALSE && !empty($constant_name)) {
  			if ($constant_value == 'true' || $constant_value == 'false') {
   				$line = "define('".$constant_name."', ".$constant_value.");".PHP_EOL;
   				$replaced = true;
 			} else {
 			   	$line = "define('".$constant_name."', '".$constant_value."');".PHP_EOL;
   			 	$replaced = true;
 			}
		fputs($writing, $line); 
	}

	fclose($reading); fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced == true) {
// 		unlink(BASEPATH.'vce-config.php');
  		rename(BASEPATH.'vce-configTEMP.php', BASEPATH.'vce-config.php');
	} else {
 		unlink(BASEPATH.'vce-configTEMP.php');
	}
}


/**
 * Checks constant values in the config.php file.
 * Uses PHP's "token_get_all" to look at all the defined constants in vce_config.php
 * @param string $constant_name
 * @return mixed $constant_value
 */
function check_config_value($constant_name) {
	$defines = array();
	$state = 0;
	$key = '';
	$value = '';

	$file = file_get_contents(BASEPATH.'vce-config.php');
	$tokens = token_get_all($file);
	$token = reset($tokens);
	while ($token) {
    	if (is_array($token)) {
       	 if ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT) {
           	 // do nothing
       	 } else if ($token[0] == T_STRING && strtolower($token[1]) == 'define') {
            $state = 1;
       	 } else if ($state == 2 && is_constant($token[0])) {
       	     $key = $token[1];
       	     $state = 3;
      	  } else if ($state == 4 && is_constant($token[0])) {
      	      $value = $token[1];
       	     $state = 5;
      	  }
   	 } else {
     	   $symbol = trim($token);
     	   if ($symbol == '(' && $state == 1) {
     	       $state = 2;
     	   } else if ($symbol == ',' && $state == 3) {
     	       $state = 4;
     	   } else if ($symbol == ')' && $state == 5) {
     	       $defines[strip($key)] = strip($value);
     	       $state = 0;
     	   }
   	 }
  	  $token = next($tokens);
	}
	//checks constant existance and returns value if exists
	foreach ($defines as $k => $v) {
		if ($constant_name == $k) {
//   	 	 	echo "'$k' => '$v'\n";
  	 	 	return $v;
  	 	 }
	}

}
/**
 * Checks if token is constant.
 * Called from check_config_value().
 * @param mixed $token
 * @return mixed $token
 */
function is_constant($token) {
    return $token == T_CONSTANT_ENCAPSED_STRING || $token == T_STRING ||
        $token == T_LNUMBER || $token == T_DNUMBER;
}


/**
 * Strips constant value.
 * Called from check_config_value().
 * @param mixed $value
 * @return mixed $value
 */
function strip($value) {
	  return preg_replace('!^([\'"])(.*)\1$!', '$2', $value);
}



function form_validation_js() {
$script = <<<EOF
<script src="vce-application/js/jquery/jquery.min.js"></script>
<script src="vce-application/js/jquery/jquery-ui.min.js"></script>
<script type='text/javascript'>
$(document).ready(function() {

// click-bar
$('.clickbar-title').on('click touchend', function(e) {
	if ($(this).hasClass('disabled') !== true) {
		$(this).toggleClass('clickbar-closed');
		$(this).parent('.clickbar-container').children('.clickbar-content').slideToggle();
	}
});

$(document).on('focus', 'textarea, input[type=text],input[type=email], input[type=password], select', function() {
	$('.form-error').fadeOut(1000, function(){ 
    	$(this).remove();
	});
	$(this).parent('label').removeClass('highlight-alert').addClass('highlight');
	$(this).parents().eq(1).children(':submit').addClass('active-button');
});

$(document).on('blur', 'textarea, input[type=text], input[type=email], input[type=password], select', function() {
	$(this).parent('label').removeClass('highlight');
	if ($(this).val() === "") {
		$(this).parents().eq(1).children(':submit').removeClass('active-button');
	}
});

$('.installer-form').on('submit', function(e) {

	var formsubmitted = $(this);
	
	var submittable = true;
	
	var textareatest = $(this).find('textarea');
		textareatest.each(function(index) {
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				submittable = false;
			}
		});
			
	var typetest = $(this).find('input[type=text]');
		typetest.each(function(index) {
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				submittable = false;
			}
		});
	
	var emailtest = $(this).find('input[type=email]');
		emailtest.each(function(index) {
			reg = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
			if (!reg.test($(this).val()) && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				submittable = false;
			}
		});
		
	var passwordtest = $(this).find('input[type=password]');
		var test = [];
		passwordtest.each(function(index) {
		test[index] = $(this).val();
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').find('.label-error').text('Enter A Password');
				$(this).parent('label').addClass('highlight-alert');
				submittable = false;
			}
      		re = /[0-9]/;
      		if (!re.test($(this).val())) {
       			$(this).parent('label').find('.label-error').text('Password must contain at least one number (0-9)');
       	 		$(this).parent('label').addClass('highlight-alert');
        		submittable = false;
      		}
      		re = /[a-z]/;
      		if (!re.test($(this).val())) {
       			$(this).parent('label').find('.label-error').text('Password must contain at least one lowercase letter (a-z)');
       	 		$(this).parent('label').addClass('highlight-alert');
        		submittable = false;
      		}
      		re = /[A-Z]/;
      		if (!re.test($(this).val())) {
       			$(this).parent('label').find('.label-error').text('Password must contain at least one uppercase letter (A-Z)');
       	 		$(this).parent('label').addClass('highlight-alert');
        		submittable = false;
      		}
      		if (test[0] && test[1]) {
      			if (test[0] !== test[1]) {
       			$(this).parent('label').find('.label-error').text('Passwords do not match');
       	 		$(this).parent('label').addClass('highlight-alert');
        		submittable = false;
      			}
      		}
		});
		
	var selecttest = $(this).find('select');
		selecttest.each(function(index) {
			if ($(this).find('option:selected').val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				submittable = false;
			}
		});
	
	var checkboxtest = $(this).find('input[type=checkbox]');
		var box = {};	
		checkboxtest.each(function(index) {
			var boxname = $(this).attr('name');			
			var boxcheck = $(this).prop('checked');
			if (typeof box[boxname] !== 'undefined') {
				if (box[boxname] === false) {
					box[boxname] = boxcheck;
				}
			} else {
				box[boxname] = boxcheck;	
			}
			if (box[boxname] === false) {
				$(this).parent('label').parent('label').addClass('highlight-alert');
				submittable = false;
			} else {
				$(this).parent('label').parent('label').removeClass('highlight-alert');
				submittable = true;
			}
		});
	
	if (submittable) {
		return true;
	}
	
	return false;

});

function checkContinueForm(form)
  {
    if (form.direction.value == "wait") {
      alert("You must successfully submit the form below to continue!");
      form.username.focus();
      return false;
     }
     return true; 
  }

});
</script>
EOF;

return $script;
}

function installer_css(){

$style = <<<EOF
<style>

* {
font-family: sans-serif;
font-weight: 400;
font-size: 15px;
color: #333;
-webkit-tap-highlight-color: rgba(0,0,0,0);
}

html, body {
height: 100%;
margin: 0;
padding: 0;
-webkit-text-size-adjust: 100%;
-moz-text-size-adjust: 100%;
-ms-text-size-adjust: 100%;
}

#wrapper {
position: relative;
display: block;
width: 100%;
min-height: 100%;
margin: 0;
padding: 0;
background: #fff;
}

#content {
position: relative;
display: block;
padding: 0px 0px 100px 0px;
}

#decorative-bar {
position: relative;
display: block;
height: 15px;
background-color: #00A14B;
}

#header {
position: relative;
display: block;	
background-color: #005EAC;
height: 100px;
}

#header .inner {
height: 100px;
}

#header h1 {
font-size: 28px;
letter-spacing: 2px;
color: #FFF;
text-transform: uppercase;
text-align: center;
padding-top: 20px;
margin-top: 0px;
margin-bottom: 0px;
}

h1 {
font-size: 24px;
letter-spacing: 2px;
}

#info-bar {
height: 50px;
padding: 10px 0px 10px 0px;
background-color: #EEE8DA;
}

.inner {
width: 940px;
margin: 0 auto;
}

#info-bar-left {
display: block;
float: left;
max-width: 45%;
text-align: left;
}

#info-bar-right {
display: block;
float: right;
max-width: 45%;
text-align: right;
}

#welcome-text {
display: block;
padding: 0px 10px;
}


/* footer */
#footer {
position: absolute;
display: block;
width: 100%;
height: 60px;
bottom: 0px;
left: 0px;
color: #FFF;
text-align: center;
font-size: 11px;
background-color: #00A14B;
padding: 20px 0px 0px 0px;
line-height: 20px;
}

#footer .inner {
color: #FFF;
text-align: center;
font-size: 11px;
}

</style>
EOF;

return $style;

}

function print_globals() {
	foreach($_SESSION as $key=>$value) {

		echo $key.': ';
		print_r($value);
		echo '<br>';
	}
}


/**
 * Records specifics about the site installation
 *
 */
function personalize_site($site_name, $site_description, $db) {
// echo '<br>sn: '.$site_name.'<br>st: '.$site_description;
	$site_name = $db->mysqli_escape($site_name);
	$site_description = $db->mysqli_escape($site_description);
	$sql1 = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$site_name."' WHERE meta_key = 'site_title'";
	$sql2 = "UPDATE ".TABLE_PREFIX."site_meta SET meta_value = '".$_SESSION['site_admin']."' WHERE meta_key = 'site_email'";
	$table_query = 'SELECT meta_value FROM '.TABLE_PREFIX.'site_meta WHERE meta_key = "site_description"';
 	if (!$result = $db->query($table_query)) {	
		$sql3 = "UPDATE ".TABLE_PREFIX."site_meta SET  meta_value = '".$site_name."' WHERE meta_key = 'site_description'";
	}else{
		$sql3 = "INSERT INTO ".TABLE_PREFIX."site_meta (meta_key, meta_value, minutia) VALUES ('site_description', '".$site_description."', '')";
	}
	$db->query($sql1);
	$db->query($sql2);
	$db->query($sql3);
// 		define_config_value('VCE_SITE_NAME', $site_name);
// 		define_config_value('VCE_SITE_DESCRIPTION', $site_description);
	return 'continue';
	
}

function define_sql(){
$sql = <<<EOF

CREATE TABLE `vce_components` (
`component_id` bigint(20) unsigned NOT NULL,
`parent_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`sequence` bigint(20) unsigned NOT NULL DEFAULT '0',
`url` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=13;

INSERT INTO `vce_components` (`component_id`, `parent_id`, `sequence`, `url`) VALUES
(1, 0, 1, ''),
(2, 1, 1, ''),
(3, 2, 1, 'admin'),
(4, 3, 1, 'admin/manage_recipes'),
(5, 3, 2, 'admin/manage_components'),
(6, 3, 4, 'admin/manage_menus'),
(7, 3, 5, 'admin/manage_users'),
(8, 3, 6, 'admin/mange_site'),
(9, 3, 7, 'admin/manage_datalists'),
(10, 0, 1, 'logout'),
(11, 0, 1, '/'),
(12, 0, 1, 'user');

ALTER TABLE `vce_components`
ADD PRIMARY KEY (`component_id`);
ALTER TABLE `vce_components`
ADD INDEX (`component_id`);
ALTER TABLE `vce_components`
ADD INDEX (`parent_id`);

ALTER TABLE `vce_components`
MODIFY `component_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;

CREATE TABLE `vce_components_meta` (
`id` bigint(20) unsigned NOT NULL,
`component_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=63;

INSERT INTO `vce_components_meta` (`id`, `component_id`, `meta_key`, `meta_value`, `minutia`) VALUES
(1, 1, 'created_by', '1', ''),
(2, 1, 'created_at', '1460412330', ''),
(3, 1, 'recipe', '{"recipe":[{"title":"Login","auto_create":"forward","type":"Login","components":[{"content_create":1,"role_access":1,"content_delete":"user","content_edit":"user","repudiated_url":"/","title":"Admin Area","auto_create":"forward","type":"Access","components":[{"url":"admin","title":"Admin","auto_create":"forward","type":"Location","components":[{"url":"admin/manage_recipes","title":"Manage Recipes","auto_create":"forward","type":"ManageRecipes"},{"url":"admin/manage_components","title":"Manage Components","auto_create":"forward","type":"ManageComponents"},{"url":"admin/manage_menus","title":"Manage Menus","auto_create":"forward","type":"ManageMenus"},{"url":"admin/manage_users","title":"Manage Users","auto_create":"forward","type":"ManageUsers"},{"url":"admin/mange_site","title":"Manage Site","auto_create":"forward","type":"ManageSite"},{"url":"admin/manage_datalists","title":"Manage Datalists","auto_create":"forward","type":"ManageDatalists"}]}]}]}],"recipe_name":"Admin"}', ''),
(4, 1, 'title', 'Login', ''),
(5, 1, 'type', 'Login', ''),
(6, 1, 'recipe_name', 'Admin', ''),
(7, 2, 'created_by', '1', ''),
(8, 2, 'created_at', '1460412330', ''),
(9, 2, 'role_access', '1', ''),
(10, 2, 'title', 'Admin Area', ''),
(11, 2, 'type', 'Access', ''),
(12, 2, 'content_create', '1', ''),
(13, 2, 'content_delete', 'user', ''),
(14, 2, 'content_edit', 'user', ''),
(15, 3, 'created_by', '1', ''),
(16, 3, 'created_at', '1460412330', ''),
(17, 3, 'title', 'Admin', ''),
(18, 3, 'type', 'Location', ''),
(19, 4, 'created_by', '1', ''),
(20, 4, 'created_at', '1460412330', ''),
(21, 4, 'title', 'Manage Recipes', ''),
(22, 4, 'type', 'ManageRecipes', ''),
(23, 5, 'created_by', '1', ''),
(24, 5, 'created_at', '1460414199', ''),
(25, 5, 'title', 'Manage Components', ''),
(26, 5, 'type', 'ManageComponents', ''),
(27, 6, 'created_by', '1', ''),
(28, 6, 'created_at', '1460501860', ''),
(29, 6, 'title', 'Manage Menus', ''),
(30, 6, 'type', 'ManageMenus', ''),
(31, 6, 'updated_at', '1466100290', ''),
(32, 7, 'created_by', '1', ''),
(33, 7, 'created_at', '1460501968', ''),
(34, 7, 'title', 'Manage Users', ''),
(35, 7, 'type', 'ManageUsers', ''),
(36, 8, 'created_by', '1', ''),
(37, 8, 'created_at', '1460501968', ''),
(38, 8, 'title', 'Manage Site', ''),
(39, 8, 'type', 'ManageSite', ''),
(40, 9, 'created_by', '1', ''),
(41, 9, 'created_at', '1460662937', ''),
(42, 9, 'title', 'Manage Datalists', ''),
(43, 9, 'type', 'ManageDatalists', ''),
(44, 10, 'created_by', '1', ''),
(45, 10, 'created_at', '1467146039', ''),
(46, 10, 'recipe', '{"recipe":[{"url":"logout","title":"Logout","auto_create":"forward","type":"Logout"}],"recipe_name":"Logout"}', ''),
(47, 10, 'title', 'Logout', ''),
(48, 10, 'type', 'Logout', ''),
(49, 10, 'recipe_name', 'Logout', ''),
(50, 11, 'created_by', '1', ''),
(51, 11, 'created_at', '1467146780', ''),
(52, 11, 'recipe', '{"recipe":[{"template":"home.php","url":"/","title":"Home Page","auto_create":"forward","type":"Location"}],"recipe_name":"Home Page"}', ''),
(53, 11, 'template', 'home.php', ''),
(54, 11, 'title', 'Home Page', ''),
(55, 11, 'type', 'Locations', ''),
(56, 11, 'recipe_name', 'Home Page', ''),
(57, 12, 'created_by', '1', ''),
(58, 12, 'created_at', '1467149348', ''),
(59, 12, 'recipe', '{"recipe":[{"url":"user","title":"User Settings","auto_create":"forward","type":"UserSettings"}],"recipe_name":"My Account"}', ''),
(60, 12, 'title', 'User Settings', ''),
(61, 12, 'type', 'UserSettings', ''),
(62, 12, 'recipe_name', 'My Account', '');

ALTER TABLE `vce_components_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_components_meta`
ADD INDEX (`component_id`);
ALTER TABLE `vce_components_meta` 
ADD INDEX (`meta_key`);

ALTER TABLE `vce_components_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=63;

CREATE TABLE `vce_datalists` (
`datalist_id` bigint(20) unsigned NOT NULL,
`parent_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`item_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`component_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
`sequence` bigint(20) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists`
ADD PRIMARY KEY (`datalist_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (`datalist_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (`parent_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (`item_id`);
ALTER TABLE `vce_datalists`
ADD INDEX (component_id);
ALTER TABLE `vce_datalists`
ADD INDEX (`user_id`);

ALTER TABLE `vce_datalists`
MODIFY `datalist_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_datalists_meta` (
`id` bigint(20) unsigned NOT NULL,
`datalist_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) CHARACTER SET latin1 NOT NULL,
`meta_value` text CHARACTER SET latin1 NOT NULL,
`minutia` varchar(255) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_datalists_meta`
ADD INDEX (`datalist_id`);
ALTER TABLE `vce_datalists_meta`
ADD INDEX (`meta_key`);

ALTER TABLE `vce_datalists_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_datalists_items` (
`item_id` bigint(20) NOT NULL,
`datalist_id` bigint(20) NOT NULL,
`sequence` bigint(20) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists_items`
ADD PRIMARY KEY (`item_id`);
ALTER TABLE vce_datalists_items`
ADD INDEX (`item_id`);
ALTER TABLE vce_datalists_items`
ADD INDEX (`datalist_id`);

ALTER TABLE `vce_datalists_items`
MODIFY `item_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_datalists_items_meta` (
`id` bigint(20) unsigned NOT NULL,
`item_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

ALTER TABLE `vce_datalists_items_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_datalists_items_meta`
ADD INDEX (`item_id`);
ALTER TABLE `vce_datalists_items_meta`
ADD INDEX (`meta_key`);

ALTER TABLE `vce_datalists_items_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_site_meta` (
`id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=10;

INSERT INTO `vce_site_meta` (`id`, `meta_key`, `meta_value`, `minutia`) VALUES
(1, 'site_url', '', ''),
(2, 'site_title', '', ''),
(3, 'site_description', '', ''),
(4, 'site_email', '', ''),
(5, 'site_menus', '{"main":[{"role_access":"1|2|3","title":"My Account","url":"user","id":1171},{"role_access":"1|2|3","title":"Logout","url":"logout","id":235},{"role_access":"1|x","title":"Admin","url":"admin","id":49}]}', ''),
(6, 'site_theme', 'default', ''),
(7, 'roles', '{"1":{"role_name":"Admin","permissions":{"ManageUsers":"create_users,edit_users,delete_users,masquerade_users"},"role_hierarchy":"0"}}', ''),
(8, 'user_attributes', '{"first_name":{"type":"text","title":"First Name","required":"1","sortable":"1","editable":"1"},"last_name":{"type":"text","title":"Last Name","required":"1","sortable":"1","editable":"1"}}', ''),
(9, 'installed_components', '{"Input":"vce-application/components/input/input.php","File":"vce-application/components/file/file.php","Upload":"vce-application/components/upload/upload.php","Access":"vce-application/components/access/access.php","Item":"vce-application/components/item/item.php","Layout":"vce-application/components/layout/layout.php","Login":"vce-application/components/login/login.php","Logout":"vce-application/components/logout/logout.php","ManageMenus":"vce-application/components/managemenus/managemenus.php","ManageRecipes":"vce-application/components/managerecipes/managerecipes.php","ManageComponents":"vce-application/components/managecomponents/managecomponents.php","ManageSite":"vce-application/components/managesite/managesite.php","ManageUsers":"vce-application/components/manageusers/manageusers.php","Media":"vce-application/components/media/media.php","Set":"vce-application/components/set/set.php","UserSettings":"vce-application/components/usersettings/usersettings.php","ManageDatalists":"vce-application/components/managedatalists/managedatalists.php","Location":"vce-application/components/location/location.php","Image":"vce-application/components/image/image.php","Text":"vce-application/components/text/text.php"}', ''),
(10, 'activated_components', '{"Input":"vce-application/components/input/input.php","File":"vce-application/components/file/file.php","Upload":"vce-application/components/upload/upload.php","Access":"vce-application/components/access/access.php","Item":"vce-application/components/item/item.php","Layout":"vce-application/components/layout/layout.php","Login":"vce-application/components/login/login.php","Logout":"vce-application/components/logout/logout.php","ManageMenus":"vce-application/components/managemenus/managemenus.php","ManageRecipes":"vce-application/components/managerecipes/managerecipes.php","ManageComponents":"vce-application/components/managecomponents/managecomponents.php","ManageSite":"vce-application/components/managesite/managesite.php","ManageUsers":"vce-application/components/manageusers/manageusers.php","Set":"vce-application/components/set/set.php","UserSettings":"vce-application/components/usersettings/usersettings.php","ManageDatalists":"vce-application/components/managedatalists/managedatalists.php","Location":"vce-application/components/location/location.php","Media":"vce-application/components/media/media.php","Image":"vce-application/components/image/image.php","Text":"vce-application/components/text/text.php"}', ''),
(11, 'preloaded_components', '{"Input":"vce-application/components/input/input.php","File":"vce-application/components/file/file.php","Upload":"vce-application/components/upload/upload.php","Media":"vce-application/components/media/media.php"}', ''),
(12, 'enabled_mediatype', '{"Image":"vce-application/components/image/image.php","Text":"vce-application/components/text/text.php"}', '');

ALTER TABLE `vce_site_meta`
ADD PRIMARY KEY (`id`);

ALTER TABLE `vce_site_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;

CREATE TABLE `vce_users` (
`user_id` bigint(20) unsigned NOT NULL,
`vector` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`role_id` bigint(20) unsigned NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `vce_users`
ADD PRIMARY KEY (`user_id`);
ALTER TABLE `vce_users`
ADD INDEX (`user_id`);

ALTER TABLE `vce_users`
MODIFY `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE `vce_users_meta` (
`id` bigint(20) unsigned NOT NULL,
`user_id` bigint(20) unsigned NOT NULL,
`meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
`meta_value` text COLLATE utf8_unicode_ci NOT NULL,
`minutia` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `vce_users_meta`
ADD PRIMARY KEY (`id`);
ALTER TABLE `vce_users_meta`
ADD INDEX (`user_id`);
ALTER TABLE `vce_users_meta`
ADD INDEX (`meta_key`);

ALTER TABLE `vce_users_meta`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

EOF;
return $sql;
}

?>
