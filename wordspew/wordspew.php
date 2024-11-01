<?php
/*
Plugin Name: Jalenack's Wordspew
Plugin URI: http://blog.jalenack.com/ajax/
Description: A plugin that creates a live shoutbox, using AJAX as a backend. Users can chat freely from your blog without refreshing the page! It uses the Fade Anything Technique for extra glamour
Author: Andrew Sutherland
Version: 1.16
Author URI: http://blog.jalenack.com
*/

// Version of this plugin. Not very useful for you, but for the dev
$jal_version = "1.16";

// The required user level needed to access the admin page for this plugin
$jal_admin_user_level = 8;

// The number of comments that should show up in one viewing.
$jal_number_of_comments = 35;

/*
==================// Important Info //=======================================

This file is called in three different places. First, it is called by Wordpress
so that it can display the jal_get_shoutbox function.

Secondly, it is called by javascript. Because it is called by javascript and not by wordpress,
normal plugin procedure won't work without directly calling the wp-config file in your root
directory. We cannot use the $wpdb object. So instead, we open up the config file, comment out
the bit about calling the rest of Wordpress, and then evaluate the config file to extract how to 
access the database. We do all this so that the entirety of Wordpress is not loaded by each call
to the server, which would put great unneccessary load on the server and conflict with other plugins.

To differentiate between the two remote calls, I've used the $jalGetChat and $jalSendChat variables.
These two variables are sent by the javascript file as "yes". That way, the script will only perform
certain actions if it is called by the javascript file.

Thirdly, it is called by the wordpress admin panel, which allows users to edit settings for this plugin

==================// End of Info //==========================================
*/

// Register globals - Thanks Karan et Etienne
$jal_lastID    = isset($_GET['jal_lastID']) ? $_GET['jal_lastID'] : "";
$jal_user_name = isset($_POST['n']) ? $_POST['n'] : ""; 
$jal_user_url  = isset($_POST['u']) ? $_POST['u'] : "";
$jal_user_text = isset($_POST['c']) ? $_POST['c'] : "";
$jalGetChat    = isset($_GET['jalGetChat']) ? $_GET['jalGetChat'] : "";
$jalSendChat   = isset($_GET['jalSendChat']) ? $_GET['jalSendChat'] : "";

function jal_install_shout () {
	global $table_prefix, $wpdb, $user_level, $jal_admin_user_level;
	
    get_currentuserinfo();
    if ($user_level < $jal_admin_user_level) return; 
	
  	$result = mysql_list_tables(DB_NAME);
  	$tables = array();

  	while ($row = mysql_fetch_row($result)) { $tables[] = $row[0]; }
  
    if (!in_array($table_prefix."liveshoutbox", $tables)) {
    	$first_install = "yes";
    }

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	
	dbDelta("CREATE TABLE ".$table_prefix."liveshoutbox (
		     id mediumint(7) NOT NULL AUTO_INCREMENT,
		     time bigint(11) DEFAULT '0' NOT NULL,
		     name tinytext NOT NULL,
		     text text NOT NULL,
		     url text NOT NULL,
		     UNIQUE KEY id (id)
		    );");
		
	if ($first_install == "yes") {
		
		$welcome_name = "Jalenack";
		$welcome_text = "Congratulations, you just completed the installation of this shoutbox.";
	
		$wpdb->query("INSERT INTO ".$table_prefix."liveshoutbox (time,name,text) VALUES ('".time()."','".$welcome_name."','".$welcome_text."')");
		
		// Default shoutbox color config
		add_option('shoutbox_fade_from', "666666");
		add_option('shoutbox_fade_to', "FFFFFF");
		add_option('shoutbox_update_seconds', 4000);
		add_option('shoutbox_fade_length', 1500);
		add_option('shoutbox_text_color', "333333");
		add_option('shoutbox_name_color', "0066CC");
		add_option('shoutbox_regisitered_only', '0');
	}
}

if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('init', 'jal_install_shout');
}


// function to print the external javascript and css links
function jal_add_to_head () {
    global $jal_version;
  
      //$jal_wp_url = (dirname($_SERVER['PHP_SELF']) == "/") ? "/" : dirname($_SERVER['PHP_SELF']) . "/";
      $jal_wp_url = get_bloginfo('wpurl') . "/";
    
    echo '
    <!-- Added By Wordspew Plugin. Version '.$jal_version.' -->
    <link rel="stylesheet" href="'.$jal_wp_url.'wp-content/plugins/wordspew/css.php" type="text/css" />
    <script type="text/javascript" src="'.$jal_wp_url.'wp-content/plugins/wordspew/fatAjax.php"></script>
		';
}

// In the administration page, add some style...
function jal_add_to_admin_head () {
?>
<style type="text/css">

/* Styles added by Wordspew shoutbox plugin */

input[name=jal_delete]:hover, #jal_truncate_all:hover {
background: #c22;
color: #fff;
cursor: pointer;
}

input[name=jal_edit]:hover {
background: #2c2;
color: #fff;
cursor: pointer;
}

#shoutbox_options p {
text-indent: 15px;
padding: 5px 0;
color: #555;
}

#shoutbox_options span {
border: 1px dotted #ccc;
padding: 4px 14px;
}
</style>

<?php
}
	
// HTML printed to the admin panel
function jal_shoutbox_admin () { 
	global $jal_admin_user_level, $wpdb, $user_level, $table_prefix, $jal_number_of_comments;
	get_currentuserinfo(); // Gets logged in user.
	
	// If user is not allowed to use the admin page
	if ($user_level < $jal_admin_user_level) { 
		echo '<div class="wrap"><h2>No Access for you!</h2></div>';
	} else {
 ?>
 		<?php if (isset($_GET['jal_delete'])) { ?>
            <div class="updated">
                <p>The comment was deleted successfully.</p>
            </div>
		<?php } if (isset($_GET['jal_edit'])) { ?>
            <div class="updated">
                <p>The comment was edited successfully.</p>
            </div>
		<?php } if (isset($_GET['jal_truncate'])) { ?>
            <div class="updated">
                <p>The shoutbox database has been wiped. You now have a fresh slate!</p>
            </div>
		<?php } ?>
		
	<div class="wrap">
		<h2>Jalenack's Live Shoutbox</h2>

		<p>When you update the Times and Colors, you may need to refresh/empty cache before you see the changes take effect</p>
		<p>There have been <b><?php
		 $results = $wpdb->get_var("SELECT id FROM ".$table_prefix."liveshoutbox ORDER BY id DESC LIMIT 1");
		 echo $results; ?></b> messages in this shoutbox
		<form name="shoutbox_options" action="" method="get" id="shoutbox_options"> 
  			<fieldset> 
  				<legend><b>Colors (Must be 6 digit hex)</b></legend>
  				<input type="hidden" name="page" value="wordspew" />
  				Fade from: #<input type="text" maxlength="6" name="fade_from" value="<?php echo get_option('shoutbox_fade_from'); ?>" size="6" /> <span style="background: #<?php echo get_option('shoutbox_fade_from'); ?>;">&nbsp;</span>
  				<p>The color that new messages fade in from. Default: <span style="color: #666">666666</span></p>
  				Fade to: #<input type="text" maxlength="6" name="fade_to" value="<?php echo get_option('shoutbox_fade_to'); ?>" size="6" /> <span style="background: #<?php echo get_option('shoutbox_fade_to'); ?>;">&nbsp;</span>
				<p>Also used as the background color of the box. Default: FFFFFF (white)</p>
  				Text Color: #<input type="text" maxlength="6" name="text_color" value="<?php echo get_option('shoutbox_text_color'); ?>" size="6" /> <span style="background: #<?php echo get_option('shoutbox_text_color'); ?>;">&nbsp;</span>
				<p>The color of text within the box. Default: <span style="color: #333">333333</span></p>
				Name Color: #<input type="text" maxlength="6" name="name_color" value="<?php echo get_option('shoutbox_name_color'); ?>" size="6" /> <span style="background: #<?php echo get_option('shoutbox_name_color'); ?>;">&nbsp;</span>
				<p>The color of peoples' names. Default: <span style="color: #06c">0066CC</span></p>

  			</fieldset>
  			<br />
  			<fieldset> 
  				<legend><b>Other</b></legend>
  				Update Every: <input type="text" maxlength="3" name="update_seconds" value="<?php echo get_option('shoutbox_update_seconds') / 1000; ?>" size="2" /> Seconds <br />
  					<p>This determines how "live" the shoutbox is. With a bigger number, it will take more time for messages to show up, but also decrease the server load. You may use decimals. This number is used as the base for the first 8 javascript loads. After that, the number gets successively bigger. Adding a new comment or mousing over the shoutbox will reset the interval to the number suplied above. Default: 4 Seconds</p>
  				Fade Length: <input type="text" maxlength="3" name="fade_length" value="<?php echo get_option('shoutbox_fade_length') / 1000; ?>" size="2" /> Seconds <br />
					<p>The amount of time it takes for the fader to completely blend with the background color. You may use decimals. Default 1.5 seconds</p>
            Use textarea: <input type="checkbox" name="use_textarea" <?php if(get_option('shoutbox_use_textarea') == 'true') { echo 'checked="checked" '; } ?>/>
               <p>A textarea is a bigger type of input box. Users will have more room to type their comments, but it will take up more space.</p>
            Use URL field: <input type="checkbox" name="use_url" <?php if(get_option('shoutbox_use_url') == 'true') echo 'checked="checked" '; ?>/>
               <p>Check this if you want users to have an option to add their URL when submitting a message.</p>
				Only allow registered users: <input type="checkbox" name="registered_only" <?php if(get_option('shoutbox_registered_only') == '1') echo 'checked="checked" '; ?>/>
  					<p>This will only let your registered users use the form that allows one to type messages. Users who are NOT logged in will be able to watch the chat and a message saying they must be logged in to comment. <b>Note:</b> this is not completely "secure" .. If someone REALLY wanted to, they could write a script that interacts directly with the message receiving file. They'd have to know what they're doing and it would be quite pointless.</p>
</fieldset><br />
  						 
  			<input type="submit" name="jal_admin_options" value="Save" class="button" style="font-size: 140%"  /><br /><br />
  			<input type="submit" name="jal_truncate" id="jal_truncate_all" onclick="return confirm('You are about to delete ALL messages in the shoutbox. It will completely erase all messages. Are you sure you want to do this?');" value="Delete ALL messages" /><br /><br />
		</form>
  			
  			<fieldset> 
  				<legend><b>Data</b> (showing the last <b><?php echo $jal_number_of_comments; ?></b> messages)</legend>
  				<p>Reminder: You MUST have at LEAST one comment in your shoutbox at all times. This is not live. New comments made while viewing this page will not magically appear like they do in the real thing.</p>
				
				<?php
 						$results = $wpdb->get_results("SELECT * FROM ".$table_prefix."liveshoutbox ORDER BY id DESC LIMIT ". $jal_number_of_comments);
 						if (!$results) { echo "<b>You must have at least 1 message in your shoutbox at all times!<br /> Go to your shoutbox and add a messages.</b>"; } else {
 						$jal_first_time = "yes"; // Will only add the last message div if it is looping for the first time
						foreach( $results as $r ) { // Loops the messages into a list
							
							$url = (empty($r->url) && $r->url = "http://") ? $r->name : '<a href="'.$r->url.'">'.$r->name.'</a>';

							if ($jal_first_time == "yes") {	echo '<div id="lastMessage"><span>Last Message</span> <em id="responseTime">'.jal_time_since( $r->time ).' ago</em></div>
 						<ul id="outputList">
 						'; }
						        echo '<li><form name="shoutbox_options" action="" method="get"><span>'.stripslashes($url).' : </span><input type="text" name="jal_text" value="'.stripslashes($r->text).'" size="60" />
						        <input type="hidden" name="page" value="wordspew" />
						        <input type="hidden" name="jal_comment_id" value="'.$r->id.'" />
						        <input type="submit" name="jal_delete" value="Delete" />
						        <input type="submit" name="jal_edit" value="Edit" /></form></li>

						        '; 
					$jal_first_time = "0";	} }
				?>
				</ul>


  			</fieldset>
	</div>

<?php } }

// To add administration page under Management Section
function shoutbox_admin_page() {
	global $jal_admin_user_level;
	add_management_page('Shoutbox Management', 'Live Shoutbox', $jal_admin_user_level, "wordspew", 'jal_shoutbox_admin');
}

// Time Since function courtesy 
// http://blog.natbat.co.uk/archive/2003/Jun/14/jal_time_since

// Works out the time since the entry post, takes a an argument in unix time (seconds)
function jal_time_since($original) {
    // array of time period chunks
    $chunks = array(
        array(60 * 60 * 24 * 365 , 'year'),
        array(60 * 60 * 24 * 30 , 'month'),
        array(60 * 60 * 24 * 7, 'week'),
        array(60 * 60 * 24 , 'day'),
        array(60 * 60 , 'hour'),
        array(60 , 'minute'),
    );
    $original = $original - 10; // Shaves a second, eliminates a bug where $time and $original match.
    $today = time(); /* Current unix time  */
    $since = $today - $original;
    
    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
        
        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }
    
    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
    
    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];
        
        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
        }
    }
    return $print;
}

////////////////////////////////////////////////////////////
// Functions Below are for getting comments from the database
////////////////////////////////////////////////////////////

// Never cache this page
if ($jalGetChat == "yes" || $jalSendChat == "yes") {
	header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
	header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )."GMT" ); 
	header( "Cache-Control: no-cache, must-revalidate" ); 
	header( "Pragma: no-cache" );
	header("Content-Type: text/html; charset=utf-8");
	
	//if the request does not provide the id of the last know message the id is set to 0
	if (!$jal_lastID) $jal_lastID = 0;
}

// retrieves all messages with an id greater than $jal_lastID
if ($jalGetChat == "yes") {
	jal_getData($jal_lastID);
}

// Where the shoutbox receives information
function jal_getData ($jal_lastID) {

	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);

	$sql = "SELECT * FROM ".$table_prefix."liveshoutbox WHERE id > ".$jal_lastID." ORDER BY id DESC";
	$results = mysql_query($sql, $conn);
	$loop = "";
	while ($row = mysql_fetch_array($results)) {

		$id   = $row[0];
		$time = $row[1];
		$name = $row[2];
		$text = $row[3];
		$url  = $row[4];
		
		// append the new id's to the beginning of $loop
		$loop = $id."---".stripslashes($name)."---".stripslashes($text)."---".jal_time_since($time)." ago---".stripslashes($url)."---" . $loop; // --- is being used to separate the fields in the output
	}
	echo $loop;
	
	// if there's no new data, send one byte. Fixes a bug where safari gives up w/ no data
	if (empty($loop)) { echo "0"; }
}

function jal_special_chars ($s) {
  $s = htmlspecialchars($s, ENT_COMPAT,'UTF-8');
  return str_replace("---","&minus;-&minus;",$s);
}

////////////////////////////////////////////////////////////
// Functions Below are for submitting comments to the database
////////////////////////////////////////////////////////////

// When user submits and javascript fails
if (isset($_POST['shout_no_js'])) {
	if ($_POST['shoutboxname'] != '' && $_POST['chatbarText'] != '') {
		jal_addData($_POST['shoutboxname'], $_POST['chatbarText'], $_POST['shoutboxurl']);
		
		jal_deleteOld(); //some database maintenance
    	
    	setcookie("jalUserName",$_POST['shoutboxname'],time()+60*60*24*30*3,'/');
    	setcookie("jalUrl",$_POST['shoutboxurl'],time()+60*60*24*30*3,'/');
        //take them right back where they left off
		header('location: '.$_SERVER['HTTP_REFERER']);
	} else echo "You must have a name and a comment";
}

	//only if a name and a message have been provides the information is added to the db
if ($jal_user_name != '' && $jal_user_text != '' && $jalSendChat == "yes") {
		jal_addData($jal_user_name,$jal_user_text,$jal_user_url); //adds new data to the database
		jal_deleteOld(); //some database maintenance
		echo "0";
}

function jal_addData($jal_user_name,$jal_user_text,$jal_user_url) {
	//the message is cut of after 500 letters
	$jal_user_text = substr($jal_user_text,0,500); 
	
	$jal_user_name = substr(trim($jal_user_name), 0,18);

///// The code below can mess up multibyte strings

// If there isn't a url, truncate the words to 25 chars each
//	if (!preg_match("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", $jal_user_text, $matches))
//		$jal_user_text = preg_replace("/([^\s]{25})/","$1 ",$jal_user_text);


	// CENSORS .. default is off. To turn it on, uncomment the line below. Add new lines with new censors as needed.	
	//$jal_user_text = str_replace("fuck", "****", $jal_user_text);

	$jal_user_text = jal_special_chars(trim($jal_user_text));
	$jal_user_name = (empty($jal_user_name)) ? "Anonymous" : jal_special_chars($jal_user_name);
	$jal_user_url = ($jal_user_url == "http://") ? "" : jal_special_chars($jal_user_url);

	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	
	mysql_query("INSERT INTO ".$table_prefix."liveshoutbox (time,name,text,url) VALUES ('".time()."','".mysql_real_escape_string($jal_user_name)."','".mysql_real_escape_string($jal_user_text)."','".mysql_real_escape_string($jal_user_url)."')", $conn);
}

//Maintains the database by deleting past comments
function jal_deleteOld() {
	global $jal_number_of_comments;

	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);

	$results = mysql_query("SELECT * FROM ".$table_prefix."liveshoutbox ORDER BY id DESC LIMIT ".$jal_number_of_comments, $conn);	

	while ($row = mysql_fetch_array($results)) { $id = $row[0]; }
	
	if ($id) mysql_query("DELETE FROM ".$table_prefix."liveshoutbox WHERE id < ".$id, $conn);

}

// Prints the html structure for the shoutbox
	function jal_get_shoutbox () {
	global $wpdb, $table_prefix, $jal_number_of_comments;
?>
            <div id="wordspew">
				<div id="chatoutput">
					<?php
								 								
 								$wpdb->hide_errors();
								$results = $wpdb->get_results("SELECT * FROM ".$table_prefix."liveshoutbox ORDER BY id DESC LIMIT ".$jal_number_of_comments);
								$wpdb->show_errors();
								
								// Will only add the last message div if it is looping for the first time
								$jal_first_time = true; 
								
								// Loops the messages into a list
								if($results) {foreach( $results as $r ) { 
								
								// Add links								
								$r->text = preg_replace( "`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", "<a href=\"\\0\">&laquo;link&raquo;</a>", $r->text);

								if ($jal_first_time == true) { echo '<div id="lastMessage"><span>'.__("Latest Message").'</span> <em id="responseTime">'.jal_time_since( $r->time ).' ago</em></div>
 						<ul id="outputList">
 						'; }
 						
 						if ($jal_first_time == true) $lastID = $r->id;
 						
 						$url = (empty($r->url) && $r->url = "http://") ? $r->name : '<a href="'.$r->url.'">'.$r->name.'</a>';
 						 						
						        echo '<li><span title="'.jal_time_since( $r->time ).'">'.stripslashes($url).' : </span>'.convert_smilies(" ".stripslashes($r->text)).'</li>
						        '; 
						        
					$jal_first_time = false; } 
					
					// If there is less than one entry in the box
					} else {
					echo "You need <b>at least one entry</b> in your shoutbox! Just type in a message now and reload, then you should be fine.";
					}
					
					$use_url = (get_option('shoutbox_use_url') == "true") ? TRUE : FALSE;
					$use_textarea = (get_option('shoutbox_use_textarea') == "true") ? TRUE : FALSE;
					$registered_only = (get_option('shoutbox_registered_only') == "1") ? TRUE : FALSE;
					
					global $user_level, $user_nickname, $user_url, $user_ID, $jal_admin_user_level;
					get_currentuserinfo(); // Gets logged in user.

				?>
</ul>

				</div>
		<?php if (!$registered_only || ($registered_only && $user_ID)) { ?>
				<form id="chatForm" method="post" action="<?php bloginfo('wpurl'); ?>/wp-content/plugins/wordspew/wordspew.php">
				    <p><?php
					
					if ($user_level >= $jal_admin_user_level) { // If user is allowed to use the admin page
						echo '<a href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?page=wordspew" id="shoutboxAdmin">'.__('Admin').'</a>';
					} 
					if (!empty($user_nickname)) { /* If they are logged in, then print their nickname */ ?>
						
					<label><?php _e('Name'); ?>: <em><?php echo $user_nickname ?></em></label>
					
					<input type="hidden" name="shoutboxname" id="shoutboxname" value="<?php echo $user_nickname; ?>" />
					<input type="hidden" name="shoutboxurl" id="shoutboxurl" value="<?php if($use_url) { echo $user_url; } ?>" />
					<?php } else { echo "\n"; /* Otherwise allow the user to pick their own name */ ?>
					<label for="shoutboxname"><?php _e('Name'); ?>:</label>
					<input type="text" name="shoutboxname" id="shoutboxname" value="<?php if ($_COOKIE['jalUserName']) { echo $_COOKIE['jalUserName']; } ?>" />
					<?php if (!$use_url) { echo '<span style="display: none">'; } ?>
					<label for="shoutboxurl"><?php _e('URL'); ?>:</label>
					<input type="text" name="shoutboxurl" id="shoutboxurl" value="<?php if ($_COOKIE['jalUrl']) { echo $_COOKIE['jalUrl']; } else { echo 'http://'; } ?>" />
					<?php if (!$use_url) { echo "</span>"; } ?>
					<?php  } echo "\n"; ?>
					<label for="chatbarText"><?php _e('Message') ?>:</label>
					<?php if ($use_textarea) { ?>
					<textarea rows="4" cols="16" name="chatbarText" id="chatbarText" onkeypress="return pressedEnter(this,event);"></textarea>
					<?php } else { ?>
					<input type="text" name="chatbarText" id="chatbarText" />
					<?php } ?>
					<input type="hidden" id="jal_lastID" value="<?php echo $lastID + 1; ?>" name="jal_lastID" />
					<input type="hidden" name="shout_no_js" value="true" />
					<input type="submit" id="submitchat" name="submit" value="<?php _e('Send'); ?>" />
					</p>
				</form>
		<?php } else echo "<p>You must be a registered user to participate in this chat</p>"; ?>
            </div>
<?php }

function jal_admin_options() {
    global $wpdb, $table_prefix, $user_level, $jal_admin_user_level;
    
    // Security
    get_currentuserinfo();
    if ($user_level <  $jal_admin_user_level)
        die('Nice try, you cheeky monkey!');

	// Convert from milliseconds
	$fade_length = $_GET['fade_length'] * 1000;
	$update_seconds = $_GET['update_seconds'] * 1000;

	// Update choices from admin panel
	update_option('shoutbox_fade_from', $_GET['fade_from']);
	update_option('shoutbox_fade_to', $_GET['fade_to']);
	update_option('shoutbox_update_seconds', $update_seconds);
	update_option('shoutbox_fade_length', $fade_length);
	update_option('shoutbox_text_color', $_GET['text_color']);
	update_option('shoutbox_name_color', $_GET['name_color']);
	
	$use_url = ($_GET['use_url']) ? "true" : "";
	$use_textarea = ($_GET['use_textarea']) ? "true" : "";
	$registered_only = ($_GET['registered_only']) ? "1" : "0";

	update_option('shoutbox_use_url', $use_url);
	update_option('shoutbox_use_textarea', $use_textarea);
	update_option('shoutbox_registered_only', $registered_only);

}

function jal_shout_edit() {
    global $wpdb, $table_prefix, $user_level, $jal_admin_user_level;
    
    // Security
    get_currentuserinfo();
    if ($user_level <  $jal_admin_user_level)
        die('Nice try, you cheeky monkey!');

	$wpdb->query("UPDATE ".$table_prefix."liveshoutbox SET text = '".$wpdb->escape($_GET['jal_text'])."' WHERE id = ".$wpdb->escape($_GET['jal_comment_id']));
}

function jal_shout_delete() {
    global $wpdb, $table_prefix, $user_level, $jal_admin_user_level;
    
    // Security
    get_currentuserinfo();
    if ($user_level <  $jal_admin_user_level)
        die('Nice try, you cheeky monkey!');

	$wpdb->query("DELETE FROM ".$table_prefix."liveshoutbox WHERE id = ".$wpdb->escape($_GET['jal_comment_id']));
}

function jal_shout_truncate() {
    global $wpdb, $table_prefix, $user_level, $jal_admin_user_level;
    
    // Security
    get_currentuserinfo();
    if ($user_level <  $jal_admin_user_level)
        die('Nice try, you cheeky monkey!');

	$wpdb->query("TRUNCATE TABLE ".$table_prefix."liveshoutbox");
			
   $welcome_name = "Jalenack";
   $welcome_text = "Your shoutbox is blank. Add a message!";

	$wpdb->query("INSERT INTO ".$table_prefix."liveshoutbox (time,name,text) VALUES ('".time()."','".$welcome_name."','".$welcome_text."')");
}

// If user has updated the admin panel
if (isset($_GET['jal_admin_options']))
    add_action('init', 'jal_admin_options');

// If someone has deleted an entry through the admin panel
if (isset($_GET['jal_delete']))
    add_action('init', 'jal_shout_delete');

// If someone has edited an entry through the admin panel
if (isset($_GET['jal_edit']))
    add_action('init', 'jal_shout_edit');

// If someone has clicked the "delete all" button
if (isset($_GET['jal_truncate']))
    add_action('init', 'jal_shout_truncate');

// Print to the <script> and <link> (for css) to the head of the document
// And adds the admin menu
if (function_exists('add_action')) {
	add_action('wp_head', 'jal_add_to_head');
	add_action('admin_menu', 'shoutbox_admin_page');
	if (strstr($_SERVER['REQUEST_URI'], 'wordspew'))
	   add_action('admin_head', 'jal_add_to_admin_head');
} ?>