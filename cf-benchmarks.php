<?php
/* 
Plugin Name: FireBreather Benchmarks
Plugin URI: http://www.crossfithickory.com/plugins
Version: 0.4
Description: Simplify the posting of standard CrossFit workouts that show up repeatedly in the hopper.  You can also track top performances when used with the FireBreather Scoreboard plugin.  This has only been tested in WP version 2.7.
Author: Monty
Author URI: http://www.crossfithickory.com/plugins
*/

/*  Released under GPL:
	http://www.gnu.org/licenses/gpl-3.0-standalone.html
*/

/*
This setting determines who can see and manage the benchmark workouts (admin_userlevel).
Key:  0=Subscriber, 1=Contributor, 2/3/4=Author, 5/6/7=Editor, 8/9/10=Administrator
*/
$cfbenchmarks_admin_userlevel = 7;

/*
===== NO CHANGES BEYOND THIS POINT! ===========================================
*/

global $cfbenchmarks_db_version;
$cfbenchmarks_db_version = '0.2';
$benchmark_icon = "<img src=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/images/cfbenchmarks.png\" align=\"absmiddle\">";
global $image_path;
$image_path = WP_PLUGIN_URL . "/crossfit-benchmarks/images/";


// Installation ===============================================================
function cfbenchmarks_install () {
	global $wpdb;
	$table_cfscores = $wpdb->prefix . "cf_scores";
	$table_cfwods = $wpdb->prefix . "cf_wods";

	// Set some default options, if they're not already set
	if(!get_option("cfbenchmarks")){
//		$options = array( 'db_version' => $cfbenchmarks_db_version , 'sort_type' => 'Girls', 'css' => 'cf-benchmarks.css' );
		$options["db_version"] = $cfbenchmarks_db_version;
		update_option("cfbenchmarks", $options);
	}

	if(!get_option("cfbm_sort")){
		$options1["sort_type"] = 'Girls';
		update_option("cfbm_sort", $options1);
	}

	if ( FALSE === get_option('cfbm_css') ) add_option( 'cfbm_css', 'cf-benchmarks.css' );
	if ( FALSE === get_option('cfbm_custom') ) add_option( 'cfbm_custom', '' );

    // fix my bone-headed custom category default error (version 0.2)
    if(get_option('cfbm_custom') == 'CrossFit Hickory') update_option( 'cfbm_custom', '' );

	// Nip the character set issue in the bud
	if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
		$db_charset = 'utf8';
	$db_charset = "CHARACTER SET ".$db_charset;
	if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
		$db_collate = "COLLATE ".$db_collate;

	// If table name already exists
	if($wpdb->get_var("SHOW TABLES LIKE '$table_cfwods'") == $table_cfwods) {
		// this is how we change the character set if needed
		//$wpdb->query("ALTER TABLE {$table_cfwods} {$db_charset} {$db_collate}");
		//$wpdb->query("ALTER TABLE {$table_cfwods} MODIFY wod_name TEXT {$db_charset} {$db_collate}");
		//this is how we add a column if needed in the future
		//if(!($wpdb->get_results("SHOW COLUMNS FROM {$table_cfwods} LIKE 'column_name'"))) \{
   		//$wpdb->query("ALTER TABLE '{$table_cfwods}' ADD 'column_name' VARCHAR(255) {$db_charset} {$db_collate} AFTER 'source'");

   		//We need to add the 'Custom' wod_type to bring the beta testers up to speed (0.1 -> 0.2)
   		$wpdb->query("ALTER TABLE {$table_cfwods} MODIFY wod_type enum('Girls', 'Heroes', 'Named', 'Olympic', 'Strength', 'Endurance', 'Gymnastics', 'Custom') DEFAULT 'Girls' NOT NULL");
	}
	else {
	// If the table isn't there, create it from scratch
		$sql = "CREATE TABLE " . $table_cfwods. " (
			wod_id mediumint(9) NOT NULL AUTO_INCREMENT,
			wod_type enum('Girls', 'Heroes', 'Named', 'Olympic', 'Strength', 'Endurance', 'Gymnastics', 'Custom') DEFAULT 'Girls' NOT NULL,
			wod_name text NOT NULL,
			wod_pic text NULL,
			wod_story text NULL,
			wod_description text NOT NULL,
			wod_reps text NOT NULL,
			wod_notes text NULL,
			wod_video text NULL,
			wod_sort enum('asc', 'desc') DEFAULT 'asc' NOT NULL,
			wod_show enum('yes', 'no') DEFAULT 'yes' NOT NULL,
			wod_records mediumint(9) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (wod_id)
		) {$db_charset} {$db_collate};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);      

		// Add the benchmark WODS (current as of January 2009)
		$newline=chr(13).chr(10);
		global $image_path;
		$insert = "INSERT INTO " . $table_cfwods .
		" (wod_type, wod_name, wod_pic, wod_story, wod_description, wod_reps, wod_notes, wod_video, wod_sort) VALUES " .
		" ( 'Girls' , 'Angie', '', '', '100 pull-ups" . $newline . "100 push-ups" . $newline . "100 sit-ups" . $newline . "100 squats', 'For time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Annie', '', '', 'Double-unders" . $newline . "Sit-ups', '50/40/30/20/10 rep rounds, for time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Barbara', '', '', '20 pull-ups" . $newline . "30 push-ups" . $newline . "40 sit-ups" . $newline . "50 squats', '5 rounds, each for time', 'Rest precisely three minutes between each round.  Post time for each of five rounds.' , '', 'asc' ), " .
		" ( 'Girls' , 'Chelsea', '', '', '5 pull-ups" . $newline . "10 push-ups" . $newline . "15 squats', 'Each minute on the minute for 30 minutes', '' , '', 'desc' ), " .
		" ( 'Girls' , 'Cindy', '', '', '5 pull-ups" . $newline . "10 push-ups" . $newline . "15 squats', 'As many rounds as possible in 20 minutes', '' , 'http://media.crossfit.com/cf-video/CrossFit_ValenciaMiniCindyWOD.mov', 'desc' ), " .
		" ( 'Girls' , 'Diane', '', '', 'Deadlift 225 lb" . $newline . "Handstand push-ups', '21-15-9 reps, for time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Elizabeth', '', '', 'Clean 135 lb" . $newline . "Ring dips', '21-15-9 reps, for time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Eva', '', '', 'Run 800 meters" . $newline . "30 Kettlebell swings, 2 pd" . $newline . "30 pull-ups', '5 rounds for time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Fran', '', '', 'Thruster 95 lb" . $newline . "Pull-ups', '21-15-9 reps, for time', '' , 'http://media.crossfit.com/cf-video/CrossFit_HardCoreFLCertFran.mov', 'asc' ), " .
		" ( 'Girls' , 'Grace', '', '', 'Clean & jerk 135 lb', '30 reps for time', '' , 'http://media.crossfit.com/cf-video/CrossFit_SCL1ToshDoesGrace.mov', 'asc' ), " .
		" ( 'Girls' , 'Helen', '', '', '400 meter run" . $newline . "21 Kettlebell swings, 1.5 pd" . $newline . "12 pull-ups', '3 rounds for time', '' , 'http://media.crossfit.com/cf-video/CrossFit_HelenDemo.mov', 'asc' ), " .
		" ( 'Girls' , 'Isabel', '', '', 'Snatch 135 lb', '30 reps for time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Jackie', '', '', '1000 meter row" . $newline . "50 thrusters, 45 lb" . $newline . "30 pull-ups', 'For time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Karen', '', '', '150 wall ball shots', 'For time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Kelly', '', '', 'Run 400 meters" . $newline . "30 box jumps, 24 inches" . $newline . "30 wall ball shots, 20 lb', '5 rounds for time', '' , '', 'asc' ), " .
		" ( 'Girls' , 'Linda', '', '', 'Deadlift 1½ BW" . $newline . "Bench press BW" . $newline . "Clean ¾ BW', '10/9/8/7/6/5/4/3/2/1 rep rounds for time', '' , 'http://media.crossfit.com/cf-video/CrossFitNY_GillianVsLinda.mov', 'asc' ), " .
		" ( 'Girls' , 'Lynne', '', '', 'Bench press BW" . $newline . "Pull-ups', '5 rounds for max reps', '' , '', 'desc' ), " .
		" ( 'Girls' , 'Mary', '', '', '5 Handstand push-ups" . $newline . "10 single-leg squats" . $newline . "15 pull-ups', 'As many rounds as possible in 20 minutes', '' , 'http://media.crossfit.com/cf-video/CrossFit_MillerDoesMaryAt7000.mov', 'desc' ), " .
		" ( 'Girls' , 'Nancy', '', '', '400 meter run" . $newline . "15 overhead squats, 95 lb', '5 rounds for time', '' , 'http://media.crossfit.com/cf-video/CrossFit_NancyDemo.mov', 'asc' ), " .
		" ( 'Girls' , 'Nicole', '', '', '400 meter run" . $newline . "Max rep pull-ups', 'As many rounds as possible in 20 minutes', 'Note the number of pull-ups for each round.' , '', 'desc' ), " .
		" ( 'Heroes' , 'Badger', '" . $image_path . "badger.jpg', 'In honor of Navy Chief Petty Officer Mark Carter, 27, of Virginia Beach, VA who was killed in Iraq 11 December 2007.', '30 Squat cleans, 95 lb" . $newline . "30 Pull-ups" . $newline . "Run 800 meters', '3 rounds for time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Daniel', '" . $image_path . "daniel.jpg', 'Dedicated to Army Sgt 1st Class Daniel Crabtree who was killed in Al Kut, Iraq on Thursday June 8th 2006.', '50 Pull-ups" . $newline . "400 meter run" . $newline . "21 Thrusters, 95 lb" . $newline . "800 meter run" . $newline . "21 Thrusters, 95 lb" . $newline . "400 meter run" . $newline . "50 Pull-ups', 'For time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Erin', '" . $image_path . "ErinD.jpg', 'Canadian Army Master Corporal Erin Doyle, 32, was killed in a firefight August 11th, 2008 in the Panjwaii District, Kandahar Province, Afghanistan. He is survived by his wife Nicole and his daughter Zarine.', '15 Dumbbells split clean, 40 lb" . $newline . "21 Pull-ups', '5 rounds for time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Griff', '" . $image_path . "griff.jpg', 'In honor of USAF SSgt Travis L. Griffin, 28, who was killed April 3, 2008 in the Rasheed district of Baghdad by an IED strike to his vehicle. Travis is survived by his son Elijah.', 'Run 800 meters" . $newline . "Run 400 meters backwards" . $newline . "Run 800 meters" . $newline . "Run 400 meters backwards', 'For time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Jason', '" . $image_path . "Jason_Lewis.jpg', 'S01 (SEAL) Jason Dale Lewis was killed by an IED while conducting combat operations in Southern Baghdad July 6, 2007. We name this workout \'Jason\' in honor of his life, family, and courage.', '100 Squats" . $newline . "5 Muscle-ups" . $newline . "75 Squats" . $newline . "10 Muscle-ups" . $newline . "50 Squats" . $newline . "15 Muscle-ups" . $newline . "25 Squats" . $newline . "20 Muscle-ups', 'For time', '' , 'http://media.crossfit.com/cf-video/CrossFit_MillerDoesJason.mov', 'asc' ), " .
		" ( 'Heroes' , 'Josh', '" . $image_path . "joshhagar180.jpg', 'SSG Joshua Hager, United States Army, was killed Thursday February 22 2007 in Ar Ramadi, Iraq. ', '21 Overhead squats, 95 lb" . $newline . "42 Pull-ups" . $newline . "15 Overhead squats, 95 lb" . $newline . "30 Pull-ups" . $newline . "9 Overhead squats, 95 lb" . $newline . "18 Pull-ups', 'For time', '' , 'http://media.crossfit.com/cf-video/CrossFit_AnnieDoesJosh.mov', 'asc' ), " .
		" ( 'Heroes' , 'Joshie', '" . $image_path . "joshie.jpg', 'In honor of Army Staff Sergeant Joshua Whitaker, 23, of Long Beach, CA who was killed in Afghanistan May 15th, 2007.', '21 Dumbbell snatches, 40 lb, right arm" . $newline . "21 L Pull-ups" . $newline . "21 Dumbbell snatches, 40 lb, left arm" . $newline . "21 L Pull-ups', '3 rounds for time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'JT', '" . $image_path . "jt.jpg', 'In honor of Petty Officer 1st Class Jeff Taylor, 30, of Little Creek, VA, who was killed in Afghanistan June 2005.', 'Handstand push-ups" . $newline . "Ring dips" . $newline . "Push-ups', '21-15-9 reps, for time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Michael', '" . $image_path . "michael.jpg', 'In honor of Navy Lieutenant Michael McGreevy, 30, of Portville, NY, who was killed in Afghanistan June 28 2005.', 'Run 800 meters" . $newline . "50 Back Extensions" . $newline . "50 Sit-ups', '3 rounds for time', '' , 'http://media.crossfit.com/cf-video/CrossFit_MichaelWOD.mov', 'asc' ), " .
		" ( 'Heroes' , 'Mr. Joshua', '" . $image_path . "mrjosh.jpg', 'SO1 Joshua Thomas Harris, 36, drowned during combat operations, August 30th 2008 in Afghanistan. He is survived by his parents Dr. Sam and Evelyn Harris, his brother Ranchor and twin sister Kiki.', 'Run 400 meters" . $newline . "30 Glute-ham sit-ups" . $newline . "15 Deadlifts, 250 lb', '5 rounds for time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Murph', '" . $image_path . "murph180.jpg', 'In memory of Navy Lieutenant Michael Murphy, 29, of Patchogue, N.Y., who was killed in Afghanistan June 28th, 2005.  This workout was one of Mike\'s favorites and he\'d named it \'Body Armor.\' From here on it will be referred to as \'Murph\' in honor of the focused warrior and great American who wanted nothing more in life than to serve this great country and the beautiful people who make it what it is.', '1 mile run" . $newline . "100 Pull-ups" . $newline . "200 Push-ups" . $newline . "300 Squats" . $newline . "1 mile run', 'For time', 'Partition the pull-ups, push-ups, and squats as needed. Start and finish with a mile run. If you have a twenty pound vest or body armor, wear it.' , '', 'asc' ), " .
		" ( 'Heroes' , 'Nate', '" . $image_path . "nate.jpg', 'In honor of Chief Petty Officer Nate Hardy, who was killed Sunday February 4th during combat operations in Iraq. Nate is survived by his wife, Mindi, and his infant son Parker. ', '2 Muscle-ups" . $newline . "4 Handstand Push-ups" . $newline . "8 2-Pood Kettlebell swings', 'As many rounds as possible in 20 minutes', '', 'http://media.crossfit.com/cf-video/CrossFit_NateEvaWOD.mov', 'desc' ), " .
		" ( 'Heroes' , 'Randy', '" . $image_path . "randy.jpg', 'In honor of Randy Simmons, 51, a 27 year LAPD veteran and SWAT team member who was killed February 6 in the line of duty. Our thoughts and prayers go out to Officer Simmons\' wife and two children.', '75 lb power snatch, 75 reps', 'For time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Ryan', '" . $image_path . "RyanH.jpg', 'Maplewood, Missouri Firefighter, Ryan Hummert, 22, was killed by sniper fire July 21st 2008 when he stepped off his fire truck responding to a call. He is survived by his parents Andrew and Jackie Hummert.', '7 Muscle-ups" . $newline . "21 Burpees', '5 rounds for time', '' , '', 'asc' ), " .
		" ( 'Heroes' , 'Tommy V', '" . $image_path . "tommyv.jpg', 'In honor of Senior Chief Petty Officer Thomas J. Valentine, 37, of Ham Lake, Minnesota, died in an training accident in Arizona, on Feb. 13 2008. ', '21 Thrusters, 115 lb" . $newline . "15 ft Rope Climb, 12 ascents" . $newline . "15 Thrusters, 115 lb" . $newline . "15 ft Rope Climb, 9 ascents" . $newline . "9 Thrusters, 115 lb" . $newline . "15 ft Rope Climb, 6 ascents', 'For time', '' , '', 'asc' ), " .
		" ( 'Named' , 'Fight Gone Bad (3 rounds)', '', '', 'Wall ball (20 lb, 10 ft target), for reps".$newline."Sumo deadlift high-pull (75 lb), for reps".$newline."Box jump (20\"), for reps".$newline."Push press (75 lb), for reps".$newline."Row, for calories', 'For total points (count reps + calories) in 3 rounds', 'In this workout you move from each of five stations after a minute.The clock does not reset or stop between exercises. This is a five-minute round from which a one-minute break is allowed before repeating. On call of \"rotate\", the athletes must move to next station immediately for best score. One point is given for each rep, except on the rower where each calorie is one point.' , 'http://media.crossfit.com/cf-video/CrossFit_FGBSCDemoExplanation.mov', 'desc' ), " .
		" ( 'Named' , 'Fight Gone Bad (5 rounds)', '', '', 'Wall ball (20 lb, 10 ft target), for reps".$newline."Sumo deadlift high-pull (75 lb), for reps".$newline."Box jump (20\"), for reps".$newline."Push press (75 lb), for reps".$newline."Row, for calories', 'For total points (count reps + calories) in 5 rounds', 'In this workout you move from each of five stations after a minute.The clock does not reset or stop between exercises. This is a five-minute round from which a one-minute break is allowed before repeating. On call of \"rotate\", the athletes must move to next station immediately for best score. One point is given for each rep, except on the rower where each calorie is one point.' , 'http://media.crossfit.com/cf-video/CrossFit_FGBSCDemoExplanation.mov', 'desc' ), " .
		" ( 'Named' , 'Filthy Fifty', '', '', '50 Box jumps (24\")" . $newline . "50 Jumping pull-ups" . $newline . "50 Kettlebell swings (1 pd)" . $newline . "50 Walking lunges" . $newline . "50 Knees to elbows" . $newline . "50 Push press (45 lb)" . $newline . "50 Back extensions" . $newline . "50 Wall ball shots (20 lb)" . $newline . "50 Burpees" . $newline . "50 Double-unders', 'For time', '' , 'http://media.crossfit.com/cf-video/CrossFit_PittFilthyFifty.mov', 'asc' ), " .
		" ( 'Named' , 'GI Jane', '', '', '100 Burpee pull-ups', 'For time', '' , 'http://media.crossfit.com/cf-video/CrossFit_AmundsonGIJane.mov', 'asc' ), " .
		" ( 'Named' , 'Nasty Girls', '', '', '50 Squats" . $newline . "7 Muscle-ups" . $newline . "10 Hang power-cleans (135 lb)', '3 rounds, for time', '' , 'http://media.crossfit.com/cf-video/051204.mov', 'asc' ), " .
		" ( 'Named' , 'Quarter Gone Bad', '', '', '135 lb Thruster, 15 seconds" . $newline . "Rest 45 seconds" . $newline . "50 lb Weighted Pull-up, 15 seconds" . $newline . "Rest 45 seconds" . $newline . "Burpees, 15 seconds" . $newline . "Rest 45 seconds', 'Five rounds For total reps', '' , 'http://media.crossfit.com/cf-video/CrossFit_QuarterGoneBad.mov', 'desc' ), " .
		" ( 'Named' , 'Tabata Something Else', '', '', 'Tabata Pull-ups" . $newline . "Tabata Push-ups" . $newline . "Tabata Sit-ups" . $newline . "Tabata Squats', 'Tabata intervals (20 seconds work/10 seconds rest x 8 rounds) for each exercise, with no rest between exercises.  ', 'Score is the total reps from all four stations (32 intervals).' , 'http://media.crossfit.com/cf-video/CrossFit_MillerTabataSE.mov', 'desc' ), " .
		" ( 'Named' , 'Tabata This', '', '', 'Tabata Squats" . $newline . "Tabata Rows" . $newline . "Tabata Pull-ups" . $newline . "Tabata Sit-ups" . $newline . "Tabata Push-ups', 'Tabata intervals (20 seconds work/10 seconds rest x 8 rounds) for each exercise, with one minute break between exercises.', 'Scoring: Each exercise is scored by the <em>weakest</em> number of reps (calories on the rower) in each of the eight intervals. The score is the total of the scores from the five stations.' , '', 'desc' ), " .
		" ( 'Olympic' , 'Clean', '', '', 'Clean', 'For weight', 'specify hang/power/full (squat)' , 'http://media.crossfit.com/cf-video/cfj-nov-05/clean.mov', 'desc' ), " .
		" ( 'Olympic' , 'Clean & jerk', '', '', 'Clean & jerk', 'For weight', 'specify hang/power/full (squat)' , 'http://media.crossfit.com/cf-video/cfj-nov-05/clean-n-jerk.mov', 'desc' ), " .
		" ( 'Olympic' , 'Snatch', '', '', 'Snatch', 'For weight', 'specify hang/power/full (squat)' , 'http://media.crossfit.com/cf-video/CrossFit_BarbellSnatch.wmv', 'desc' ), " .
		" ( 'Strength' , 'Back squat', '', '', 'Back squat', 'For weight', '' , 'http://media.crossfit.com/cf-video/backsquat.mpg', 'desc' ), " .
		" ( 'Strength' , 'Bench press', '', '', 'Bench press', 'For weight', '' , '', 'desc' ), " .
		" ( 'Strength' , 'Deadlift', '', '', 'Deadlift', 'For weight', '' , 'http://media.crossfit.com/cf-video/CrossFit_DeadliftIntro.mov', 'desc' ), " .
		" ( 'Strength' , 'Front squat', '', '', 'Front squat', 'For weight', '' , '', 'desc' ), " .
		" ( 'Strength' , 'Overhead squat', '', '', 'Overhead squat', 'For weight', '' , 'http://media.crossfit.com/cf-video/CrossFit_OverheadSquattingSafely.mov', 'desc' ), " .
		" ( 'Strength' , 'Shoulder press', '', '', 'Shoulder press', 'For weight', '' , 'http://media.crossfit.com/cf-video/CrossFit_ShoulderPressIntro.mov', 'desc' ), " .
		" ( 'Strength' , 'CrossFit Total', '', '', 'Back squat 1 rep" . $newline . "Shoulder press 1 rep" . $newline . "Deadlift 1 rep', 'For total weight', 'Download the <a href=\"http://journal.crossfit.com/2006/12/the-crossfit-total-by-mark-rip.tpl\" target=\"_blank\">free PDF</a> from the CrossFit Journal for details on how to perform the CrossFit Total.' , 'http://media.crossfit.com/cf-video/CrossFit_CFTotalNZ.mov', 'desc' ), " .
		" ( 'Endurance' , 'Row 2K', '', '', 'Row 2K', 'For time', '' , '', 'asc' ), " .
		" ( 'Endurance' , 'Run 5K', '', '', 'Run 5K', 'For time', '' , '', 'asc' ), " .
		" ( 'Gymnastics' , 'Handstand push-ups', '', '', 'Max handstand push-ups', 'For reps', 'specify floor/paralletes' , '', 'desc' ), " .
		" ( 'Gymnastics' , 'L-sit', '', '', 'Max L-sit time', 'For time', 'specify support as bar/paralletes/floor/rings' , '', 'asc' ), " .
		" ( 'Gymnastics' , 'Muscle-ups', '', '', 'Max muscle-ups', 'For reps', 'specify bar/rings' , '', 'desc' ), " .
		" ( 'Gymnastics' , 'Pull-ups', '', '', 'Max pull-ups', 'For reps', 'specify strict/kipping/butterfly' , '', 'desc' )";
	

		$results = $wpdb->query( $insert );

	}

	global $cfbenchmarks_db_version;
	$options = get_option('cfbenchmarks');
	$options['db_version'] = $cfbenchmarks_db_version;
	update_option('cfbenchmarks', $options);

}



// Data functions =============================================================

function cfbenchmarks_add_wod ($wod_type = 'Girls', $wod_name = "", $wod_pic = "", $wod_story = "", $wod_description = "", $wod_reps, $wod_notes, $wod_video = "", $wod_sort = 'asc', $wod_show = 'yes') {
	if(!$wod_name) return __('Nothing added to the database.', 'cfbenchmarks');
	
	global $wpdb;
	$table_name = $wpdb->prefix . "cf_wods";
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
		return __('Database table not found', 'cfbenchmarks');
	else //Add the WOD data to the database
	{		
		if ( ini_get('magic_quotes_gpc') ) {
		  $wod_type = stripslashes($wod_type);	
		  $wod_name = stripslashes($wod_name);	
		  $wod_pic = stripslashes($wod_pic);	
		  $wod_story = stripslashes($wod_story);	
		  $wod_description = stripslashes($wod_description);
		  $wod_reps = stripslashes($wod_reps);
		  $wod_notes = stripslashes($wod_notes);
		  $wod_video = stripslashes($wod_video);	
		  $wod_sort = stripslashes($wod_sort);
	  	}
//		$wod_type = "'".$wpdb->escape($wod_type)."'"; don't do this for drop-down lists
		$wod_name = $wod_name?"'".$wpdb->escape($wod_name)."'":"NULL";
		$wod_pic = $wod_pic?"'".$wpdb->escape($wod_pic)."'":"NULL";
		$wod_story = $wod_story?"'".$wpdb->escape($wod_story)."'":"NULL";
		$wod_description = $wod_description?"'".$wpdb->escape($wod_description)."'":"NULL";
		$wod_reps = $wod_reps?"'".$wpdb->escape($wod_reps)."'":"NULL";
		$wod_notes = $wod_notes?"'".$wpdb->escape($wod_notes)."'":"NULL";
		$wod_video = $wod_video?"'".$wpdb->escape($wod_video)."'":"NULL";
		if(!$wod_show) $wod_show = "'no'";
		else $wod_show = "'yes'";
		$insert = "INSERT INTO " . $table_name .
			"(wod_type, wod_name, wod_pic, wod_story, wod_description, wod_reps, wod_notes, wod_video, wod_sort, wod_show)" .
			"VALUES ({$wod_type}, {$wod_name}, {$wod_pic}, {$wod_story}, {$wod_description}, {$wod_reps}, {$wod_notes}, {$wod_video}, {$wod_sort}, {$wod_show})";
		$results = $wpdb->query( $insert );
		if(FALSE === $results)
			return __('There was an error: be sure all required fields are filled in properly', 'cfbenchmarks');
		else
			return __('Benchmark workout added', 'cfbenchmarks');
			
		echo $insert;
   }
}

function cfbenchmarks_edit_wod ($wod_id, $wod_type = 'Girls', $wod_name, $wod_pic, $wod_story = "", $wod_description = "", $wod_reps = "", $wod_notes = "", $wod_video, $wod_sort = 'asc', $wod_show = 'yes') {
	if(!$wod_name) return __('WOD not updated.', 'cfbenchmarks');
	if(!$wod_id) return srgq_addwod($wod_type, $wod_name, $wod_pic, $wod_story, $wod_description, $wod_reps, $wod_notes, $wod_video, $wod_sort, $wod_reps);
	global $wpdb;
	$table_name = $wpdb->prefix . "cf_wods";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
		return __('Database table not found', 'cfbenchmarks');
	else //Update database
	{
		
		if ( ini_get('magic_quotes_gpc') ) {
		  $wod_type = stripslashes($wod_type);
		  $wod_name = stripslashes($wod_name);
		  $wod_pic = stripslashes($wod_pic);
		  $wod_story = stripslashes($wod_story);	
		  $wod_description = stripslashes($wod_description);	
		  $wod_reps = stripslashes($wod_reps);
		  $wod_notes = stripslashes($wod_notes);
		  $wod_video = stripslashes($wod_video);
		  $wod_sort = stripslashes($wod_sort);
	  	}
//	  	$wod_type = "'".$wpdb->escape($wod_type)."'";
	  	$wod_name = "'".$wpdb->escape($wod_name)."'";
	  	$wod_pic = "'".$wpdb->escape($wod_pic)."'";
		$wod_story = $wod_story?"'".$wpdb->escape($wod_story)."'":"NULL";
		$wod_description = $wod_description?"'".$wpdb->escape($wod_description)."'":"NULL";
		$wod_reps = $wod_reps?"'".$wpdb->escape($wod_reps)."'":"NULL";
		$wod_notes = $wod_notes?"'".$wpdb->escape($wod_notes)."'":"NULL";
	  	$wod_video = "'".$wpdb->escape($wod_video)."'";
		if(!$wod_show) $wod_show = "'no'";
		else $wod_show = "'yes'";
		$update = "UPDATE " . $table_name . "
			SET wod_type = {$wod_type}, 
				wod_name = {$wod_name}, 
				wod_pic = {$wod_pic}, 
				wod_story = {$wod_story}, 
				wod_description = {$wod_description}, 
				wod_reps = {$wod_reps}, 
				wod_notes = {$wod_notes}, 
				wod_video = {$wod_video}, 
				wod_show = {$wod_show},
				wod_sort = {$wod_sort}
			WHERE wod_id = $wod_id";
		$results = $wpdb->query( $update );
		if(FALSE === $results)
			return __('There was an error in the MySQL query', 'cfbenchmarks');		
		else
			return __('Changes saved', 'cfbenchmarks');
   }
}

function cfbenchmarks_delete_wod ($wod_id) {
	if($wod_id) {
	global $wpdb;
	$sql = "DELETE from " . $wpdb->prefix ."cf_wods" .
		" WHERE wod_id = " . $wod_id;
	if(FALSE === $wpdb->query($sql))
		return __('There was an error in the MySQL query', 'cfbenchmarks');		
	else
		return __('WOD deleted', 'cfbenchmarks');
	}
	else return __('The WOD cannot be deleted', 'cfbenchmarks');	
}

function cfbenchmarks_getwoddata($wod_id) {
	global $wpdb;
	$sql = "SELECT wod_id, wod_type, wod_name, wod_pic, wod_story, wod_description, wod_reps, wod_notes, wod_video, wod_sort, wod_show
		FROM " . $wpdb->prefix . "cf_wods 
		WHERE wod_id = {$wod_id}";
	$wod_data = $wpdb->get_row($sql, ARRAY_A);	
	return $wod_data;
}

function cfbenchmarks_getwoddata_byname($wod_name) {
	global $wpdb;
	$sql = "SELECT wod_id, wod_type, wod_name, wod_pic, wod_story, wod_description, wod_reps, wod_notes, wod_video, wod_sort, wod_show
		FROM " . $wpdb->prefix . "cf_wods 
		WHERE wod_name = {$wod_name}";
	$wod_data = $wpdb->get_row($sql, ARRAY_A);	
	return $wod_data;
}

function cfbenchmarks_changewodvisibility($wod_ids, $wod_show = 'yes')
{
	if(!$wod_ids)
		return __('Nothing done!', 'cfbenchmarks');
	global $wpdb;
	$sql = "UPDATE ".$wpdb->prefix."cf_wods 
		SET wod_show = '".$wod_show."'
		WHERE wod_id IN (".implode(', ', $wod_ids).")";
	$wpdb->query($sql);
	return sprintf(__("Visibility status of selected WOD(s) set to '%s'", 'cfbenchmarks'), $wod_show);
}

function cfbenchmarks_bulkwoddelete($wod_ids)
{
	if(!$wod_ids)
		return __('Nothing done!', 'cfbenchmarks');
	global $wpdb;
	$sql = "DELETE FROM ".$wpdb->prefix."cf_wods 
		WHERE wod_id IN (".implode(', ', $wod_ids).")";
	$wpdb->query($sql);
	return __('WOD(s) deleted', 'cfbenchmarks');
}

function cfbenchmarks_count_wod($condition = "")
{
	global $wpdb;
	$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "cf_wods ".$condition;
	$count = $wpdb->get_var($sql);
	return $count;
}



// Admin page =================================================================

function cfbenchmarks_versioncheck(){
//	Make sure we don't need to run install again
	global $cfbenchmarks_db_version;
	$options = get_option('cfbenchmarks');
	if($options['db_version'] != $cfbenchmarks_db_version ) 
		cfbenchmarks_install();
		
}
	

function cfbenchmarks_manage_wods(){
	
	cfbenchmarks_versioncheck();
	global $benchmark_icon;
	global $image_path;
	global $cfbenchmarks_admin_userlevel;
	$custom_cat = get_option('cfbm_custom');
	
	if($_REQUEST['submit'] == __('Add WOD', 'cfbenchmarks')) {
		extract($_REQUEST);
		$msg = cfbenchmarks_add_wod($wod_type, $wod_name, $wod_pic, $wod_story, $wod_description, $wod_reps, $wod_notes, $wod_video, $wod_sort, $wod_show);
	}
	else if($_REQUEST['submit'] == __('Save changes', 'cfbenchmarks')) {
		extract($_REQUEST);
		$msg = cfbenchmarks_edit_wod($wod_id, $wod_type, $wod_name, $wod_pic, $wod_story, $wod_description, $wod_reps, $wod_notes, $wod_video, $wod_sort, $wod_show);
	}
	else if($_REQUEST['action'] == 'editwod') {
		$display .= "<div class=\"wrap\">\n<h2>{$benchmark_icon} FireBreather Benchmarks &raquo; ".__('Edit WOD', 'cfbenchmarks')."</h2>";
		$display .=  cfbenchmarks_editwod_form($_REQUEST['id']);
		$display .= "</div>";
		echo $display;
		return;
	}
	else if($_REQUEST['action'] == 'delwod') {
		$msg = cfbenchmarks_delete_wod($_REQUEST['id']);
	}
	else if($_REQUEST['action'] == 'manage_workouts') {
		cfbenchmarks_management();
	}
	else if(isset($_REQUEST['bulkaction']))  {
		if($_REQUEST['bulkaction'] == __('Delete', 'cfbenchmarks')) 
			$msg = cfbenchmarks_bulkwoddelete($_REQUEST['bulkcheck']);
		if($_REQUEST['bulkaction'] == __('Make visible', 'cfbenchmarks')) {
			$msg = cfbenchmarks_changewodvisibility($_REQUEST['bulkcheck'], 'yes');
		}
		if($_REQUEST['bulkaction'] == __('Make invisible', 'cfbenchmarks')) {
			$msg = cfbenchmarks_changewodvisibility($_REQUEST['bulkcheck'], 'no');
		}
	}
	
	$display .= "<div class=\"wrap\">";
	
	if($msg)
		$display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";

	$display .= "<h2>{$benchmark_icon} FireBreather Benchmark Workouts</h2>";

	// Get all the WODS from the database
	global $wpdb;

	$sql = "SELECT wod_id, wod_type, wod_name, wod_pic, wod_story, wod_description, wod_reps, wod_notes, wod_video, wod_sort, wod_show, wod_records 
		FROM " . $wpdb->prefix . "cf_wods";
	
	if(isset($_REQUEST['showtype'])) {
	    if($_REQUEST['showtype'] != 'All') $sql .= " WHERE wod_type = '" . $_REQUEST['showtype'] ."'";
    	$option_selected[$_REQUEST['showtype']] = " selected=\"selected\"";
		$options["sort_type"] = $_REQUEST['showtype'];
		update_option("cfbm_sort", $options);
	    }
	else {
		$cfbm_sort = get_option('cfbm_sort');
		if($cfbm_sort['sort_type'] != 'All') $sql .= " WHERE wod_type = '" . $cfbm_sort['sort_type'] . "'";
		$option_selected[$cfbm_sort['sort_type']] = " selected=\"selected\"";
	}

	$wods = $wpdb->get_results($sql);
	
	foreach($wods as $wod_data) {
		$wods_list_type = $wod_data->wod_type;

        if(!$custom_cat && $wods_list_type == "Custom") ; // do nothing
        else {
            if($alternate) $alternate = "";
            else $alternate = " class=\"alternate\"";
            $wods_list .= "<tr{$alternate}>";
            $wods_list .= "<td class=\"check-column\"><input type=\"checkbox\" name=\"bulkcheck[]\" value=\"".$wod_data->wod_id."\" /></td>";
            $wods_list .= "<td>" . $wod_data->wod_id . "</td>";
            if($custom_cat && $wods_list_type == "Custom") {
                $wods_list .= "<td>" . $custom_cat ."</td>";
            }
            else {
                $wods_list .= "<td>" . $wods_list_type ."</td>";
            }
            $wods_list .= "<td>" . wptexturize(nl2br($wod_data->wod_name)) ."</td>";
            $wods_list .="<td>";
            if($wod_data->wod_pic) $wods_list .= "<a href=\"" . $wod_data->wod_pic ."\" class=\"thickbox\"><img alt=\"\" src=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/images/camera.png\" /></a>";
            if($wod_data->wod_video) $wods_list .= "<a href=\"" . $wod_data->wod_video ."\" target=\"_blank\"><img alt=\"\" src=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/images/video.png\" /></a>";
            $wods_list .= "</td>";
            $wods_list .= "<td>" . wptexturize(nl2br($wod_data->wod_story)) ."</td>";
            $wods_list .= "<td>" . wptexturize(nl2br($wod_data->wod_description)) ."</td>";
            $wods_list .= "<td>" . wptexturize(nl2br($wod_data->wod_reps)) ."</td>";
            $wods_list .= "<td>" . wptexturize(nl2br($wod_data->wod_notes)) ."</td>";
            $wods_list .= "<td>" . $wod_data->wod_sort ."</td>";
            $wods_list .= "<td>" . $wod_data->wod_show ."</td>";
            $wods_list .= "<td>" . $wod_data->wod_records ."</td>";
            $wods_list .= "<td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=crossfit-benchmarks/cf-benchmarks.php&action=editwod&amp;id=".$wod_data->wod_id."\" class=\"edit\">".__('Edit', 'cfbenchmarks')."</a></td>
        <td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=crossfit-benchmarks/cf-benchmarks.php&action=delwod&amp;id=".$wod_data->wod_id."\" onclick=\"return confirm( '".__('Are you sure you want to delete this WOD?  Take a moment and think this through.  What has this WOD ever done to you?  You can always just make it invisible.  Yea, that would be better.  Just hit CANCEL and we can pretend this never happened.', 'cfbenchmarks')."');\" class=\"delete\">".__('Delete', 'cfbenchmarks')."</a> </td>";
            $wods_list .= "</tr>";
        }
	}

	// anchor to add new WOD
	$navlinks .= " | <a href=\"#addwod\"><strong>".__('Add new WOD', 'cfbenchmarks')."</strong></a> ";
    $navlinks .= " | <a href=\"#TB_inline?height=400&width=600&inlineId=options\" class=\"thickbox\" title=\"FireBreather Benchmarks\"><strong>".__('Options', 'cfbenchmarks')."</strong></a> ";
    $navlinks .= " | <a href=\"#TB_inline?height=400&width=600&inlineId=instructions\" class=\"thickbox\" title=\"FireBreather Benchmarks\"><strong>".__('Instructions', 'cfbenchmarks')."</strong></a> ";
	if(function_exists('cfscoreboard_install'))  // only show this if cf-scoreboard plugin is also installed
		$navlinks .= " | <a href=\"" . $_SERVER['PHP_SELF'] . "?page=crossfit-benchmarks/cf-scoreboard.php\"><strong>Manage the Scoreboard</strong></a>";
		
	if($wods_list) {
		$display .= "<p>";
	$wods_count = cfbenchmarks_count_wod();
	$display .= sprintf(__ngettext('Currently, you have %d Benchmark WOD', 'Currently, you have %d Benchmark WODs', $wods_count, 'cfbenchmarks'), $wods_count);
	$display .= $navlinks;
	$display .= "</p>";

		$display .= "<form id=\"cfbenchmarks\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}?page=crossfit-benchmarks/cf-benchmarks.php\">";
		$display .= "<div class=\"tablenav\">";
		$display .= "<div class=\"alignleft actions\">";
//		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Delete', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make visible', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make invisible', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "&nbsp;&nbsp;&nbsp;";
		$display .= __('Show workout type: ', 'cfbenchmarks');
		$display .= "<select name=\"showtype\">";
		$display .= "<option value=\"Girls\"{$option_selected['Girls']}>".__('The Girls', 'cfbenchmarks')."</option>";
		$display .= "<option value=\"Heroes\"{$option_selected['Heroes']}>".__('CrossFit Heroes', 'cfbenchmarks')."</option>";
		$display .= "<option value=\"Named\"{$option_selected['Named']}>".__('CrossFit Named', 'cfbenchmarks')."</option>";
		$display .= "<option value=\"Olympic\"{$option_selected['Olympic']}>".__('Olympic Weightlifting', 'cfbenchmarks')."</option>";
		$display .= "<option value=\"Strength\"{$option_selected['Strength']}>".__('Strength/Powerlifting', 'cfbenchmarks')."</option>";
		$display .= "<option value=\"Endurance\"{$option_selected['Endurance']}>".__('Endurance', 'cfbenchmarks')."</option>";
		$display .= "<option value=\"Gymnastics\"{$option_selected['Gymnastics']}>".__('Gymnastics/Bodyweight &nbsp;', 'cfbenchmarks')."</option>";
		if($custom_cat) $display .= "<option value=\"Custom\"{$option_selected['Custom']}>". $custom_cat ." &nbsp;</option>";
		$display .= "<option value=\"All\"{$option_selected['All']}>".__('Show All', 'cfbenchmarks')."</option>";
		$display .= "</select>";
		$display .= "<input type=\"submit\" name=\"orderby\" value=\"".__('Go', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "</div>";
		$display .= "<div class=\"clear\"></div>";	
		$display .= "</div>";
		

		
		$display .= "<table class=\"widefat\">";
		$display .= "<thead><tr>
			<th class=\"check-column\"><input type=\"checkbox\" onclick=\"cfbenchmarks_checkAll(document.getElementById('cfbenchmarks'));\" /></th>
			<th>ID</th>
			<th>".__('Type', 'cfbenchmarks')."</th>
			<th>".__('Name', 'cfbenchmarks')."</th>
			<th>".__('Media', 'cfbenchmarks')."</th>
			<th>".__('Background story', 'cfbenchmarks')." / ".__('Memorial', 'cfbenchmarks')."</th>
			<th>".__('Description', 'cfbenchmarks')."</th>
			<th>".__('Reps', 'cfbenchmarks')." / ".__('Rounds', 'cfbenchmarks')."</th>
			<th>".__('Notes', 'cfbenchmarks')."</th>
			<th>".__('Sort', 'cfbenchmarks')."</th>
			<th>".__('Show?', 'cfbenchmarks')."</th>
			<th>".__('Recs', 'cfbenchmarks')."</th>
			<th colspan=\"2\" style=\"text-align:center\">".__('Action', 'cfbenchmarks')."</th>
		</tr></thead>";
		$display .= "<tbody id=\"the-list\">{$wods_list}</tbody>";
		$display .= "</table>";


		$display .= "<div class=\"tablenav\">";
		$display .= "<div class=\"alignleft actions\">";
//		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Delete', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make visible', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "<input type=\"submit\" name=\"bulkaction\" value=\"".__('Make invisible', 'cfbenchmarks')."\" class=\"button-secondary\" />";
		$display .= "</div>";

		$display .= "</div>";
		$display .= "</form>";
		$display .= "<br style=\"clear:both;\" />";

	}
	else {
		$display .= "<p>".__('Currently, you have no benchmark WODs', 'cfbenchmarks')."";
		$display .= $navlinks;
		$display .= "</p>";
    }


	$display .= "</div>";
	
	$display .= "<div id=\"addwod\" class=\"wrap\">\n<h2>".__('Add new benchmark workout', 'cfbenchmarks')."</h2>";
	$display .= "*Required fields are in <strong>BOLD</strong>";
	if($msg)
		$display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";
	$display .= cfbenchmarks_editwod_form();
	$display .= "</div>";
	
	// This is the hidden content showing the instructions.
	$display .= "<div class=\"wrap\" id=\"instructions\" style=\"display:none;\">";
	$display .= "<h2>FireBreather Benchmarks &raquo; Instructions</h2>";
	$display .= "<p>Thanks for using FireBreather Benchmarks.  This plugin is intended to simplify the posting of some of the standard CrossFit workouts that come up repeatedly in the hopper.";
	$display .= "<p>To insert a Benchmark workout in your post, just click on the kettlebell <img alt=\"\" src=\"" . $image_path . "cfbenchmarks.gif\" /> button in the post editing window.  If you prefer (and if you know the ID number for the workout), you can type the following shortcode:</p>";
	$display .= "<blockquote><span style=\"font-family:courier\">[cf-benchmark wod=# tag=WorkoutName pic=yes]</span></blockquote>";
	$display .= "<p>When you publish the post, the workout data will show up properly formatted, including the exercises, reps, special instructions, and even the associated picture/memorial data for the 'Hero' workouts.  Please note that <em>the shortcode is all that you will see when you're editing the post</em> - the WOD data will show up in place of this shortcode when you view the published post.</p>";
	$display .= "<p>The 'pic' variable is optional.  If pic=yes then any picture and background/memorial data associated with that WOD will be shown.</p>";
	$display .= "<p>The administration pages should be fairly self-explanatory.  You will find that the plugin comes pre-loaded with all of the standard 'Girls' and 'Heroes' workouts (as of January 2009 when this was written).  Also preloaded are the standard CrossFit 'Named' workouts like 'Fight Gone Bad', as well as some Strength, Olympic weightlifting, Gymnastics and Endurance benchmarks that people may want to track.</p>";
	$display .= "<p>The list of preloaded WODs is not intended to be all-inclusive.  There may be other named workouts that I don't know about, or that your affiliate wants to track.  And sadly there will be more 'Hero' workouts in time.  You can easily add to this list by clicking on 'Add new WOD'.</p>";
	$display .= "<p>If you will be using the FireBreather Scoreboard plugin to track athlete performances, the 'Show' option will determine which of these WODs you want to show on your scoreboard.</p>";
	$display .= "<p>Deleting WODs is inadvisable.  You could delete a WOD that has athlete performance data associated with it, and this can cause problems.  If you feel compelled to delete one of the benchmark WODs, first make sure to delete all the athlete scores on the scoreboard for that WOD.  You can sort the scores by 'WOD Name' to make that process easier.</p>";
	$display .= "<p>For more information, and to post comments, go to the plugins page at <a href=\"http://crossfithickory.com/plugins\">CrossFit Hickory</a>.</p>";
	$display .= "<p>&mdash; Monty</p>";
	$display .= "</div>";

	// This function calls the hidden content showing the options page, if needed in the future.
    if(function_exists(cfbenchmarks_options)) $display .= cfbenchmarks_options();


	echo $display;

}



// Data entry  ================================================================

function cfbenchmarks_editwod_form ($wod_id = 0) {
	// table variables: ($wod_type, $wod_name, $wod_story, $wod_description, $wod_reps, $wod_notes, $wod_video, $wod_sort, $wod_show)
	$wod_show_selected = " checked=\"checked\"";
	$submit_value = __('Add WOD', 'cfbenchmarks');
	$form_name = "addwod";
	$action_url = $_SERVER['PHP_SELF']."?page=crossfit-benchmarks/cf-benchmarks.php#addwod";
	$custom_cat = get_option('cfbm_custom');

	if($wod_id) {
		$form_name = "editwod";
		$wod_data = cfbenchmarks_getwoddata($wod_id);
		foreach($wod_data as $key => $value)
			$wod_data[$key] = $wod_data[$key];
		extract($wod_data);
		if($wod_type == 'Girls') $select_girls = "selected";
		if($wod_type == 'Heroes') $select_heroes = "selected";
		if($wod_type == 'Strength') $select_strength = "selected";
		if($wod_type == 'Endurance') $select_endurance = "selected";
		if($wod_type == 'Olympic') $select_olympic = "selected";
		if($wod_type == 'Named') $select_named = "selected";
		if($wod_type == 'Gymnastics') $select_gymnastics = "selected";
		if($wod_type == 'Custom') $select_custom = "selected";
		if($wod_sort == 'asc') $select_asc = "selected";
		if($wod_sort == 'desc') $select_desc = "selected";
		if($wod_show == 'no') $wod_show_selected = "";
		$wod_name = htmlspecialchars($wod_name);
		$wod_pic = htmlspecialchars($wod_pic);
		$wod_story = htmlspecialchars($wod_story);
		$wod_description = htmlspecialchars($wod_description);
		$wod_reps = htmlspecialchars($wod_reps);
		$wod_notes = htmlspecialchars($wod_notes);
		$wod_video = htmlspecialchars($wod_video);
		$hidden_input = "<input type=\"hidden\" name=\"wod_id\" value=\"{$wod_id}\" /><input type=\"hidden\" name=\"submitted\" />";
		$submit_value = __('Save changes', 'cfbenchmarks');
		$back = "<input type=\"submit\" name=\"submit\" value=\"".__('Back', 'cfbenchmarks')."\" />&nbsp;";
		$action_url = $_SERVER['PHP_SELF']."?page=crossfit-benchmarks/cf-benchmarks.php";
	}

	$wod_type_label = __('<strong>Workout type</strong>', 'cfbenchmarks');
	$wod_name_label = __('<strong>Workout name</strong>', 'cfbenchmarks');
	$wod_pic_label = __('Picture<br /> <em>(optional)</em>', 'cfbenchmarks');
	$wod_video_label = __('Video<br /> <em>(optional)</em>', 'cfbenchmarks');
	$wod_story_label = __('Workout background story<br /> <em>(optional)</em>', 'cfbenchmarks');
	$wod_description_label = __('<strong>Workout description</strong>', 'cfbenchmarks');
	$wod_reps_label = __('<strong>Reps/rounds/time</strong>', 'cfbenchmarks');
	$wod_notes_label = __('Notes/special instructions<br /> <em>(optional)</em>', 'cfbenchmarks');
	$wod_sort_label = __('<strong>Sort scores in this order</strong>', 'cfbenchmarks');
	$wod_sort_asc_label = __('Sort ascending (lower is better)', 'cfbenchmarks');
	$wod_sort_desc_label = __('Sort ascending (higher is better)', 'cfbenchmarks');
	$wod_show_label = __('<strong>Show this WOD?</strong>', 'cfbenchmarks');

    if($custom_cat) $show_custom_cat = "<option $select_custom value=\"'Custom'\">{$custom_cat} ";

	$display .=<<< EDITFORM
<form name="{$form_name}" method="post" action="{$action_url}">
	{$hidden_input}
	<table class="form-table" cellpadding="5" cellspacing="2" width="100%">
		<tbody>
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_type">{$wod_type_label}:</label></th>
			<td>
				<select name="wod_type">
					<option $select_girls value="'Girls'">The Girls 
					<option $select_heroes value="'Heroes'">CrossFit Heroes 
					<option $select_named value="'Named'">CrossFit Named 
					<option $select_olympic value="'Olympic'">Olympic Weightlifting 
					<option $select_strength value="'Strength'">Strength/Powerlifting 
					<option $select_endurance value="'Endurance'">Endurance 
					<option $select_gymnastics value="'Gymnastics'">Gymnastics/Bodyweight 
					{$show_custom_cat} 
				</select>
				<br /><small></small></td>
		</tr>
        <tr class="form-field form-required">
            <th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_name">{$wod_name_label}:</label></th>
            <td><input type="text" id="cfbenchmarks_name" name="wod_name" size="40" value="{$wod_name}" /> <br /><small>The name of the workout (e.g. <em>Fran</em>).</small></td>
        </tr>
		<tr class="form-field">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_pic">{$wod_pic_label}:</label></th>
			<td><input type="text" id="cfbenchmarks_pic" name="wod_pic" size="40" value="{$wod_pic}" /> <br /><small>The URL of a picture to associate with this WOD (usually associated with <em>Hero</em> WODs).  Be sure to include the http:// </small></td>
		</tr>
		<tr class="form-field">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_video">{$wod_video_label}:</label></th>
			<td><input type="text" id="cfbenchmarks_video" name="wod_video" size="40" value="{$wod_video}" /> <br /><small>The URL of a video to associate with this WOD.  Be sure to include the http:// </small></td>
		</tr>
		<tr class="form-field">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_story">{$wod_story_label}:</label></th>
			<td><textarea id="cfbenchmarks_story" name="wod_story" rows="4" cols="50" style="width: 95%;">{$wod_story}</textarea> <br /><small>If this is a <em>Hero</em> workout, enter the background story/memorial info here.</small></td>
		</tr>
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_description">{$wod_description_label}:</label></th>
			<td><textarea id="cfbenchmarks_description" name="wod_description" rows="4" cols="50" style="width: 95%;">{$wod_description}</textarea> <br /><small>Enter all the exercises performed during each round here (e.g. <em>Thrusters, Pull-ups</em>, etc).  Start a new line with the RETURN key.</small></td>
		</tr>
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_reps">{$wod_reps_label}:</label></th>
			<td><input type="text" id="cfbenchmarks_reps" name="wod_reps" size="40" value="{$wod_reps}" /> <br /><small>How many reps, rounds, etc (e.g. <em>For time</em> or <em>For max weight</em> or <em>21-15-9 reps for time</em> or <em>3 rounds for time</em> or <em>As many rounds as possible in 20 min</em>).</small></td>
		</tr>
		<tr class="form-field">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_notes">{$wod_notes_label}:</label></th>
			<td><textarea id="cfbenchmarks_notes" name="wod_notes" rows="4" cols="50" style="width: 95%;">{$wod_notes}</textarea> <br /><small>Any special instructions (such as rest intervals or how to score results) should be entered here.</small></td>
		</tr>
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_sort">{$wod_sort_label}:</label></th>
			<td>
				<select name="wod_sort">
					<option $select_asc value="'asc'">Ascending (lower score is better) 
					<option $select_desc value="'desc'">Descending (higher score is better) 
				</select>
				<br /><small>How do you want to sort the results? (e.g. <em>For time</em> would be <em>Ascending</em>, since lower is better; <em>For reps</em> would be <em>Descending</em>, since more is better).</small>
			</td>
		</tr>
		<tr>
			<th style="text-align:left;" scope="row" valign="top"><label for="cfbenchmarks_show">{$wod_show_label}</label></th>
			<td><input type="checkbox" id="cfbenchmarks_show" name="wod_show"{$wod_show_selected} /></td>
		</tr>
		</tbody>
	</table>
	<p class="submit">{$back}<input name="submit" value="{$submit_value}" type="submit" class="button" /></p>
</form>
EDITFORM;
	return $display;
}



// The options page for this plugin
function cfbenchmarks_options() { 

    /* Check if there are themes: */
    $cfbenchmarks_theme_path =  (dirname(__FILE__)."/css");
    if ($handle = opendir($cfbenchmarks_theme_path)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != ".." && $file != ".DS_Store" && $file!=".svn" && $file!="ui-darkness" ) {
                $themes[$file] = $cfbenchmarks_theme_path."/".$file."/";
            }   
        }
        closedir($handle);
    }
    
    /* Create a drop-down menu of the valid themes: */
    $themes_list .= "\n<select name=\"cfbm_css\">\n";
    if ( FALSE === get_option('cfbm_css') ) {
        $current_theme = "cf-benchmarks.css";
        }
    else {
        $current_theme = get_option('cfbm_css');
        }
    foreach($themes as  $shortname => $fullpath) {
        if($current_theme == urlencode($shortname)) {
            $themes_list .= "<option value=\"" . urlencode($shortname) . "\" selected=\"selected\">" . $shortname . "</option>\n";
        } else {
            $themes_list .= "<option value=\"" . urlencode($shortname) . "\">" . $shortname . "</option>\n";
    
        }
    }
    $themes_list .= "\n</select>";

    $nonce = wp_nonce_field('update-options');
    
    $cfbm_custom = get_option('cfbm_custom');

$display .=<<< OPTIONSFORM
<div class="wrap" id="options" style="display:none;">
	<h2>FireBreather Benchmarks &raquo; Options</h2>

	<form method="post" action="options.php">

    {$nonce}

	<table class="form-table">

        <tr valign="top">
        <th scope="row"><strong>Custom Workout Category:</strong></th>
            <td>
                <input type="text" name="cfbm_custom" value="{$cfbm_custom}" /><br /><small><em>Enter the name, or leave blank for none.</em></small>
            </td>
        </tr>

        <th scope="row"><strong>Appearance Style (CSS):</strong></th> 
        <td>
        
        {$themes_list}
            
        </td>
        </tr>
	</table>

	<p class="submit">
		<input type="submit" name="Submit" value="Save Changes" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="cfbm_custom,cfbm_css" />
	</p>
	
	<hr />
	<p><strong>Help with these options:</strong></p>
    <p><small><strong>Custom Workout Category:</strong><br />
    If you want to enter your custom benchmark workouts in your own custom category, just enter the name you want in the blank.  For example, if I entered <strong>CrossFit Hickory</strong> in the blank, then 'CrossFit Hickory' would show up as one of the workout categories (at the end of the list, right after 'Gymnastics').  You can modify the name later, or delete the name if you decide not to show the custom category any more.  Just leave it blank if you don't want to use the custom category.
    </small></p>
    
    <p><small><strong>Appearance Style:</strong><br />
    Default CSS is <strong><?php echo plugins_url() ?>/crossfit-benchmarks/css/cf-benchmarks.css</strong>.<br /><br />
    If the colors don't match your color scheme (or if your site uses a theme with a dark background) you might want to try the "minimal" style sheet <strong>(cf-benchmarks-min.css)</strong>.  This style displays the Scoreboard and WODs with a transparent background, and leaves your theme's font colors intact.<br /><br />
    If you want to make any custom changes to the style sheet, using a custom CSS file is strongly recommended.  Using a custom CSS file will prevent you from losing your changes when updates come along for this plugin.  To do this:
        <blockquote><small>1. Create a copy of the file <strong>(cf-benchmarks.css)</strong> and rename your copy to whatever you want (eg. 'cf-benchmarks-custom.css').<br />
        2. Edit your custom CSS file to your heart's content (<a href="http://www.w3schools.com/css/css_intro.asp" target="_blank">start here</a> if you're new to CSS).<br />
        3. Select your custom CSS file from the list on this options page.<br />
        4. If you make any further changes to your CSS file, be sure to clear your browser's cache or your changes won't show immediately (tip: try Shift+Refresh).</small></blockquote>
    </small></p>
    
	</form>
</div>
OPTIONSFORM;

	return $display;

}
// End of the options page



// ShortCode functions ========================================================
//	To use the shortcode, enter the following into the post:
//
//			[cf-benchmark wod=# pic=y]
//
//	Obviously, you have to know the ID # of the WOD; pic is optional.  If pic 
//	is set to "y", the WOD's picture will be shown, along with the background 
//	story/memorial (if available).  Omit pic if you don't want either to show.


function cfbenchmarks_shortcode($attr) {

	$fetchwod = "'".$attr['wod']."'";
	
	$wod_data = cfbenchmarks_getwoddata($fetchwod);
	
	$wod_display .="<div class=\"wod-show\">";
	$wod_display .= "<h3>";
	if($wod_data['wod_video']) $wod_display .= "<a href=\"" . $wod_data['wod_video'] ."\" target=\"_blank\"><img class=\"wod_pic\" style=\"float:right;margin:0px;\" src=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/images/video.png\" alt=\"Watch a video for this workout\" title=\"Watch a video for this workout\" /></a>";
	$wod_display .= "&ldquo;" . $wod_data['wod_name'] . "&rdquo;";
	$wod_display .= "</h3>";
	$wod_display .= "<table><tr><td>" . wptexturize(nl2br($wod_data['wod_description'])) . "</td></tr>";
	$wod_display .= "<tr><td>" . wptexturize(nl2br($wod_data['wod_reps'])) . "</td></tr>";
	if($wod_data['wod_notes']) $wod_display .= "<tr><td>" . wptexturize(nl2br($wod_data['wod_notes'])) . "</td></tr>";
	if($attr['pic'] == 'yes'){ 
		if($wod_data['wod_pic']) {
			$wod_display .= "<tr><td><img class=\"wod_pic\" alt=\"\" src=" . $wod_data['wod_pic'] . " />";
			$wod_display .= wptexturize(nl2br($wod_data['wod_story'])) . "</td></tr>";
		}
	}
	$wod_display .= "</table></div>";
	
	return $wod_display;
	
}
add_shortcode('cf-benchmark', 'cfbenchmarks_shortcode');



// Thickbox ===================================================================

function cfbenchmarks_admin_init(){
    wp_enqueue_script('jquery');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
	}

function cfbenchmarks_header(){
    wp_enqueue_script('jquery');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
	}

function cfbenchmarks_tb_inject() {
    $current_theme .= "/crossfit-benchmarks/css/";
    if ( FALSE === get_option('cfbm_css') ) {
        $current_theme .= "cf-benchmarks.css";
        }
    else {
        $current_theme .= get_option('cfbm_css');
        }
//    $tbi .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . WP_PLUGIN_URL . "/crossfit-benchmarks/css/cf-benchmarks.css\" /> \n";
    $tbi .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . plugins_url($path = $current_theme ) . "\" /> \n";
    $tbi .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . includes_url($path = '/js/thickbox/thickbox.css') . "\" /> \n";
    $tbi .= "<script type=\"text/javascript\"> \n";
    $tbi .= "    var tb_pathToImage = \"" . includes_url($path = '/js/thickbox/loadingAnimation.gif') . "\"; \n";
    $tbi .= "    var tb_closeImage = \"" . includes_url($path = '/js/thickbox/tb-close.png') . "\" \n";
    $tbi .= "</script> \n";
    echo $tbi;
    
}
add_action('admin_init', 'cfbenchmarks_admin_init', 1);
add_action('wp_print_scripts', 'cfbenchmarks_header', 1);
add_action('wp_head', 'cfbenchmarks_tb_inject', 10);



// Custom TinyMCE Button ======================================================
// Now let's see if we can figure out how to add a custom TinyMCE button ======

class cfbmButton {

	function cfbmButton() {
		
		// define URL
		define('cfbmButton_ABSPATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
		define('cfbmButton_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
		
//		include_once (dirname (__FILE__)."/lib/shortcodes.php");
		include_once (dirname (__FILE__)."/tinymce/tinymce.php");
	
	}

}
add_action( 'plugins_loaded', create_function( '', 'global $cfbmButton; $cfbmButton = new cfbmButton();' ) );



// Admin menu =================================================================

function cfbenchmarks_admin_menu() {
	global $cfbenchmarks_admin_userlevel;
	add_management_page('FireBreather Benchmarks', 'FireBreather Benchmarks', $cfbenchmarks_admin_userlevel, __FILE__, 'cfbenchmarks_manage_wods');
	
}
add_action('admin_menu', 'cfbenchmarks_admin_menu');



register_activation_hook( __FILE__, 'cfbenchmarks_install' );
?>