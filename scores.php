<?php
	// Load the WP config settings
	$root = dirname(dirname(dirname(dirname(__FILE__))));
	if (file_exists($root.'/wp-load.php')) {
		// WP 2.6
		require_once($root.'/wp-load.php');
	} else {
		// Before 2.6
		require_once($root.'/wp-config.php');
	}
	
	global $wpdb;

	$wod = $_GET['wod'];
	$show_rx = $_GET['rx'];
	
    global $wpdb;
    
    $sort = $wpdb->get_var("SELECT wod_sort FROM " . $wpdb->prefix . "cf_wods WHERE wod_id = " . $wod . " ");
    if($sort == 'asc') {
	    $maxmin = 'MIN';
	    }
	else {
		$maxmin = 'MAX';
		}
	
	if($show_rx == 'no') {
		$show_rx = "";
		}
	else {
		$show_rx = "AND bm_rx = 'yes' ";
		}
	
    $sql = "SELECT bm_athlete, bm_score, bm_rx, bm_comment, " . $wpdb->prefix . "cf_wods.wod_name, " . $wpdb->prefix . "cf_wods.wod_description, " . $wpdb->prefix . "cf_wods.wod_reps, " . $wpdb->prefix . "cf_wods.wod_pic, " . $wpdb->prefix . "cf_wods.wod_story, " . $wpdb->prefix . "cf_wods.wod_notes, " . $wpdb->prefix . "cf_wods.wod_sort 
    	FROM " . $wpdb->prefix . "cf_scores 
    	INNER JOIN " . $wpdb->prefix . "cf_wods 
    	ON " . $wpdb->prefix . "cf_scores.bm_wod = " . $wpdb->prefix . "cf_wods.wod_id 
     	WHERE bm_wod = " . $wod . " AND bm_pr = 'yes' " . $show_rx . " 
		GROUP BY bm_athlete 
    	ORDER BY bm_score " . $sort . " 
    	LIMIT 0, 10";


    $scoreslist = $wpdb->get_results($sql);

   	foreach($scoreslist as $score) {
   	
	  	//fix the score string if it needs to be in time (mm:ss) format
        $rawscore = explode(".", $score->bm_score);
        if($sort == 'asc'){
            $bm_score = implode(":", $rawscore);
	  	}
        else{
            $bm_score = $rawscore[0];
            if(intval($rawscore[1]) > 0) {
                $bm_score = $bm_score . "." . $rawscore[1];
                $bm_score = rtrim($bm_score, "0");
            }
        }
   	
    	$list .= "<tr><td>" . $score->bm_athlete . "</td><td>" . $bm_score . "</td><td>" . $score->bm_rx . "</td><td>" . $score->bm_comment . "</td></tr>\n";
    }
?>
<head>
	<link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL ?>/crossfit-benchmarks/css/cf-scoreboard.css" />
</head>
<body>

<?php
    if($scoreslist){
	    echo '<h3>&ldquo;' . $score->wod_name . '&rdquo;</h3>';
	    if($score->wod_pic || $score->wod_story){
		    echo '<table id="wods"><tr><td><img style="margin-left: 20px;margin-bottom: 10px;float:right" width="120" src="' . $score->wod_pic . '">';
		    echo wptexturize(nl2br($score->wod_story)) . '</td></tr></table><hr />';
		    }
	    echo '<p>' . wptexturize(nl2br($score->wod_description)) . '</p>';
	    echo '<p>' . wptexturize(nl2br($score->wod_reps)) . '</p>';
	    if($score->wod_notes) echo '<p>' . wptexturize(nl2br($score->wod_notes)) . '</p>';
	    echo '<hr /><table id="scores">';
		echo '<tr class="thead"><th>Athlete</th><th>Score</th><th>Rx?</th><th>Comment</th></tr>';
	    echo $list;    
		echo '</table><hr /><br />';
		if($show_rx) echo '<small>There may be additional scaled workouts in the database, but this scoreboard will only show workouts completed "as Rx".</small>';
	}
	else {
		if($show_rx) $show_rx = " 'As Rx'";
		echo '<h3>No data for this workout' . $show_rx . '</h3>';
		if($show_rx) echo 'There may be scaled workouts in the database, but this scoreboard will only show workouts completed "as Rx".';
	}
	
?>

</body>