<?php

	// up the memory depending about 
	ini_set('memory_limit', '196M');

	require_once '../../../wp-config.php';
	require_once './sbtracking.php';

	class b3_chartData extends b3_sbTrackingConfig
	{
	
		public function __construct()
		{
			parent::__construct();
		}
	
		public function tracking_bot_report_chart_data()
		{

			$dir = get_option('home') . '/wp-content/plugins/' . basename(dirname(__FILE__));
			// move to the folder to include it.
			@chdir('../' . $dir);
			require_once('OFC/OFC_Chart.php');


			$server = $_SERVER['SERVER_NAME'];
			$text = "Crawl Report: $server";
			$title = new OFC_Elements_Title( $text );
			$title->set_style( "{font-size: 20px; font-family: Verdana; font-weight: bold; color: #000; text-align: center;}" );
			
			if($_GET['date'] != '')
			{
				$dates = explode('-', $_GET['date']);
				$start = mktime(0,0,0,$dates[1],1,$dates[0]);
				$themonth = date('F', mktime(0,0,0,$dates[1],1,$dates[0]));
				$end = mktime(23,59,59,($dates[1]+1),0,$dates[0]);
				$first_day = date('Y-m-d', $end);
				$last_day = date('Y-m-d', $start); // 30 days ago
			}
			else
			{
				$last_day = date('Y-m-d', strtotime('-1 month')); // 30 days ago
			}

			list($year,$month,$day) = explode('-', $last_day);

			$last_visit_time = mktime( 0, 0, 0, $month, $day, $year);

			$bots = $this->wpdb->get_results("SELECT DATE(FROM_UNIXTIME(`visit_time`)) `visit_date`,`robot_name`,COUNT(*) `total` FROM $this->sbtracking_table WHERE `visit_time` >= '$start' AND `visit_time` <= '$end' GROUP BY `visit_date`,`robot_name`");

			if($_GET['page_url'] != '')
			{
				$bots = $this->wpdb->get_results("SELECT DATE(FROM_UNIXTIME(`visit_time`)) `visit_date`,`robot_name`,COUNT(*) `total` FROM $this->sbtracking_table WHERE `visit_time` >= '$start' AND `visit_time` <= '$end' AND `page_url` = '" . addslashes($_GET['page_url']) . "' GROUP BY `visit_date`,`robot_name`");

			}

			$total['google'] 		= 0;
			$total['yahoo']  		= 0;
			$total['msn']    		= 0;
			$total['technorati']    = 0;

			$max = 10;

			if(sizeof($bots) == 0)
			{
				$b = new stdClass;
				$b->visit_date = $last_day;
				$robots = explode(',', $_GET['robots']);
				$b->robot_name = $robots[0];
				$b->total = 0;

				$bots = array($b);

			}

			$g = $y = $m = 0;
			
			foreach ($bots as $bot) {

				while ($bot->visit_date > $last_day) {

					$max = max($max, $total['google'], $total['yahoo'], $total['msn'], $total['technorati']);

					$google_counts[] 		= $total['google'];
					$yahoo_counts[]  		= $total['yahoo'];
					$msn_counts[]    		= $total['msn'];
					$technorati_counts[] 	= $total['technorati'];

					$days[]          		= date('d M', $last_visit_time);

					$total['google'] 		= 0;
					$total['yahoo']  		= 0;
					$total['msn']    		= 0;
					$total['technorati'] 	= 0;

					$last_visit_time		= strtotime($last_day) + 86400;
					$last_day 				= date('Y-m-d', $last_visit_time);

				}

				$t[$bot->visit_date][] = array($bot->robot_name => intval($bot->total));
				$total[$bot->robot_name] = intval($bot->total);

			}

			$max = max($max, $total['google'], $total['yahoo'], $total['msn'], $total['technorati']);

			$google_counts[] 		= $total['google'];
			$yahoo_counts[]  		= $total['yahoo'];
			$msn_counts[]    		= $total['msn'];
			$technorati_counts[] 	= $total['technorati'];

			$max = max($max, $total['google'], $total['yahoo'], $total['msn'], $total['technorati']);

			$days[] = date('d M', $last_visit_time);

			#var_dump($google_counts);

			$bots = $total = '';


			if($_GET['page_url'] == '')
			{
				$bots = $this->wpdb->get_results("SELECT page_url, DATE(FROM_UNIXTIME(`visit_time`)) `visit_date`,`robot_name` FROM $this->sbtracking_table WHERE `visit_time` >= '$start' AND `visit_time` <= '$end' GROUP BY page_url, robot_name, visit_date ORDER BY visit_date");
			}
#			else
#			{
#				$bots = $this->wpdb->get_results("SELECT page_url, DATE(FROM_UNIXTIME(`visit_time`)) `visit_date`,`robot_name` FROM $this->sbtracking_table WHERE `visit_time` >= '$start' AND `visit_time` <= '$end' AND `page_url` = '" . $_GET['page_url'] . "' GROUP BY page_url, robot_name, visit_date ORDER BY visit_date");
#			}

			$g = $y = $m = 0;
	
			$datelast_u = date('Y-m-d', $start);
	
			foreach ($bots as $bot) {
				
				if($bot->visit_date != $datelast_u)
				{
	
					$max = max($max, $g, $y, $m);
	
					$days_u[]          		= date('d M', $last_visit_time_u);
					$last_visit_time_u		= strtotime($last_day_u) + 86400;
					$last_day_u 				= date('Y-m-d', $last_visit_time_u);
	
	#					$t[$datelast_u][] = array('google' => $g);
	#					$t[$datelast_u][] = array('yahoo' => $y);
	#					$t[$datelast_u][] = array('msn' => $m);
	
					#$total[$bot->robot_name] = intval($bot->total);
					$google_counts_u[] 		= $g;
					$yahoo_counts_u[]  		= $y;
					$msn_counts_u[]    		= $m;
					$technorati_counts_u[] 	= $t;
	
					$datelast_u = $bot->visit_date;
					$g = $y = $m = 0;
	
				}
				else
				{
	
					switch($bot->robot_name)
					{
						case 'google':
							$g++;
						break;
						case 'yahoo':
							$y++;
						break;
						case 'msn':
							$m++;
						break;
					}
	
				}
			}
	
			$google_counts_u[] 		= $g;
			$yahoo_counts_u[]  		= $y;
			$msn_counts_u[]    		= $m;
	
			$g = $y = $m = '';

			$line_1 = new OFC_Charts_Line();
			$line_1->set_values( $google_counts );
			$line_1->set_halo_size( 0 );
			$line_1->set_width( 2 );
			$line_1->set_dot_size( 5 );
			$line_1->set_colour('#FBB829');
			$line_1->set_key( 'Google', 10 );

			$line_1_u = new OFC_Charts_Line();
			$line_1_u->set_values( $google_counts_u );
			$line_1_u->set_halo_size( 0 );
			$line_1_u->set_width( 2 );
			$line_1_u->set_dot_size( 5 );
			$line_1_u->set_colour('#ffd886');
			$line_1_u->set_key( 'Google Unique', 10 );

			$line_2 = new OFC_Charts_Line();
			$line_2->set_values( $yahoo_counts );
			$line_2->set_halo_size( 0 );
			$line_2->set_width( 2 );
			$line_2->set_dot_size( 5 );
			$line_2->set_colour('#2947FB');
			$line_2->set_key( 'Yahoo', 10 );

			$line_2_u = new OFC_Charts_Line();
			$line_2_u->set_values( $yahoo_counts_u );
			$line_2_u->set_halo_size( 0 );
			$line_2_u->set_width( 2 );
			$line_2_u->set_dot_size( 5 );
			$line_2_u->set_colour('#9999ff');
			$line_2_u->set_key( 'Yahoo Unique', 10 );

			$line_3 = new OFC_Charts_Line();
			$line_3->set_values( $msn_counts );
			$line_3->set_halo_size( 0 );
			$line_3->set_width( 2 );
			$line_3->set_dot_size( 5 );
			$line_3->set_colour('#FB2929');
			$line_3->set_key( 'Bing', 10 );

			$line_3_u = new OFC_Charts_Line();
			$line_3_u->set_values( $msn_counts_u );
			$line_3_u->set_halo_size( 0 );
			$line_3_u->set_width( 2 );
			$line_3_u->set_dot_size( 5 );
			$line_3_u->set_colour('#ff9999');
			$line_3_u->set_key( 'Bing Unique', 10 );

			$line_4 = new OFC_Charts_Line();
			$line_4->set_values( $technorati_counts );
			$line_4->set_halo_size( 0 );
			$line_4->set_width( 2 );
			$line_4->set_dot_size( 5 );
			$line_4->set_colour('#51FB29');
			$line_4->set_key( 'Technorati', 10 );

			$line_4_u = new OFC_Charts_Line();
			$line_4_u->set_values( $technorati_counts_u );
			$line_4_u->set_halo_size( 0 );
			$line_4_u->set_width( 2 );
			$line_4_u->set_dot_size( 5 );
			$line_4_u->set_colour('#51FB29');
			$line_4_u->set_key( 'Technorati', 10 );

			$y = new OFC_Elements_Axis_Y();
			$y->set_range( 0, $max, 500);
			
			$x_legend = new OFC_Elements_Legend_X( 'Day of ' . $themonth );
			$x_legend->set_style( '{font-size: 16px; color: #778877}' );

			$y_legend = new OFC_Elements_Legend_Y( 'Number');
			$y_legend->set_style( '{font-size: 16px; color: #778877}' );

			$chart = new OFC_Chart();
			$chart->set_title( '' );

			$x = new OFC_Elements_Axis_X();
			$x->set_labels_from_array($days);
			$chart->set_x_axis( $x );

			$chart->set_x_legend( $x_legend );
			$chart->set_y_legend( $y_legend );

			if(stristr($_GET['robots'], 'google')){
				$chart->add_element( $line_1 );
				if($_GET['page_url'] == ''){$chart->add_element( $line_1_u );}
			}
			if(stristr($_GET['robots'], 'yahoo')){
				$chart->add_element( $line_2 );
				if($_GET['page_url'] == ''){$chart->add_element( $line_2_u );}
			}
			if(stristr($_GET['robots'], 'msn')){
				$chart->add_element( $line_3 );
				if($_GET['page_url'] == ''){$chart->add_element( $line_3_u );}
			}
			if(stristr($_GET['robots'], 'technorati')){
				$chart->add_element( $line_4 );
				if($_GET['page_url'] == ''){$chart->add_element( $line_4_u );}
			}

			$chart->set_y_axis( $y );

			return $chart->toPrettyString();

		}

	}

	if ($_GET['chart_data']==1) {
		header('Content-type: text/plain');
		$chartData = new b3_chartData();
		echo $chartData->tracking_bot_report_chart_data();
		die();
	}
	
	
	function bytesToSize1024($bytes, $precision = 2)
	{
		// human readable format -- powers of 1024
		//
		$unit = array('B','KB','MB','GB','TB','PB','EB');
	
		return @round(
			$bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
		).' '.$unit[$i];
	}

?>