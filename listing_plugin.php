<?php
/** 
 * 
 * Plugin Name: Listing Plugin
 * Description: This is a Test Plugin
 * Version: 1.0.0
 * Text Domain: options-plugin
 * 
*/

if(!defined('ABSPATH')){
    die('Unauthorized Access !!');
}

if(!class_exists('ListingPlugin')){
    class ListingPlugin{
        public function __construct()
        {

              define('MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

              define('MY_PLUGIN_URL', plugin_dir_url( __FILE__ ));

              require_once(MY_PLUGIN_PATH . '/vendor/autoload.php');

        }

        public function initialize()
        {
            //   include_once MY_PLUGIN_PATH . 'includes/utilities.php';

            //   include_once MY_PLUGIN_PATH . 'includes/options-page.php';

              include_once MY_PLUGIN_PATH . 'includes/listings.php';
        }
    }

    $listingPlugin = new ListingPlugin;
    $listingPlugin->initialize();
}