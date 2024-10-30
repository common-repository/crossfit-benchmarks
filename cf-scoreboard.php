<?php
/* 
Plugin Name: FireBreather Scoreboard
Plugin URI: http://www.crossfithickory.com/plugins
Version: 0.4
Description: A dynamic scoreboard to show top athlete performances on the CrossFit benchmark workouts.  This is intended to be used with FireBreather Benchmarks.  <strong>Activate CFBenchmarks BEFORE you activate this plugin.</strong>  Only tested on WP version 2.7.
Author: Monty
Author URI: http://www.crossfithickory.com/plugins
*/

/*  Released under GPL:
	http://www.gnu.org/licenses/gpl-3.0-standalone.html
*/

/*
These two settings determine who can see the scoreboard and enter data (user_entry_level),
and who can edit the scoreboard and make changes to other users' data (admin_userlevel).
Key:  0=Subscriber, 1=Contributor, 2/3/4=Author, 5/6/7=Editor, 8/9/10=Administrator
*/
$cfscoreboard_user_entry_level = 0; 
$cfscoreboard_admin_userlevel = 'level_7'; 

/*
===== NO CHANGES BEYOND THIS POINT! ===========================================
*/

global $cfscoreboard_db_version;
$cfscoreboard_db_version = '0.1';
$scoreboard_icon = "<img src=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/images/snatch.png\" align=\"absmiddle\">";
global $image_path;
$image_path = WP_PLUGIN_URL . "/crossfit-benchmarks/images/";


// Installation ===============================================================
function cfscoreboard_install () {
	if(!function_exists(cfbenchmarks_install)) return;
	
	global $wpdb;
	$table_cfscores = $wpdb->prefix . "cf_scores";
	$table_cfwods = $wpdb->prefix . "cf_wods";

	// Set some default options, if they're not already set
	if(!get_option("cfscoreboard")){
		$options["db_version"] = $cfscoreboard_db_version;
		update_option("cfscoreboard", $options);
	}

	// Nip the character set issue in the bud
	if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
		$db_charset = 'utf8';
	$db_charset = "CHARACTER SET ".$db_charset;
	if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
		$db_collate = "COLLATE ".$db_collate;

	// If table name already exists
	if($wpdb->get_var("SHOW TABLES LIKE '$table_cfscores'") == $table_cfscores) {
		// this is how we modify the character set if needed
		//$wpdb->query("ALTER TABLE {$table_cfscores} {$db_charset} {$db_collate}");
		//$wpdb->query("ALTER TABLE {$table_cfscores} MODIFY bm_athlete TEXT {$db_charset} {$db_collate}");
		//this is how we add a column if needed in the future
		//if(!($wpdb->get_results("SHOW COLUMNS FROM {$table_cfscores} LIKE 'column_name'"))) \{
   		//$wpdb->query("ALTER TABLE {$table_cfscores} ADD column_name VARCHAR(255) {$db_charset} {$db_collate} AFTER source");
   	}
	else {
	// If the table isn't there, create it from scratch
		$sql = "CREATE TABLE " . $table_cfscores. " (
			bm_id mediumint(9) NOT NULL AUTO_INCREMENT,
			bm_athlete text NOT NULL,
			bm_date date NOT NULL,
			bm_wod mediumint(9) NOT NULL,
			bm_score float(6,2) NOT NULL,
			bm_rx enum('yes', 'no') DEFAULT 'yes' NOT NULL,
			bm_comment text NULL,
			bm_pr enum('yes', 'no') DEFAULT 'no' NOT NULL,
			PRIMARY KEY  (bm_id)
		) {$db_charset} {$db_collate};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);      
	}

	global $cfscoreboard_db_version;
	$options = get_option('cfscoreboard');
	$options['db_version'] = $cfscoreboard_db_version;
	update_option('cfscoreboard', $options);

}



// Data functions =============================================================
function cfscoreboard_add_bm ($bm_athlete = "", $bm_date, $bm_wod = "", $bm_score = "", $bm_rx = 'yes', $bm_comment = "") {
	if(!$bm_athlete) return __('Nothing added to the database.', 'cfscoreboard');
	
	global $wpdb;
	$sendback = "... <a href =\"" . $_SERVER['HTTP_REFERER'] . "\">GO BACK</a> to the Scoreboard";
	if (strstr($sendback, 'tools.php')) $sendback = "";
	
	$table_name = $wpdb->prefix . "cf_scores";
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
		return __('Database table not found', 'cfscoreboard');
	else //Add the workout data to the database
	{		
		if ( ini_get('magic_quotes_gpc') ) {
		  $bm_athlete = stripslashes($bm_athlete);
		  $bm_date = stripslashes($bm_date);
		  $bm_wod = stripslashes($bm_wod);	
		  $bm_score = stripslashes($bm_score);	
		  $bm_comment = stripslashes($bm_comment);
	  	}
	  	
        if(!$bm_date) {
	        $bm_date = date('Y-m-d', time());
	    }
	    else {
		  	//convert the date string first to php timestamp, then to mysql date format
		  	if(strstr($bm_date, "-")) {
		  	    $d = explode("-", $bm_date);
		  	    $bm_date = implode("/", $d);
		  	}
			$bm_date = strtotime($bm_date);
	        $bm_date = date('Y-m-d', $bm_date);
    	}
        
    	if($bm_date == '1969-12-31') $bm_date = date('Y-m-d', time());
    	
	  	//fix the score string if it's in time (mm:ss) format
	  	if(strstr($bm_score, ":")) {
	  	    $time = explode(":", $bm_score);
	  	    if(count($time) > 2) {
	  	        $mins = ((int)$time['0'] * 60) + (int)$time['1'];
	  	        $time = array($mins, $time['2']);
	  	    }
	        $bm_score = implode(".", $time);
	  	        
	  	}
	  	
		$bm_athlete = "'".$wpdb->escape($bm_athlete)."'";
		$bm_date = "'".$wpdb->escape($bm_date)."'";
		$bm_wod = $bm_wod?"'".$wpdb->escape($bm_wod)."'":"NULL";
		$bm_score = $bm_score?"'".$wpdb->escape($bm_score)."'":"NULL";
		if(!$bm_rx) $bm_rx = "'no'";
		else $bm_rx = "'yes'";
		$bm_comment = $bm_comment?"'".$wpdb->escape($bm_comment)."'":"NULL";
		$insert = "INSERT INTO " . $table_name .
			"(bm_athlete, bm_date, bm_wod, bm_score, bm_rx, bm_comment)" .
			"VALUES ({$bm_athlete}, {$bm_date}, {$bm_wod}, {$bm_score}, {$bm_rx}, {$bm_comment})";

		$results = $wpdb->query( $insert );

		cfscoreboard_setpr($table_name, $bm_athlete, $bm_wod);
		
		if(FALSE === $results)
			return "There was an error: be sure all required fields are filled in properly " . $sendback; // __('There was an error in the MySQL query', 'cfscoreboard');
		else
    		$wpdb->query("UPDATE " . $wpdb->prefix . "cf_wods SET wod_records = wod_records + 1 where wod_id = " . $bm_wod . "");
			return "Score added " . $sendback; //__('Score added', 'cfscoreboard');
	   }
}

function cfscoreboard_edit_bm ($bm_id, $bm_athlete, $bm_date, $bm_wod, $bm_score, $bm_rx = 'yes', $bm_comment = "") {
	if(!$bm_athlete) return __('Score not updated.', 'cfscoreboard');
	if(!$bm_id) return cfscoreboard_addbm($bm_athlete, $bm_date, $bm_wod, $bm_score, $bm_rx, $bm_comment);
	global $wpdb;
	$table_name = $wpdb->prefix . "cf_scores";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
		return __('Database table not found', 'cfscoreboard');
	else //Update database
	{
		
		if ( ini_get('magic_quotes_gpc') ) {
		  $bm_athlete = stripslashes($bm_athlete);
		  $bm_date = stripslashes($bm_date);
		  $bm_wod = stripslashes($bm_wod);	
		  $bm_score = stripslashes($bm_score);	
		  $bm_comment = stripslashes($bm_comment);
	  	}
	  	
        if(!$bm_date) {
	        $bm_date = date('Y-m-d', time());
	    }
	    else {
		  	//convert the date string first to php timestamp, then to mysql date format
		  	if(strstr($bm_date, "-")) {
		  	    $d = explode("-", $bm_date);
		  	    $bm_date = implode("/", $d);
		  	}
			$bm_date = strtotime($bm_date);
	        $bm_date = date('Y-m-d', $bm_date);
    	}
    	
    	if($bm_date == '1969-12-31') $bm_date = date('Y-m-d', time());
    	
	  	//fix the score string if it's in time (mm:ss) format
	  	if(strstr($bm_score, ":")) {
	  	    $time = explode(":", $bm_score);
	  	    $bm_score = implode(".", $time);
	  	}
	  	
		$bm_athlete = "'".$wpdb->escape($bm_athlete)."'";
		$bm_date = "'".$wpdb->escape($bm_date)."'";
		$bm_wod = $bm_wod?"'".$wpdb->escape($bm_wod)."'":"NULL";
		$bm_score = $bm_score?"'".$wpdb->escape($bm_score)."'":"NULL";
		if(!$bm_rx) $bm_rx = "'no'";
		else $bm_rx = "'yes'";
		$bm_comment = $bm_comment?"'".$wpdb->escape($bm_comment)."'":"NULL";
		$update = "UPDATE " . $table_name . "
			SET bm_athlete = {$bm_athlete},
				bm_date = {$bm_date},
				bm_wod = {$bm_wod},
				bm_score = {$bm_score}, 
				bm_rx = {$bm_rx},
				bm_comment = {$bm_comment}
			WHERE bm_id = $bm_id";
		$results = $wpdb->query( $update );
		
		cfscoreboard_setpr($table_name, $bm_athlete, $bm_wod);
		
		if(FALSE === $results)
			return __('There was an error in the MySQL query', 'cfscoreboard');		
		else
			return __('Changes saved', 'cfscoreboard');
   }
}

function cfscoreboard_delete_bm ($bm_id) {
	if($bm_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . "cf_scores";

	$bm_wod = $wpdb->get_var("SELECT bm_wod FROM " . $table_name . " WHERE bm_id = " . $bm_id);
	$bm_athlete = $wpdb->get_var("SELECT bm_athlete FROM " . $table_name . " WHERE bm_id = " . $bm_id);
    $bm_athlete = "'" . $bm_athlete . "'";
	
	$sql = "DELETE from " . $table_name .
		" WHERE bm_id = " . $bm_id;
		
	$results = $wpdb->query( $sql );
	
    cfscoreboard_setpr($table_name, $bm_athlete, $bm_wod);

	if(FALSE === $results)
		return __('There was an error in the MySQL query', 'cfscoreboard');		
	else
    	$wpdb->query("UPDATE " . $wpdb->prefix . "cf_wods SET wod_records = wod_records - 1 where wod_id = " . $bm_wod . "");
		return __('Score deleted', 'cfscoreboard');
	}
	else return __('The score cannot be deleted', 'cfscoreboard');	

}

function cfscoreboard_getbmdata($bm_id) {
	global $wpdb;

	$sql = "SELECT bm_id, bm_athlete, UNIX_TIMESTAMP(bm_date) as bm_date, " . $wpdb->prefix . "cf_wods.wod_id, " . $wpdb->prefix . "cf_wods.wod_name, bm_score, bm_rx, bm_comment 
   		FROM  " . $wpdb->prefix . "cf_scores
   		INNER JOIN " . $wpdb->prefix . "cf_wods 
		ON " . $wpdb->prefix . "cf_scores.bm_wod = " . $wpdb->prefix . "cf_wods.wod_id 
		WHERE bm_id = {$bm_id}";

	$bm_data = $wpdb->get_row($sql, ARRAY_A);
	return $bm_data;	
}

function cfscoreboard_changebmrx($bm_ids, $bm_rx = 'yes'){
	if(!$bm_ids)
		return __('Nothing done!', 'cfscoreboard');
	global $wpdb;
	$sql = "UPDATE ".$wpdb->prefix."cf_scores 
		SET bm_rx = '".$bm_rx."'
		WHERE bm_id IN (".implode(', ', $bm_ids).")";
	$wpdb->query($sql);
	return sprintf(__("Rx status of selected workouts set to '%s'", 'cfscoreboard'), $bm_rx);
}

function cfscoreboard_bulkbmdelete($bm_ids){
	if(!$bm_ids)
		return __('Nothing done!', 'cfscoreboard');
	global $wpdb;
	$sql = "DELETE FROM ".$wpdb->prefix."cf_scores 
		WHERE bm_id IN (".implode(', ', $bm_ids).")";
	$wpdb->query($sql);
	return __('Score(s) deleted', 'cfscoreboard');
}

function cfscoreboard_count_bm($condition = ""){
	global $wpdb;
	$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "cf_scores ".$condition;
	$count = $wpdb->get_var($sql);
	return $count;
}

function cfscoreboard_wod_lookup() {
	global $wpdb;
	global $wod_lookup;
	
	$sql = "SELECT wod_id, wod_type, wod_name, wod_description, wod_reps, wod_show
		FROM " . $wpdb->prefix . "cf_wods
		ORDER BY wod_type, wod_name";
	
	$wod_lookup = $wpdb->get_results($sql);
}

function cfscoreboard_setpr($table_name, $bm_athlete, $bm_wod) {
    global $wpdb;
    
	// first set all this athlete's scores for this wod to 'no' pr	
	$set_pr = $wpdb->query("UPDATE " . $table_name . " SET bm_pr = 'no' WHERE bm_athlete = " . $bm_athlete . " AND bm_wod = " . $bm_wod . " AND bm_pr = 'yes'" );

	// determine how we sort the scores for this WOD... 
	$sort = $wpdb->get_var("SELECT wod_sort FROM " . $wpdb->prefix . "cf_wods WHERE wod_id = " . $bm_wod . " ");

	// now find this athlete's min/max score for this WOD... 
    $pr = $wpdb->get_var("SELECT bm_id FROM " . $table_name . " WHERE bm_athlete = " . $bm_athlete . " AND bm_wod = " . $bm_wod . " ORDER BY bm_score " . $sort );	
    
	// ... and set its 'pr' value to 'yes'
	if($pr) $update = $wpdb->query("UPDATE " . $table_name . " SET bm_pr = 'yes' WHERE bm_id = " . $pr );
	
}


// Admin page =================================================================

function cfscoreboard_versioncheck(){
//	Make sure we don't need to run install again
	global $cfscoreboard_db_version;
	$options = get_option('cfscoreboard');
	if($options['db_version'] != $cfscoreboard_db_version ) 
		cfscoreboard_install();
	
}

function cfscoreboard_manage_scores(){

	cfscoreboard_versioncheck();
	global $scoreboard_icon;
	global $image_path;
	global $cfscoreboard_admin_userlevel;
	
	if($_REQUEST['submit'] == __('Add workout', 'cfscoreboard')) {
		extract($_REQUEST);
		$msg = cfscoreboard_add_bm($bm_athlete, $bm_date, $bm_wod, $bm_score, $bm_rx, $bm_comment);
	}
	else if($_REQUEST['submit'] == __('Save changes', 'cfscoreboard')) {
		extract($_REQUEST);
		$msg = cfscoreboard_edit_bm($bm_id, $bm_athlete, $bm_date, $bm_wod, $bm_score, $bm_rx, $bm_comment);
	}
	else if($_REQUEST['action'] == 'editbm') {
		$display .= "<div class=\"wrap\">\n<h2>" . $scoreboard_icon . "FireBreather Scoreboard &raquo; ".__('Edit workout', 'cfscoreboard')."</h2>";
		$display .=  cfscoreboard_editbm_form($_REQUEST['id']);
		$display .= "</div>";
		echo $display;
		return;
	}
	else if($_REQUEST['action'] == 'delbm') {
		$msg = cfscoreboard_delete_bm($_REQUEST['id']);
	}
	else if($_REQUEST['action'] == 'manage_wods') {
		cfscoreboard_management(1);
	}
	else if(isset($_REQUEST['bulkaction']))  {
		if($_REQUEST['bulkaction'] == __('Delete', 'cfscoreboard')) 
			$msg = cfscoreboard_bulkbmdelete($_REQUEST['bulkcheck']);
		if($_REQUEST['bulkaction'] == __('Make Rx', 'cfscoreboard')) {
			$msg = cfscoreboard_changebmrx($_REQUEST['bulkcheck'], 'yes');
		}
		if($_REQUEST['bulkaction'] == __('Make non-Rx', 'cfscoreboard')) {
			$msg = cfscoreboard_changebmrx($_REQUEST['bulkcheck'], 'no');
		}
	}
	
	$display .= "<div class=\"wrap\">";
	
	if($msg)
		$display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";

	$display .= "<h2>" . $scoreboard_icon . "FireBreather Scoreboard</h2>";

    $user = wp_get_current_user();
    $user_ID = $user->ID;
    $user_info = get_userdata($user_ID);
    $bm_athlete = $user_info->first_name . ' ' . $user_info->last_name;

	// Get all the workouts from the database
	global $wpdb;

    $sql = "SELECT bm_id, bm_athlete, UNIX_TIMESTAMP(bm_date) as bm_date, " . $wpdb->prefix . "cf_wods.wod_id, " . $wpdb->prefix . "cf_wods.wod_name, bm_score, bm_rx, bm_comment, bm_pr 
   		FROM  " . $wpdb->prefix . "cf_scores
   		INNER JOIN " . $wpdb->prefix . "cf_wods 
		ON " . $wpdb->prefix . "cf_scores.bm_wod = " . $wpdb->prefix . "cf_wods.wod_id"; 

    if(!current_user_can('level_7'))
		$sql .= " AND " . $wpdb->prefix . "cf_scores.bm_athlete = '" . $bm_athlete . "'";


	if(isset($_REQUEST['orderby'])) {
		$sql .= " ORDER BY " . $_REQUEST['criteria'] . " " . $_REQUEST['order'];
		$option_selected[$_REQUEST['criteria']] = " selected=\"selected\"";
		$option_selected[$_REQUEST['order']] = " selected=\"selected\"";
	}
	else {
		$sql .= " ORDER BY bm_id DESC";
		$option_selected['bm_id'] = " selected=\"selected\"";
		$option_selected['DESC'] = " selected=\"selected\"";
	}

	if(isset($_REQUEST['limit'])) {
		$sql .= " " . $_REQUEST['limit'];
		$option_selected[$_REQUEST['limit']] = " selected=\"selected\"";
	}
	else {
		$sql .= " LIMIT 0, 20";
		$option_selected[' LIMIT 0, 20'] = " selected=\"selected\"";
	}


	$bm = $wpdb->get_results($sql);
	
	global $wod_lookup;
	cfscoreboard_wod_lookup;
	
	foreach($bm as $bm_data) {
		if($alternate) $alternate = "";
		else $alternate = " class=\"alternate\"";
		$bm_list .= "<tr{$alternate}>";
		$bm_list .= "<td class=\"check-column\"><input type=\"checkbox\" name=\"bulkcheck[]\" value=\"".$bm_data->bm_id."\" /></td>";
		$bm_list .= "<td>" . $bm_data->bm_id . "</td>";
		$bm_list .= "<td>" . wptexturize(nl2br($bm_data->bm_athlete)) ."</td>";
		$bm_list .= "<td>" . date('m/d/Y', $bm_data->bm_date) ."</td>";
		$bm_list .= "<td>" . wptexturize(nl2br($bm_data->wod_name)) ."</td>";
		$bm_list .= "<td>" . wptexturize(nl2br($bm_data->bm_score)) ."</td>";
		$bm_list .= "<td>" . $bm_data->bm_rx ."</td>";
		$bm_list .= "<td>" . wptexturize(nl2br($bm_data->bm_comment)) ."</td>";
		$bm_list .= "<td>" . $bm_data->bm_pr ."</td>";
		$bm_list .= "<td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=crossfit-benchmarks/cf-scoreboard.php&action=editbm&amp;id=".$bm_data->bm_id."\" class=\"edit\">".__('Edit', 'cfscoreboard')."</a></td>
                    <td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=crossfit-benchmarks/cf-scoreboard.php&action=delbm&amp;id=".$bm_data->bm_id."\" onclick=\"return confirm( '".__('Are you sure you want to delete this workout?', 'cfscoreboard')."');\" class=\"delete\">".__('Delete', 'cfscoreboard')."</a> </td>";
		$bm_list .= "</tr>";
	}
	
	// anchor to add new workout
	$navlinks .= " | <a href=\"#addscore\"><strong>".__('Add workout', 'cfscoreboard')."</strong></a> ";

	if(current_user_can( $cfscoreboard_admin_userlevel )) {
        $navlinks .= " | <a href=\"#TB_inline?height=400&width=600&inlineId=options\" class=\"thickbox\" title=\"FireBreather Benchmarks\"><strong>".__('Options', 'cfbenchmarks')."</strong></a> ";
    	$navlinks .= " | <a href=\"#TB_inline?height=400&width=600&inlineId=instructions\" class=\"thickbox\" title=\"FireBreather Scoreboard\"><strong>".__('Instructions', 'cfscoreboard')."</strong></a> ";
	    $navlinks .= " | <a href=\"" . $_SERVER['PHP_SELF'] . "?page=crossfit-benchmarks/cf-benchmarks.php\"><strong>Manage Benchmark Workouts</strong></a>";
    }
	
	if($bm_list) {
		$display .= "<p>";
	$bm_count = cfscoreboard_count_bm();
	$display .= sprintf(__ngettext('Currently, you have %d workout', 'Currently, you have %d workouts', $bm_count, 'cfscoreboard'), $bm_count);
	$display .= $navlinks;
	$display .= "</p>";

		$display .= "<form id=\"cfscoreboard\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}?page=crossfit-benchmarks/cf-scoreboard.php\">";
		$display .= "<div class=\"tablenav\">";
		$display .= "<div class=\"alignleft actions\">";
//		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Delete', 'cfscoreboard')."\" class=\"button-secondary\" />";
		if(current_user_can( $cfscoreboard_admin_userlevel )) $display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make Rx', 'cfscoreboard')."\" class=\"button-secondary\" />";
		if(current_user_can( $cfscoreboard_admin_userlevel )) $display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make non-Rx', 'cfscoreboard')."\" class=\"button-secondary\" />";
		$display .= "&nbsp;&nbsp;&nbsp;";
		$display .= __('Sort by: ', 'cfscoreboard');
		$display .= "<select name=\"criteria\">";
		$display .= "<option value=\"bm_id\"{$option_selected['bm_id']}>".__('ID', 'cfscoreboard')."</option>";
		$display .= "<option value=\"bm_athlete\"{$option_selected['bm_athlete']}>".__('Athlete', 'cfscoreboard')."</option>";
		$display .= "<option value=\"bm_date\"{$option_selected['bm_date']}>".__('Date', 'cfscoreboard')."</option>";
		$display .= "<option value=\"bm_wod\"{$option_selected['bm_wod']}>".__('Workout', 'cfscoreboard')."</option>";
		$display .= "<option value=\"bm_score\"{$option_selected['bm_score']}>".__('Score', 'cfscoreboard')."</option>";
		$display .= "<option value=\"bm_rx\"{$option_selected['bm_rx']}>".__('Rx', 'cfscoreboard')."</option>";
		$display .= "<option value=\"bm_comment\"{$option_selected['bm_comment']}>".__('Comment', 'cfscoreboard')."</option>";
		$display .= "</select>";
		$display .= "<select name=\"order\"><option{$option_selected['ASC']}>ASC</option><option{$option_selected['DESC']}>DESC</option></select>";
		$display .= "<select name=\"limit\"><option value=\" LIMIT 0, 20\"{$option_selected[' LIMIT 0, 20']}>".__('Last 20 records', 'cfscoreboard')."</option><option value=\" \"{$option_selected[' ']}>".__('Show all records', 'cfscoreboard')."</option></select>";
		$display .= "<input type=\"submit\" name=\"orderby\" value=\"".__('Go', 'cfscoreboard')."\" class=\"button-secondary\" />";
		$display .= "</div>";
		$display .= "<div class=\"clear\"></div>";	
		$display .= "</div>";
		

		
		$display .= "<table class=\"widefat\">";
		$display .= "<thead><tr>
			<th class=\"check-column\"><input type=\"checkbox\" onclick=\"cfscoreboard_checkAll(document.getElementById('cfscoreboard'));\" /></th>
			<th>ID</th>
			<th>".__('Athlete', 'cfscoreboard')."</th>
			<th>".__('Date', 'cfscoreboard')."</th>
			<th>".__('Workout', 'cfscoreboard')."</th>
			<th>".__('Time', 'cfscoreboard')." / ".__('Reps', 'cfscoreboard')." / ".__('Load', 'cfscoreboard')."</th>
			<th>".__('Rx?', 'cfscoreboard')."</th>
			<th>".__('Comment', 'cfscoreboard')."</th>
			<th>".__('PR', 'cfscoreboard')."</th>
		    <th colspan=\"2\" style=\"text-align:center\">".__('Action', 'cfscoreboard')."</th>";
		$display .= "</tr></thead>";
		$display .= "<tbody id=\"the-list\">{$bm_list}</tbody>";
		$display .= "</table>";


		$display .= "<div class=\"tablenav\">";
		$display .= "<div class=\"alignleft actions\">";
//		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Delete', 'cfscoreboard')."\" class=\"button-secondary\" />";
		if(current_user_can( $cfscoreboard_admin_userlevel )) $display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make Rx', 'cfscoreboard')."\" class=\"button-secondary\" />";
		if(current_user_can( $cfscoreboard_admin_userlevel )) $display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make non-Rx', 'cfscoreboard')."\" class=\"button-secondary\" />";
		$display .= "</div>";

		$display .= "</div>";
		$display .= "</form>";
		$display .= "<br style=\"clear:both;\" />";

	}
	else{
		$display .= "<p>".__('Currently, you have no workout data', 'cfscoreboard')."";
		$display .= $navlinks;
		$display .= "</p>";
	}


	$display .= "</div>";
	
	$display .= "<div id=\"addscore\" class=\"wrap\">\n<h2>".__('Add workout', 'cfscoreboard')."</h2>";
	$display .= "*Required fields are in <strong>BOLD</strong>";
	if($msg)
		$display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";
	$display .= cfscoreboard_editbm_form();
	$display .= "</div>";

	
	// This is the hidden content showing the instructions.
	$display .= "<div class=\"wrap\" id=\"instructions\" style=\"display:none;\">";
	$display .= "<h2>FireBreather Scoreboard &raquo; Instructions</h2>";
	$display .= "<p>Thanks for using FireBreather Scoreboard.  This plugin is works in conjuction with the FireBreather Benchmarks plugin to enable tracking of athlete performance records on the benchmark WODs.";
	$display .= "<p>To insert the Scoreboard into your page or post, just click on the kettlebell <img alt=\"\" src=\"" . $image_path . "cfbenchmarks.gif\" /> button in the page/post editing window.  In the window that pops up, select the \"Scoreboard\" tab, make sure the box is checked, and click \"Insert\". If you prefer, you can type the following shortcode:</p>";
	$display .= "<blockquote><span style=\"font-family:courier\">[cf-scoreboard rx=yes userinput=yes]</span></blockquote>";
	$display .= "<p>When you publish the post, the workout data will show up properly formatted, including the exercises, reps, special instructions, and even the associated picture/memorial data for the 'Hero' workouts.  Please note that <em>the shortcode is all that you will see when you're editing the post</em> - the actual data will show up in place of this shortcode when you view the published page/post.</p>";
	$display .= "<p>The variables \"rx\" and \"userinput\" are optional.  If \"rx\" is set to yes, then only workouts completed \"As Rx\" will be displayed on the scoreboard.  Scaled workout data will not be shown.  This is a useful option if, for example, you want to display two scoreboards &mdash; one for Rx and one for scaled workouts.</p>";
	$display .= "<p>If the variable \"userinput\" is set to yes, then registered users who are logged in to your site will have the opportunity to input their own data to the scoreboard.  This is really only useful if your web site allows user registration.</p>";
	$display .= "<p>For more information, and to post comments, go to the plugins page at <a href=\"http://crossfithickory.com/plugins\">CrossFit Hickory</a>.</p>";
	$display .= "<p>&mdash; Monty</p>";
	$display .= "</div>";
	
	
	// This function calls the hidden content showing the options page, if needed in the future.
    if(function_exists(cfbenchmarks_options)) $display .= cfbenchmarks_options();


	echo $display;
	
        
}


// Data entry  ================================================================

function cfscoreboard_editbm_form ($bm_id = 0) {
	// table variables: ($bm_athlete, $bm_date, $bm_wod, $bm_score, $bm_rx, $bm_comment)
	$bm_rx_selected = " checked=\"checked\"";
	$submit_value = __('Add workout', 'cfscoreboard');
	$form_name = "addscore";
	$action_url = $_SERVER['PHP_SELF']."?page=crossfit-benchmarks/cf-scoreboard.php#addscore";
    global $cfscoreboard_admin_userlevel;
    if(!current_user_can( $cfscoreboard_admin_userlevel )) $read_only = 'readonly';
	global $user_identity;
	$custom_cat = get_option('cfbm_custom');
    $user = wp_get_current_user();
    $user_ID = $user->ID;
    $user_info = get_userdata($user_ID);
    $bm_athlete = $user_info->first_name . ' ' . $user_info->last_name;
    $bm_date = date('m/d/Y', time());

    if($bm_id) {
        if($bm_id == 'scoreboard') {
            	$action_url = get_settings('siteurl'). "/wp-admin/tools.php?page=crossfit-benchmarks/cf-scoreboard.php";
//              $bm_athlete = $user_identity;  this results in some strange looking entries on the scoreboard - best to go with the user's name
				$read_only = 'readonly';
        }
        else {
            $form_name = "editscore";
            $bm_data = cfscoreboard_getbmdata($bm_id);
            foreach($bm_data as $key => $value)
                $bm_data[$key] = $bm_data[$key];
            extract($bm_data);
            $bm_athlete = htmlspecialchars($bm_athlete);
			$bm_date = date('m/d/Y', $bm_date);
            $bm_wod = htmlspecialchars($bm_wod);
            $bm_score = htmlspecialchars($bm_score);
            $hidden_input = "<input type=\"hidden\" name=\"bm_id\" value=\"{$bm_id}\" />";
            if($bm_rx == 'no') $bm_rx_selected = "";
            $submit_value = __('Save changes', 'cfscoreboard');
            $back = "<input type=\"submit\" name=\"submit\" value=\"".__('Back', 'cfscoreboard')."\" />&nbsp;";
            $action_url = $_SERVER['PHP_SELF']."?page=crossfit-benchmarks/cf-scoreboard.php";
        }
    }

	$bm_athlete_label = __('<strong>Athlete name</strong>', 'cfscoreboard');
	$bm_date_label = __('<strong>Date</strong>', 'cfscoreboard');
	$bm_wod_label = __('<strong>Workout name</strong>', 'cfscoreboard');
	$bm_score_label = __('<strong>Workout result</strong>', 'cfscoreboard');
	$bm_rx_label = __('<strong>As Rx?</strong>', 'cfscoreboard');
	$bm_comment_label = __('Comment <em> (optional)</em>', 'cfscoreboard');
	
	// Get all the WODS from the database
	global $wpdb;
    
	$sql .= "SELECT wod_id, wod_type, wod_name, wod_description, wod_reps, wod_show
		FROM " . $wpdb->prefix . "cf_wods ";
	if(!$custom_cat) $sql .= "WHERE wod_type != 'Custom' ";
	$sql .= "ORDER BY wod_type, wod_name";
	
	$wods = $wpdb->get_results($sql);
	
	foreach($wods as $wod_data) {
		if($bm_data['wod_id'] == $wod_data->wod_id){
			$wods_list .= "<option value=\"" . $wod_data->wod_id . "\" selected=\"selected\">" . $wod_data->wod_type . " | " . $wod_data->wod_name . "\n ";
		}
		else{
			$wods_list .= "<option value=\"" . $wod_data->wod_id . "\">" . $wod_data->wod_type . " | " . $wod_data->wod_name . "\n ";
		}
	}
    	
    if($read_only == 'readonly' ) $read_only1 = ' <br /><small>Your profile name (cannot be changed).</small>';

   	$athlete_name = "<tr class=\"form-field form-required\"><th style=\"text-align:left;\" scope=\"row\" valign=\"top\"><label for=\"cfscoreboard_athlete\">{$bm_athlete_label}:</label></th><td><input type=\"text\" id=\"cfscoreboard_athlete\" name=\"bm_athlete\" size=\"30\" value=\"{$bm_athlete}\" " . $read_only . " />" . $read_only1 . "</td></tr>";

	$display_wod = "<tr class=\"form-field form-required\"><th style=\"text-align:left;\" scope=\"row\" valign=\"top\"><label for=\"cfscoreboard_wod\">{$bm_wod_label}:</label></th><td><select name=\"bm_wod\">$wods_list</select><br /><small></small></td></tr>";	
	
	$display .=<<< EDITFORM
<form name="{$form_name}" method="post" action="{$action_url}">
	{$hidden_input}
	<table class="form-table" cellpadding="5" cellspacing="2" width="100%">
		<tbody>
        {$athlete_name}

		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfscoreboard_date">{$bm_date_label}:</label></th>
			<td><input type="text" id="cfscoreboard_date" name="bm_date" size="30" value="{$bm_date}" /> <br /><small>Enter the date in M/D/Y format.</small></td>
		</tr>

		{$display_wod}
		
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfscoreboard_score">{$bm_score_label}:</label></th>
			<td><input type="text" id="cfscoreboard_score" name="bm_score" size="30" value="{$bm_score}" /> <br /><small>Enter ONE NUMBER ONLY (number of reps, pounds, etc).  Enter times as <strong>mm:ss</strong> or <strong>h:mm:ss</strong>.  Use comments as needed.</small></td>
		</tr>
		<tr>
			<th style="text-align:left;" scope="row" valign="top"><label for="cfscoreboard_rx">{$bm_rx_label}</label></th>
			<td><input type="checkbox" id="cfscoreboard_rx" name="bm_rx"{$bm_rx_selected} /> <br /><small>If scaled, specify how workout was modified in comments.</small></td>
		</tr>
		<tr class="form-field">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfscoreboard_comment">{$bm_comment_label}:</label></th>
			<td><input type="text" id="cfscoreboard_comment" name="bm_comment" size="30" value="{$bm_comment}" /> <br /><small>Examples: list times for each round of Barbara, pullups per round of Nicole, body weight for strength/olympic records, etc.</small></td>
		</tr></tbody>
	</table>
	<p class="submit">{$back}<input name="submit" value="{$submit_value}" type="submit" class="button" /></p>
</form>
EDITFORM;
    if($read_only) {
        if(!$user_info->first_name || !$user_info->last_name) $display = "<br />You have to tell us your name before we can enter your data to the scoreboard.  <a href=\"". admin_url('profile.php') . "\"><em>Click here</em></a> to edit your profile &mdash; enter your FIRST and LAST name &mdash; then come back and post your scores!";
        }
	return $display;

}


function cfscoreboard_shortcode($attr) {
	
    global $wpdb;
    global $user_identity;
	$show_rx = "'".$attr['rx']."'";
	$user_input = $attr['userinput'];
	$custom_cat = get_option('cfbm_custom');
	
	if($show_rx == "'no'"){
		$show_rx = "&rx=no";
		}
	else {
		$show_rx = "&rx=yes";
		}	
		
    $sb .= "<!-- FireBreather Scoreboard --> \n";
    $sb .= "<div class=\"demo-show\">";
    $sb .= "<p>Choose a category to see the benchmark workouts.  Workouts with performance data are marked by *.</p>";
    $sb .= "<h3>&raquo; The \"Girls\" of CrossFit</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Girls' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";
    
    $sb .= "<h3>&raquo; The CrossFit Heroes</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Heroes' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";
    
    $sb .= "<h3>&raquo; Other Named Workouts</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Named' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";
    
    $sb .= "<h3>&raquo; Strength/Powerlifting</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Strength' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";
    
    $sb .= "<h3>&raquo; Olympic Weightlifting</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Olympic' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";
    
    $sb .= "<h3>&raquo; CrossFit Endurance</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Endurance' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";
    
    $sb .= "<h3>&raquo; Gymnastics/Bodyweight</h3>";
    $sb .= "<div><ul>";
        $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Gymnastics' AND wod_show = 'yes' ORDER BY wod_name");
        if(is_array($wodlist)) {
            foreach($wodlist as $wod) {
                $wod_records = "";
                if($wod->wod_records > 0) $wod_records = "*";
                $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
            }
        }
    $sb .= "</ul><br /></div>";

    if($custom_cat) {
        $sb .= "<h3>&raquo; " . $custom_cat . "</h3>";
        $sb .= "<div><ul>";
            $wodlist = $wpdb->get_results("SELECT wod_id, wod_name, wod_records FROM " . $wpdb->prefix . "cf_wods WHERE wod_type = 'Custom' AND wod_show = 'yes' ORDER BY wod_name");
            if(is_array($wodlist)) {
                foreach($wodlist as $wod) {
                    $wod_records = "";
                    if($wod->wod_records > 0) $wod_records = "*";
                    $sb .= "<li><a href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/scores.php?wod=" . $wod->wod_id . $show_rx . "&?height=600&width=400\" class=\"thickbox\" title=\"Results for " . $wod->wod_name . "\">" . $wod->wod_name . " " . $wod_records . "</a></li> \n";
                }
            }
        $sb .= "</ul><br /></div>";
    }
    
    if($show_rx == "&rx=yes") $sb.= "<p><small>This scoreboard is set to display ONLY workouts completed &ldquo;as Rx&rdquo;.</small></p>";
    
	if( is_user_logged_in() && $user_input=="yes" ) {
	    $sb .= "&nbsp;<br />";
	    $sb .= "<p>Hey, " . $user_identity . "!  Have you performed one of these benchmark workouts with 'top 10' results?  Then you belong on this scoreboard!</p>";
	    $sb .= "<h3>&raquo; Enter YOUR workout stats here</h3>";
	    $sb .= "<div>";
	    $sb .= cfscoreboard_editbm_form(scoreboard);
	    if($show_rx == "&rx=yes") $sb .= "<p><small><strong>Note:</strong> this scoreboard is currently set to display only those workouts which were completed \"As Rx\" &mdash; scaled workouts will not be displayed.</small></p>";
	    $sb .= "</div>";
	}


    $sb .= "</div>";
    $sb .= "<!-- FireBreather Scoreboard --> \n";
    
	
    return $sb;

}
add_shortcode('cf-scoreboard', 'cfscoreboard_shortcode');


// Thickbox ===================================================================
// We're using the built-in jQuery and Thickbox that comes with WordPress
function cfscoreboard_header(){
	$cfbm_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) );
    wp_enqueue_script('jquery');
    wp_enqueue_script('cfbm_more_show_script', $cfbm_plugin_url.'/js/more-show.js', array('jquery', 'thickbox'));
    wp_enqueue_script('ui_datepicker', $cfbm_plugin_url.'/js/jquery-ui-1.7.custom.min.js', array('jquery'));

}

function cfscoreboard_wp_head() {
	$cfhead .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/css/ui-darkness/jquery-ui-1.7.custom.css\" /> \n";
	$cfhead .= "<script type=\"text/javascript\">jQuery(function() {jQuery(\"#cfscoreboard_date\").datepicker({inline: true});});</script> \n";
	echo $cfhead;
}

add_action('wp_print_scripts', 'cfscoreboard_header');
add_action('wp_head', 'cfscoreboard_wp_head', 11);



// Admin menu =================================================================

function cfscoreboard_admin_menu() {
	global $cfscoreboard_user_entry_level;
	
	add_management_page('FireBreather Scoreboard', 'FireBreather Scoreboard', $cfscoreboard_user_entry_level, __FILE__, 'cfscoreboard_manage_scores');
	
}
add_action('admin_menu', 'cfscoreboard_admin_menu');



register_activation_hook( __FILE__, 'cfscoreboard_install' );
?>