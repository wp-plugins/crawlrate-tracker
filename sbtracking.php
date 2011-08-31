<?php

	// this class is for config.
	// its functions are protected as this is pretty useless on its own and should be extended.
	class b3_sbTrackingConfig
	{

		var $sbtracking_db_version;
		var $sbtracking_table;
		var $sbtracking_plugin_dir;
		var $sbtracking_plugin_file;

		protected function __construct()
		{
			// boot strap
			global $wpdb;
			$this->wpdb = $wpdb;
			$this->wpdb->show_errors();
		
			$this->sbtracking_db_version = "2.0";
			// db table name
			$this->sbtracking_table = $this->wpdb->prefix . 'sbtracking';
			$this->sbtracking_permalink_table = $this->wpdb->prefix . 'sbtracking_permalinks';

			$this->sbtracking_plugin_dir = str_replace(str_replace('\\', '/', ABSPATH), get_settings('siteurl').'/', str_replace('\\', '/', dirname(__FILE__))).'/';
			$this->sbtracking_plugin_file = dirname(__FILE__) . '/' . basename(__FILE__);
		}
		
		protected final function cut_str_length($string, $max_length)
		{
		   if (strlen($string) > $max_length){
			   $string = substr($string, 0, $max_length);
			   $pos = strrpos($string, " ");
			   if($pos === false) {
					   return substr($string, 0, $max_length)."...";
				   }
			   return substr($string, 0, $pos)."...";
		   }else{
			   return $string;
		   }
		}

	}



	/*
	
		CLASS:			b3_sbTracking
		DESCRIPTION:	Main class for the sbTracking Module from B3
	
	*/
	class b3_sbTracking extends b3_sbTrackingConfig
	{

		var $feed = 'http://feeds.feedburner.com/blogstorm/';

		public function __construct()
		{

			parent::__construct();

		}

		// START
		public function trackingBotSearch($content = '')
		{
			$bot_name = $this->getBotName();
		
			if ($bot_name != "") {
				$sbt = new b3_sbTracking_SearchBotTracking($this->sbtracking_table);

				$sbt->page_url = $this->curPageURL();
				$sbt->robot_name = $bot_name;
				$sbt->visit_time = time();
				$sbt->save(); 
			}
		}

		private function curPageURL()
		{
		
			$pageURL = ($_SERVER["HTTPS"] == "on") ? 'https://' : 'http://';
		
			if ($_SERVER["SERVER_PORT"] != "80")
			{
				$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			} else {
				$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			}
		
			return $pageURL;
		
		}

		/**
		 * Get search engine name
		 * msn : MSN Search Engine
		 * google : Yahoo Search Engine 
		 * yahoo : Google Search Engine 
		 */
		private function getBotName()
		{

			$crawlagent = "";
			#if(preg_match("/msnbot/i", $_SERVER['HTTP_USER_AGENT']) == 1){
			if(preg_match("/bingbot/i", $_SERVER['HTTP_USER_AGENT']) == 1){
				$crawlagent="msn";
			}
			if(preg_match("/slurp/i", $_SERVER['HTTP_USER_AGENT']) == 1){
				$crawlagent="yahoo";
			}
			if(preg_match("/googlebot/i", $_SERVER['HTTP_USER_AGENT']) == 1){
				$crawlagent="google";
			}
			if(preg_match("/technoratibot/i", $_SERVER['HTTP_USER_AGENT']) == 1){
				$crawlagent="technorati";
			}

			/*
				//If not match, return empty
				if(!stristr($_SERVER['SCRIPT_NAME'], 'wp-admin'))
				{
					return 'google';
				}
			*/

			return $crawlagent;

		}

		// ADMIN
		public function admin_report_menu()
		{

			$admin = new sbTrackingAdmin();

			add_menu_page( 'Crawl Tracker', 'Crawl Tracker', 'add_users', 'sb_tracking', array( $admin, 'tracking_bot_report' ) );

			add_submenu_page('sb_tracking', __('Crawled Pages','search-bot-report'), __('Crawled Pages','crawled-pages-report'), 'import', 'crawled-pages-report', array( $admin, 'tracking_crawled_pages' ));

			add_submenu_page('sb_tracking', __('Non Crawled Pages','search-bot-report'), __('Non Crawled Pages','non-crawled-pages-report'), 'import', 'non-crawled-pages-report', array( $admin, 'tracking_non_crawled_pages' ));

		}

		// BLOGSTORM DASHBOARD
		// lightly adapted from Yoasts' SEO Plugin - http://yoast.com/wordpress/seo/
		function setupDashboard()
		{
			wp_add_dashboard_widget( 'blogstorm_rss_widget' , 'The Latest From BlogStorm' , array(&$this, 'rss_widget') );
		}
		
		function fetch_rss_items($num)
		{
			include_once(ABSPATH . WPINC . '/feed.php');
			$rss = fetch_feed( $this->feed );

			// Bail if feed doesn't work
			if ( is_wp_error($rss) ){return false;}

			$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );

			if (!$rss_items)
			{
				$md5 = md5( $this->feed );
				delete_transient( 'feed_' . $md5 );
				delete_transient( 'feed_mod_' . $md5 );
				$rss = fetch_feed( $this->feed );
				$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
			}

			return $rss_items;

		}
		
		function rss_widget()
		{

			$rss_items = $this->fetch_rss_items(3);
			
			echo '<div class="rss-widget">';
				echo '<a href="http://www.blogstorm.co.uk/" title="Blogstorm"><img src="http://www.blogstorm.co.uk/wp-content/themes/blogstorm/images/blogstormLogo.png" class="alignright" alt="Yoast"/></a>';			
			echo '<ul>';

			if ( !$rss_items ) {
			    echo '<li class="blogstorm">no news items, feed might be broken...</li>';
			} else {
			    foreach ( $rss_items as $item ) {
					echo '<li class="blogstorm">';
						echo '<a class="rsswidget" href="'.esc_url( $item->get_permalink(), $protocolls=null, 'display' ).'">'. esc_html( $item->get_title() ) .'</a>';
							echo ' <span class="rss-date">'. $item->get_date('F j, Y') .'</span>';
						echo '<div class="rssSummary">'. esc_html( $this->cut_str_length( strip_tags( $item->get_description() ), 150 ) ).'</div>';
					echo '</li>';
			    }
			}						

			echo '</ul>';
			echo '<br class="clear"/><div style="margin-top:10px;border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">';
			echo '<a href="http://feeds.feedburner.com/blogstorm">Subscribe with RSS</a>';
			echo ' &nbsp; | &nbsp; ';
			echo '<a href="http://www.blogstorm.co.uk/"> Subscribe by email</a>';
			echo '</div>';
			echo '</div>';
		}

		// ACTIVATION
		public function sbtracking_activation()
		{
		
		   if($this->wpdb->get_var("show tables like '" . $this->sbtracking_table . "'") != $this->sbtracking_table)
		   {
		
				$sql = "CREATE TABLE `" . $this->sbtracking_table . "` (
							`id` INT NOT NULL AUTO_INCREMENT ,
							`robot_name` VARCHAR( 100 ) NOT NULL ,
							`page_url` VARCHAR( 250 ) NOT NULL ,
							`visit_time` INT NOT NULL ,
							PRIMARY KEY ( `id` )
						);";
			
				$this->wpdb->query( $sql );
			
				add_option("sbtracking_db_version", $this->sbtracking_db_version);
			}
			
		   if($this->wpdb->get_var("show tables like '" . $this->sbtracking_permalink_table . "'") != $this->sbtracking_permalink_table)
		   {
		
				$sql = "CREATE TABLE `" . $this->sbtracking_permalink_table . "` (
							`id` INT NOT NULL AUTO_INCREMENT ,
							`permalink` TEXT NOT NULL,
							PRIMARY KEY ( `id` )
						);";
			
				$this->wpdb->query( $sql );

			}

		}
		
		// SHOW BUTTON
		function add_button($content) {

			if (current_user_can('edit_users'))
			{
				global $post;
				$content .= "<p><a href='".get_bloginfo('wpurl').'/wp-admin/admin.php?page=sb_tracking&page_url='.get_permalink($post->ID)."'><img width='16' height='16' src='".$this->sbtracking_plugin_dir."stats.jpg' alt='Bot statistics for this page' title='Bot statistics for this page'/></a></p>";
			}

			return $content;	
		}
		
	}







	/*
	
		CLASS:			b3_sbTracking_SearchBotTracking
		DESCRIPTION:	Class for an sbTracking item
	
	*/
	class b3_sbTracking_SearchBotTracking extends b3_sbTrackingConfig
	{
	
		var $id;
		var $robot_name;
		var $page_url;
		var $visit_time;
		
		public function __construct()
		{
			parent::__construct();
		}
	
		// insert into DB when a robot visited, what they looked at
		public function save()
		{
	
			$sql = "INSERT INTO `". $this->sbtracking_table . "` (`id`, `robot_name`, `page_url`, `visit_time`)
					values (
						'". $this->id ."', 
						'". $this->robot_name ."', 
						'". $this->page_url ."', 
						'". $this->visit_time ."'
					)";
					
			mysql_query($sql) or die (mysql_error());
	
		}
	
		// check the database for the URL
		public function getByURL($page_url)
		{
	
			$sql = "SELECT id, robot_name, page_url, visit_time FROM ". $this->sbtracking_table . 
				   "WHERE page_url LIKE '" . $page_url. "'" ;
	
			$result = mysql_query($sql);
	
			// After calling this function, the id is null that means this url not exist in Database
			$this->id = "";
	
			while ($row = mysql_fetch_object($result)) {
				$this->id = $row->id;
				$this->robot_name = $row->robot_name;
				$this->page_url = $row->page_url;
				$this->visit_time = $row->visit_time;
			}
	
			mysql_free_result($result);
	
		}
	
		// Check if this page is in database. Return the visit time
		public function checkIfExisted($pageURL)
		{
	
			$this->getByURL($pageURL);
	
			//check if exist
			if ($this->id != "") {
				return $this->visit_time;
			} else {
				return 0;
			}
	
		}
	
		// Increment visit page times
		public function increasePageVisitTimes($visit_time)
		{
	
			$sql = "UPDATE ". $this->sbtracking_table . " SET visit_time = '". $visit_time . "'
					WHERE page_url LIKE '". $this->page_url . "'";
	
			mysql_query($sql);
	
		}
	
	}
	
	
	
	class sbTrackingAdmin extends b3_sbTrackingConfig
	{
	
		function __construct()
		{
			// boot
			parent::__construct();
		}

		public function tracking_bot_report()
		{
			
			if($_GET['date'] == ''){$_GET['date'] = date('Y-m');}
			
			echo '<div class="wrap" style="margin-bottom:40px">
					<h2>The Crawl Rate Tracker</h2>';

			global $post;

			echo '<h3>All Crawl Stats for ' . $_SERVER['HTTP_HOST'] . '</h3>';

			if ($_POST['clear_db'])
			{
				$this->wpdb->query("DELETE FROM $sbtracking_table");
			}
			
			if (!$_GET['page_url'])
			{

				echo '<div style="float:left; width:49%;">';

					echo '<p>Switch to: <select name="monthyear" id="monthyear" onchange="var selected = this.options[this.selectedIndex].value; window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected">';
	
					$timeBottom = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
																   MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time ASC LIMIT 1");
																   
					$timeTop = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
																MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time DESC LIMIT 1");
	
					for($i=$timeBottom[0]->vyear; $i<$timeTop[0]->vyear+1;$i++)
					{
						for($j=1; $j<13; $j++)
						{
	
							$start = mktime(0,0,0,$j,1,$i);
							$proper_date = date('M Y', $start);
							echo '<option value="' . $i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) . '"';
							
							if($i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) == $_GET['date']){echo ' selected="selected"';}
							
							echo '>' . $proper_date . '</option>';
						}
					}
	
					echo '</select>&nbsp;&nbsp;&nbsp;';
	
					if($_GET['unique'] == 'true')
					{
						$unique_checkbox = 'true';
					}
		
					if($_GET['robots'] != ''){
						$robots = explode(',', $_GET['robots']);
					}elseif(isset($_GET['robots'])){
						$robots = array();
					}else{
						$robots = array('google');
					}

				
					echo '<input type="checkbox" name="robot_checkbox" id="robot_checkbox_google" value="google"';
						if(in_array('google', $robots)){echo ' checked="checked"';}
					echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&robots=\'+robots" /> Google&nbsp;&nbsp;&nbsp;';
	
					echo '<input type="checkbox" name="robot_checkbox" id="robot_checkbox_yahoo" value="yahoo"';
						if(in_array('yahoo', $robots)){echo ' checked="checked"';}
					echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&robots=\'+robots" /> Yahoo&nbsp;&nbsp;&nbsp;';
	
					echo '<input type="checkbox" name="robot_checkbox" id="robot_checkbox_msn" value="msn"';
						if(in_array('msn', $robots)){echo ' checked="checked"';}
					echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&robots=\'+robots" /> Bing';
				echo '</div>';
				
				/*
				echo '<div style="float:right; width:49%; text-align:right;">';
					echo '<p>';
						echo '<input type="checkbox" name="unique_checkbox" id="unique_checkbox" value="true"';
							if($unique_checkbox == 'true'){echo ' checked="checked"';}
						echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} if(document.getElementById(\'unique_checkbox\').checked){ var unique = \'true\';}else{var unique = \'false\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&robots=\'+robots+\'&unique=\'+unique" /> Unique Views';
					echo '</p>';
				echo '</div>';
				*/

				$dir = get_option('home') . '/wp-content/plugins/' . basename(dirname(__FILE__));
				$url = $dir .'/sbtracking-chart-data.php?chart_data=1&date=' . $_GET['date'] . '&robots=' . implode(',', $robots) . '&unique=' . $unique_checkbox;

				#echo $url;

				$width = "100%";
				$height = 480;
				$chart = open_flash_chart_object($width, $height, $url, true, get_option('home') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/');
				echo $chart;
				echo '<br style="clear:both;" />';
			}
			else
			{

				echo '<p>Switch to: <select name="monthyear" id="monthyear" onchange="var selected = this.options[this.selectedIndex].value; window.location.href=\'/wp-admin/admin.php?page=sb_tracking&page_url=' . $_GET['page_url'] . '&date=\'+selected">';

				$timeBottom = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
															   MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time ASC LIMIT 1");
											   
				$timeTop = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
															MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time DESC LIMIT 1");

				for($i=$timeBottom[0]->vyear; $i<$timeTop[0]->vyear+1;$i++)
				{
					for($j=1; $j<13; $j++)
					{

						$start = mktime(0,0,0,$j,1,$i);
						$proper_date = date('M Y', $start);
						echo '<option value="' . $i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) . '"';
						
						if($i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) == $_GET['date']){echo ' selected="selected"';}
						
						echo '>' . $proper_date . '</option>';
					}
				}

				echo '</select>&nbsp;&nbsp;&nbsp;';

				if($_GET['robots'] != ''){
					$robots = explode(',', $_GET['robots']);
				}elseif(isset($_GET['robots'])){
					$robots = array();
				}else{
					$robots = array('google');
				}

				echo '<input type="checkbox" name="robot_checkbox" id="robot_checkbox_google" value="google"';
					if(in_array('google', $robots)){echo ' checked="checked"';}
				echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&page_url=' . $_GET['page_url'] . '&robots=\'+robots" /> Google&nbsp;&nbsp;&nbsp;';

				echo '<input type="checkbox" name="robot_checkbox" id="robot_checkbox_yahoo" value="yahoo"';
					if(in_array('yahoo', $robots)){echo ' checked="checked"';}
				echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&page_url=' . $_GET['page_url'] . '&robots=\'+robots" /> Yahoo&nbsp;&nbsp;&nbsp;';

				echo '<input type="checkbox" name="robot_checkbox" id="robot_checkbox_msn" value="msn"';
					if(in_array('msn', $robots)){echo ' checked="checked"';}
				echo ' onclick="var selected = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robots = \'\'; if(document.getElementById(\'robot_checkbox_google\').checked){ robots += \'google,\';} if(document.getElementById(\'robot_checkbox_yahoo\').checked){ robots += \'yahoo,\';} if(document.getElementById(\'robot_checkbox_msn\').checked){ robots += \'msn,\';} window.location.href=\'/wp-admin/admin.php?page=sb_tracking&date=\'+selected+\'&page_url=' . $_GET['page_url'] . '&robots=\'+robots" /> Bing';

			}
			
			$report_text = $this->tracking_bot_report_text();
		
			if ($_GET['page_url'])
			{
				$url = $_GET['page_url'];
				$query_str = '?page=' . $_GET['page'];
				echo '<div class="float:left;clear:both;padding-bottom: 30px;"><strong>Page:</strong>&nbsp;&nbsp;<a href="' . $url . '">' . $url . '</a>';
				echo "<a href='$query_str' class='button alignright'>&laquo; Return to main report</a></div><br style='clear:both'>";
			}
			else
			{
				echo '<div class="float:left; width:100%;"><h3 style="float:left;">Detail report</h3>';
				echo "<form method='post' onsubmit='javascript:return confirm(\"Do you really want to clear the Crawls Log ?\")'>
					<span class='alignright' style='margin-top:10px;'>
						<input type='submit' name='clear_db' value='Clear Crawls Log' class='button-primary'>
					</span>
				</form></div>";
			}
				
			echo $report_text;
			echo "</div>";

		}
		
		private function tracking_bot_report_text()
		{

			$query_str = '?page=' . $_GET['page'];
			$page_size = 50;
			$page_no = intval($_GET['p']);
			$skip = $page_no * $page_size;
			
			if($_GET['date'] != '')
			{
				$dates = explode('-', $_GET['date']);
				$start = mktime(0,0,0,$dates[1],1,$dates[0]);
				$themonth = date('F', mktime(0,0,0,$dates[1],1,$dates[0]));
				$theyear = date('Y', mktime(0,0,0,$dates[1],1,$dates[0]));
				$end = mktime(23,59,59,($dates[1]+1),0,$dates[0]);
			}
			else
			{
				$themonth = date('F');
				$theyear = date('Y');
			}
			
			if ($url = $_GET['page_url'])
			{
				$query_str2 = $query_str . '&page_url=' . urlencode($url);
				$page_url = $this->wpdb->escape($url);
				
				//$text .= "<a href='$query_str' class='button alignright' style='margin-top:-40px;'>&laquo; Return to main report</a>";
				
				echo '<table cellpadding="3" style="margin-top:0px; float:left; border-top:1px solid #ddd;border-bottom:1px solid #ddd; width:100%; border-collapse:collapse;">';
				$total_bots = $this->wpdb->get_var("SELECT COUNT(*) FROM $this->sbtracking_table WHERE `page_url` = '$page_url' AND `visit_time` >= '$start' AND `visit_time` <= '$end'");

				$total_days = $this->wpdb->get_var("SELECT COUNT(DISTINCT(DATE(FROM_UNIXTIME(`visit_time`)))) as counter FROM " . $this->sbtracking_table . " WHERE `page_url` = '$page_url' AND `visit_time` >= '$start' AND `visit_time` <= '$end'");

				echo '<p><strong>' . $total_bots . ' crawls for this page in ' . $themonth . ', average of ' . floor($total_bots/$total_days) . ' crawls per day</strong></p><br />';

				$bots = $this->wpdb->get_results("SELECT `visit_time`, `robot_name` FROM $this->sbtracking_table WHERE `page_url` = '$page_url' AND `visit_time` >= '$start' AND `visit_time` <= '$end' ORDER BY `visit_time` DESC LIMIT $skip, $page_size");
				
#				echo "SELECT `visit_time`, `robot_name` FROM $this->sbtracking_table WHERE `page_url` = '$page_url' ORDER BY `visit_time` DESC LIMIT $skip, $page_size";
				
				$SOMETHING = '';

				if($_GET['robots'] != ''){
					$robots = explode(',', $_GET['robots']);
				}elseif(isset($_GET['robots'])){
					$robots = array();
				}else{
					$robots = array('google');
				}

				$dir = get_option('home') . '/wp-content/plugins/' . basename(dirname(__FILE__));
				$url = $dir .'/sbtracking-chart-data.php?chart_data=1&page_url=' . $_GET['page_url'] . '&date=' . $_GET['date'] . '&robots=' . implode(',', $robots);

				$width = "100%";
				$height = 480;
				$chart = open_flash_chart_object($width, $height, $url, true, get_option('home') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/');
				echo $chart;
				echo '<br style="clear:both;" />';
				
				
				$dir = get_option('home') . '/wp-content/plugins/' . basename(dirname(__FILE__));
	
				foreach ($bots as $bot)
				{
					$robot_name = $bot->robot_name;
					$visit_time = date('jS M Y H:i:s', $bot->visit_time);
					
					if($i % 2 == 1)
					{
						$class= " style='padding:5px;background:#e8e8e8;color:#333;font-weight:normal'";
					}else
					{
						$class = ' style="padding:5px;color:#333;font-weight:normal"';
					}
					
					if(strtolower($robot_name) == 'msn'){$robot_name = 'bing';}
					
					$text .= "<tr><td" . $class . "><img width=\"16\" src='$dir/$robot_name.png' alt='$robot_name' style='vertical-align:middle;margin-right:5px;'><span style='vertical-align:top;text-transform:capitalize;'>$robot_name</span></td><td align='right'" . $class . ">$visit_time</td></tr>";

					$i++;

				}
				$text .= '</table><br><div>';
				if ($page_no < 0) {
					$page_no = 0;
				}
				if ($page_no > 0) {
					$p = $page_no -1;
					$text .="<a href='$query_str2&p=$p' class='button alignleft'>&lt; Back</a>";
				}
				if ($skip + $page_size < $total_bots) {
					$p = $page_no +1;
					$text .="&nbsp;&nbsp; <a href='$query_str2&p=$p' class='button alignright'>Next &gt;</a>";
				}

				$text .= "</div><br><form method='post' onsubmit='javascript:return confirm(\"Do you really want to clear the Crawls Log ?\")'>
					<span class='alignright'>
						<input type='submit' name='clear_db' value='Clear Crawls Log' class='button-primary'>
					</span>
				</form>";
				
			}
			else
			{
				
				echo '<div style="width:100%;clear:both;"><table cellpadding="3" style="border-collapse:collapse;border-top:1px solid #ddd;border-bottom:1px solid #ddd;width:100%; float:left;">';

				$bots = $this->wpdb->get_results("SELECT 0 FROM $this->sbtracking_table GROUP BY `page_url` AND `visit_time` >= '$start' AND `visit_time` <= '$end'");
				$total_bots = count($bots);
				$bots = $this->wpdb->get_results("SELECT COUNT(*) AS 'counter', `page_url` FROM $this->sbtracking_table WHERE `visit_time` >= '$start' AND `visit_time` <= '$end' GROUP BY `page_url` ORDER BY `counter` DESC LIMIT $skip, $page_size");
				
				$total_days = $this->wpdb->get_var("SELECT COUNT(DISTINCT(DATE(FROM_UNIXTIME(`visit_time`)))) as counter FROM " . $this->sbtracking_table . " WHERE `page_url` = '$page_url' AND `visit_time` >= '$start' AND `visit_time` <= '$end'");

				#echo '<p><strong>' . $total_bots . ' crawls for this page in ' . $themonth . ', average of ' . floor($total_bots/$total_days) . ' crawls per day</strong></p><br />';
				$total = 0;
				foreach ($bots as $bot)
				{
					$total += $bot->counter;
				}
				$home_url = get_option('home');
				$row_count = 0;
				foreach ($bots as $bot)
				{
					$page_url = $bot->page_url;
					$short_url = $this->trimLength( str_replace( $home_url, '', $page_url), 50);
					$counter = $bot->counter;
					$width = intval($total_length * $counter / $total);
					if($row_count % 2 == 1){
						$text .= "<tr><td style='padding:5px;background-color:#e8e8e8'><a href='$page_url' title='$page_url' style='font-weight:normal'>$short_url</a> </td><td style='background-color:#e8e8e8;padding-right:10px;' align='right'><a href='$query_str&page_url=$page_url'>$counter crawls</a></td>".
					//<td><span style='display:block;width: ${counter}px; background: #606060;'>&nbsp;</span></td>
					"<td align='right' style='background-color:#e8e8e8;width:80px;'><a class='button' style='font-weight:normal;margin-right:10px;' href='https://siteexplorer.search.yahoo.com/advsearch?p=${page_url}&bwm=i&bwmo=d&bwmf=u'>Links</a></td>";
					} else {
						$text .= "<tr><td style='padding:5px'><a href='$page_url' title='$page_url' style='font-weight:normal'>$short_url</a> </td><td align='right' style='padding-right:10px;'><a href='$query_str&page_url=$page_url'>$counter crawls</a></td>".
					//<td><span style='display:block;width: ${counter}px; background: #606060;'>&nbsp;</span></td>
					"<td align='right'><a class='button' style='font-weight:normal;margin-right:10px;' href='https://siteexplorer.search.yahoo.com/advsearch?p=${page_url}&bwm=i&bwmo=d&bwmf=u'>Links</a></td>";
					}					
					$text .= "</tr>";
					$row_count++;
				}
				$text .= '</table><br>';
				if ($page_no < 0) {
					$page_no = 0;
				}
				if ($page_no > 0) {
					$p = $page_no -1;
					$text .="<a href='$query_str&p=$p' class='button'>&lt; Back</a>&nbsp;&nbsp;&nbsp;";
				}
				if ($skip + $page_size < $total_bots) {
					$p = $page_no +1;
					$text .="<a href='$query_str&p=$p' class='button alignright'>Next &gt;</a>";
				}
				$text .= "</div>";
			}

			$text .= '<div class="" style="float:left; width:100%; margin:10px 0 0 0;">
						<p><strong>Information</strong></p>
						<p>Google / Yahoo / Bing shows number of visits from crawlers.</p>
						<p>Google Unique / Yahoo Unique / Bing Unique shows number of unique pages visited by crawlers.</p>
					  </div>';

			return $text;
		}
		
		
		public function tracking_crawled_pages(){

			if($_GET['date'] == ''){$_GET['date'] = date('Y-m');}

			echo '<div class="wrap">
					<h2>The Crawl Rate Tracker</h2>';

			echo '<div style="float:left; width:100%;"><span style="float:left;">Switch to: </span><select name="monthyear" id="monthyear" style="float:left;">';

				$timeBottom = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
															   MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time ASC LIMIT 1");
															   
				$timeTop = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
															MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time DESC LIMIT 1");

				echo '<option value="last7"';
					if($_GET['date'] == 'last7'){echo ' selected="selected"';}
				echo '>Last 7 Days</option>';
				echo '<option value="last14"';
					if($_GET['date'] == 'last14'){echo ' selected="selected"';}
				echo '>Last 14 Days</option>';

				for($i=$timeBottom[0]->vyear; $i<$timeTop[0]->vyear+1;$i++)
				{
					for($j=1; $j<13; $j++)
					{

						$start = mktime(0,0,0,$j,1,$i);
						$proper_date = date('M Y', $start);
						echo '<option value="' . $i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) . '"';
						
						if($i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) == $_GET['date']){echo ' selected="selected"';}
						
						echo '>' . $proper_date . '</option>';
					}
				}

			echo '</select>';
			
			echo '<select name="robot" id="robot" style="float:left;">';
			
				echo '<option value=""';
					if($_GET['robot'] == ''){echo ' selected="selected"';}
				echo '>Any</option>';
				echo '<option value="google"';
					if($_GET['robot'] == 'google'){echo ' selected="selected"';}
				echo '>Google</option>';
				echo '<option value="yahoo"';
					if($_GET['robot'] == 'yahoo'){echo ' selected="selected"';}
				echo '>Yahoo</option>';
				echo '<option value="msn"';
					if($_GET['robot'] == 'msn'){echo ' selected="selected"';}
				echo '>Bing</option>';
				#echo '<option value="technorati"';
				#	if($_GET['robot'] == 'technorati'){echo ' selected="selected"';}
				#echo '>Technorati</option>';
			
			echo '</select>';
			
			echo '<input type="button" name="submit" id="submit" value="switch now" style="float:left; cursor:pointer;" onclick="var monthyear = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robot = document.getElementById(\'robot\').options[document.getElementById(\'robot\').selectedIndex].value; window.location.href=\'/wp-admin/admin.php?page=crawled-pages-report&date=\'+monthyear+\'&robot=\'+robot" /></div><br /><br />';
			

			if($_GET['date'] != '')
			{
				if($_GET['date'] == 'last7')
				{
					$start = mktime(0,0,0,date('m'),1,date('d',strtotime(THEDATE . '-7 days')));
					$end = mktime(0,0,0,date('m'),1,date('d'));
				}
				elseif($_GET['date'] == 'last14')
				{
					$start = mktime(0,0,0,date('m'),1,date('d',strtotime(THEDATE . '-14 days')));
					$end = mktime(0,0,0,date('m'),1,date('d'));
				}
				else
				{
					$dates = explode('-', $_GET['date']);
					$start = mktime(0,0,0,$dates[1],1,$dates[0]);
					$end = mktime(23,59,59,($dates[1]+1),0,$dates[0]);
				}
			}else{

				$start = mktime(0,0,0,date('m'),1,date('d'));
				$end = mktime(23,59,59,(date('m')+1),0,date('d'));
			}

			if($_GET['robot'] != '')
			{
				$robot = "AND robot_name = '" . addslashes($_GET['robot']) . "'";
			}

			echo '<h3>Pages crawled for ' . $_SERVER['HTTP_HOST'] . ' for the time period and robot selected above.</h3><br />';

			$permalinks = $this->wpdb->get_results("SELECT DISTINCT page_url from " . $this->sbtracking_table . " wps 
													WHERE `visit_time` >= '" . $start . "' AND `visit_time` <= '" . $end . "'
													" . $robot);

			echo '<table cellpadding="3" style="border-collapse:collapse;">';

			if(sizeof($permalinks) == 0)
			{
				echo '<p>No pages were crawled in this time by the robot(s) selected above.</p>';
			}
			else
			{

				$i = 1;

				foreach($permalinks as $permalink){
						echo '<tr>';
							echo '<td> ' . $i . '. <a href="/wp-admin/admin.php?page=sb_tracking&page_url=' . $permalink->page_url . '">' . $permalink->page_url . '</a></td>';
						echo '</tr>';
						$i++;
				}
			
			}

			echo '</table>';


		}
		
		public function tracking_non_crawled_pages()
		{

			if($_GET['date'] == ''){$_GET['date'] = date('Y-m');}

			global $post;

			$postBefore = $post;
			$children = get_pages();
			foreach($children as $post)
			{
				setup_postdata($post);
				$permalink[] = "'" . get_permalink() . "'";
			}

			$args = array( 'numberposts' => -1 );
			$children = get_posts($args);
			foreach($children as $post)
			{
				setup_postdata($post);
				$permalink[] = "'" . get_permalink() . "'";
			}

			// set up temp table.
			$this->wpdb->query("DELETE FROM $this->sbtracking_permalink_table");
			foreach($permalink as $p){
				
				$sql = "INSERT INTO $this->sbtracking_permalink_table SET permalink = " . $p . ';';
				$answer = $this->wpdb->query($sql);

			}

			$post = $postBefore;

			$perma = implode(',', $permalink);
			
			echo '<div class="wrap">
					<h2>The Crawl Rate Tracker</h2>';

			echo '<div style="float:left; width:100%;"><span style="float:left;">Switch to: </span><select name="monthyear" id="monthyear" style="float:left;">';

				$timeBottom = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
															   MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time ASC LIMIT 1");
															   
				$timeTop = $this->wpdb->get_results("SELECT YEAR(DATE(FROM_UNIXTIME(`visit_time`))) as vyear,
															MONTH(DATE(FROM_UNIXTIME(`visit_time`))) as vmonth FROM " . $this->sbtracking_table . " ORDER BY visit_time DESC LIMIT 1");

				for($i=$timeBottom[0]->vyear; $i<$timeTop[0]->vyear+1;$i++)
				{
					for($j=1; $j<13; $j++)
					{

						$start = mktime(0,0,0,$j,1,$i);
						$proper_date = date('M Y', $start);
						echo '<option value="' . $i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) . '"';
						
						if($i . '-' . str_pad($j, 2, '0', STR_PAD_LEFT) == $_GET['date']){echo ' selected="selected"';}
						
						echo '>' . $proper_date . '</option>';
					}
				}

			echo '</select>';
			
			echo '<select name="robot" id="robot" style="float:left;">';
			
				echo '<option value=""';
					if($_GET['robot'] == ''){echo ' selected="selected"';}
				echo '>Any</option>';
				echo '<option value="google"';
					if($_GET['robot'] == 'google'){echo ' selected="selected"';}
				echo '>Google</option>';
				echo '<option value="yahoo"';
					if($_GET['robot'] == 'yahoo'){echo ' selected="selected"';}
				echo '>Yahoo</option>';
				echo '<option value="msn"';
					if($_GET['robot'] == 'msn'){echo ' selected="selected"';}
				echo '>Bing</option>';
				echo '<option value="technorati"';
					if($_GET['robot'] == 'technorati'){echo ' selected="selected"';}
				echo '>Technorati</option>';
			
			echo '</select>';
			
			echo '<input type="button" name="submit" id="submit" value="switch now" style="float:left; cursor:pointer;" onclick="var monthyear = document.getElementById(\'monthyear\').options[document.getElementById(\'monthyear\').selectedIndex].value; var robot = document.getElementById(\'robot\').options[document.getElementById(\'robot\').selectedIndex].value; window.location.href=\'/wp-admin/admin.php?page=non-crawled-pages-report&date=\'+monthyear+\'&robot=\'+robot" /></div><br /><br />';
			

			if($_GET['date'] != '')
			{
				$dates = explode('-', $_GET['date']);
				$start = mktime(0,0,0,$dates[1],1,$dates[0]);
				$end = mktime(23,59,59,($dates[1]+1),0,$dates[0]);
			}else{
				$start = mktime(0,0,0,date('m'),1,date('d'));
				$end = mktime(23,59,59,(date('m')+1),0,date('d'));
			}

			if($_GET['robot'] != '')
			{
				$robot = "AND robot_name = '" . addslashes($_GET['robot']) . "'";
			}

			$permalinks = $this->wpdb->get_results("SELECT permalink 
													FROM `" . $this->sbtracking_permalink_table . "` as wpsp 
													WHERE NOT EXISTS(
														SELECT * from " . $this->sbtracking_table . " wps 
														WHERE wps.page_url = wpsp.permalink
														AND `visit_time` >= '" . $start . "' AND `visit_time` <= '" . $end . "'
														" . $robot . "
													)");

			echo '<h3>Pages not crawled for ' . $_SERVER['HTTP_HOST'] . ' for the time period and robot selected above.</h3><br />';

			echo '<table cellpadding="3" style="border-collapse:collapse;">';

			if(sizeof($permalinks) == 0)
			{
				echo '<p>All pages were crawled in this time.</p>';
			}
			else
			{

				$i = 1;

				foreach($permalinks as $permalink){
						echo '<tr>';
							echo '<td> ' . $i . '. <a href="/wp-admin/admin.php?page=sb_tracking&page_url=' . $permalink->permalink . '">' . $permalink->permalink . '</a></td>';
						echo '</tr>';
						$i++;
				}
			
			}

			echo '</table>';

		}
		
		private function trimLength($text, $max_length)
		{
			if ( strlen($text) <= $max_length ) {
				return $text;
			}
			return substr($text, 0, $max_length) . '...';
		}

	}
?>