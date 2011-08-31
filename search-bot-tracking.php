<?php

/*
Plugin Name: The Crawl Rate Tracker
Plugin URI: http://www.blogstorm.co.uk/wordpress-crawl-rate-tracker/
Description: This is a plugin to log every visit by search engine robots, and show the results in different reports.
Author: Patrick Altoft
Version: 2.0.2
Author URI: http://www.blogstorm.co.uk

Written By: Douglas Radburn - http://www.douglasradburn.co.uk
*/

error_reporting(E_ALL ^ E_NOTICE);

require 'open-flash-chart-object.php';
require 'sbtracking.php';

$sbTracking = new b3_sbTracking();

register_activation_hook( __FILE__, array($sbTracking, 'sbtracking_activation') );
add_action( 'init', array($sbTracking, 'trackingBotSearch') );
add_filter( 'the_content' , array($sbTracking, 'add_button') , 90 );
add_action( 'admin_menu' , array($sbTracking, 'admin_report_menu') );
add_action( 'wp_dashboard_setup', array($sbTracking,'setupDashboard') );

?>
