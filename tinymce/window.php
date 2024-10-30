<?php

// Load the WP config settings
$root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if (file_exists($root.'/wp-load.php')) {
    // WP 2.6
    require_once($root.'/wp-load.php');
} else {
    // Before 2.6
    require_once($root.'/wp-config.php');
}

require_once(ABSPATH.'/wp-admin/admin.php');

// check for rights
if(!current_user_can('edit_posts')) die;

global $wpdb;

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>CrossFit Benchmarks</title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo WP_PLUGIN_URL ?>/crossfit-benchmarks/tinymce/tinymce.js"></script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';document.getElementById('wodtag').focus();" style="display: none">
<!-- <form onsubmit="insertLink();return false;" action="#"> -->
	<form name="Benchmark" action="#">
	<div class="tabs">
		<ul>
			<li id="wod_tab" class="current"><span><a href="javascript:mcTabs.displayTab('wod_tab','wod_panel');" onmousedown="return false;"><?php _e("Workouts", 'cfbenchmarks'); ?></a></span></li>
			<li id="scores_tab"><span><a href="javascript:mcTabs.displayTab('scores_tab','scores_panel');" onmousedown="return false;"><?php _e("Scoreboard", 'cfbenchmarks'); ?></a></span></li>
		</ul>
	</div>
	
	<div class="panel_wrapper">
		<!-- wod panel -->
		<div id="wod_panel" class="panel current">
		<br />
		<table border="0" cellpadding="4" cellspacing="0">
         <tr>
            <td nowrap="nowrap"><label for="wodtag"><?php _e("Select workout", 'cfbenchmarks'); ?></label></td>
            <td><select id="wodtag" name="wodtag" style="width: 200px">
                <option value="0"><?php _e("No workout", 'cfbenchmarks'); ?></option>
				<?php
					$wodlist = $wpdb->get_results("SELECT wod_id, wod_type, wod_name, wod_show FROM " . $wpdb->prefix . "cf_wods ORDER BY wod_type, wod_name");
					if(is_array($wodlist)) {
						foreach($wodlist as $wod) {
							echo '<option value="wod=' . $wod->wod_id . ' tag=' . $wod->wod_name . '" >' . $wod->wod_type . ' - ' . $wod->wod_name . '</option>'."\n";
						}
					}
				?>
            </select></td>
          </tr>
          <tr>
            <td nowrap="nowrap" valign="top"><label for="showtag"><?php _e("Show pic/story?", 'cfbenchmarks'); ?></label></td>
            <td><label><input name="showtag" type="radio" value="yes" checked="checked" /> <?php _e('Yes', 'cfbenchmarks') ;?></label><br />
			<label><input name="showtag" type="radio" value="no"  /> <?php _e('No', 'cfbenchmarks') ;?></label></td>
          </tr>
        </table>
		</div>
		<!-- wod panel -->
		
		<!-- scores panel -->
		<div id="scores_panel" class="panel">
		<br />
		<table border="0" cellpadding="4" cellspacing="0">
            <?php
                if(function_exists(cfscoreboard_install)) {
                    echo '<tr>';
                    echo '<td nowrap="nowrap" valign="top"><label for="showscores"></label></td>';
                    echo '<td><label><input name="showscores" type="checkbox" value="yes" checked="checked" /> Show the scoreboard?</label></td>';
                    echo '</tr>';

                    echo '<tr>';
                    echo '<td nowrap="nowrap" valign="top"><label for="rx"></label></td>';
                    echo '<td><label><input id="rx" type="checkbox" value="yes" /> Show only "as Rx" workouts? (if checked &rarr; scaled workouts will not be displayed)</label></td>';
                    echo '</tr>';

                    echo '<tr>';
                    echo '<td nowrap="nowrap" valign="top"><label for="userinput"></label></td>';
                    echo '<td><label><input id="userinput" type="checkbox" value="yes" /> Allow registered users to input their own workout data?</label></td>';
                    echo '</tr>';

                }
                else 
                    echo 'You need to install the CrossFit Scoreboard plugin!';
                  
            ?>
        </table>
		</div>
		<!-- scores panel -->

		
	</div>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'cfbenchmarks'); ?>" onclick="tinyMCEPopup.close();" />
		</div>

		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'cfbenchmarks'); ?>" onclick="insertcfbmButtonLink();" />
		</div>
	</div>
</form>
</body>
</html>
