<?php
/** 
 * 
 * Plugin Name: Listing Plugin
 * Description: This is a Test Plugin
 * Version: 1.0.0
 * Text Domain: options-plugin
 * 
*/

if (!defined('ABSPATH')) {
      die('You cannot be here');
}

if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}
require_once( ABSPATH . 'wp-admin/includes/taxonomy.php');

define('MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

include_once MY_PLUGIN_PATH . 'reservations.php';
include_once MY_PLUGIN_PATH . 'api_custom_settings.php';
include_once MY_PLUGIN_PATH . 'a_ajax_actions.php';

require_once(MY_PLUGIN_PATH . '/vendor/autoload.php');

function calendar_enque_scripts_and_styles() {
    // echo 'enque scripts';
    // wp_register_script( 'zabuto-calendar-jquery-js', plugins_url().'/listing_plugin/calendar-2/lib/jquery/jquery.js', array( '' ), '1.0', true );
    // wp_register_script( 'zabuto-calendar-js', plugins_url().'/listing_plugin/calendar-2/dist/zabuto_calendar.min.js', array( 'zabuto-calendar-jquery-js' ), '1.0', true );
    // wp_register_script( 'calendar-script', plugins_url().'/listing_plugin/calendar-script.js', array( '' ), '1.0', true );
	// wp_enqueue_script( 'zabuto-calendar-jquery-js');
    // wp_enqueue_script( 'zabuto-calendar-js');
    // wp_enqueue_script( 'calendar-script');

    // wp_enqueue_style( 'zabuto-calendar-style', plugins_url().'/listing_plugin/calendar-2/dist/zabuto_calendar.min.css', '', '1.0' );
    // wp_enqueue_style( 'zabuto-calendar-style', plugin_dir_url().'/calendar-2/dist/zabuto_calendar.min.css');
    // wp_enqueue_script('zabuto-calendar-js',plugin_dir_url().'/calendar-2/dist/zabuto_calendar.min.js',array( 'jquery' ), false, true);
    // wp_enqueue_script( 'calendar-script', plugin_dir_url() . 'calendar-script.js', array('jquery'), false, true );
}

add_action( 'wp_enqueue_scripts', 'calendar_enque_scripts_and_styles' );


add_shortcode('listing', 'show_listing_form');

add_action('rest_api_init', 'create_rest_endpoint');

add_action('init', 'create_listing_page');

add_action( 'init', 'custom_listing_country_taxonomy', 0 );

add_action( 'init', 'custom_listing_city_taxonomy', 0 );

add_action( 'init', 'custom_listing_states_taxonomy', 0 );

add_action( 'init', 'custom_listing_amenity_taxonomy', 0 );

add_action( 'init', 'custom_listing_type_taxonomy', 0 );

add_action('admin_menu', 'add_listing_setting_submenu');

add_action('add_meta_boxes', 'create_meta_box');

add_filter('manage_listing_posts_columns', 'custom_listing_columns');

add_action('manage_listing_posts_custom_column', 'fill_listing_columns', 10, 2);

add_action('admin_init', 'setup_search');

function add_listing_setting_submenu() {
    add_submenu_page(
        'edit.php?post_type=listing',
        __( 'Listing Settings', 'textdomain' ),
        __( 'Settings', 'textdomain' ),
        'manage_options',
        'listing_settings',
        'listing_settings_page_callback' 
    );
}

/**
 * Display callback for the submenu page.
 */
function listing_settings_page_callback() { 
    ?>
    <div class="wrap">
        <h1><?php _e( 'Listing Settings', 'myTestSite' ); ?></h1>
        <div id="form_success" style="background-color:green; color:#fff;"></div>
        <div id="form_error" style="background-color:red; color:#fff;"></div>
        <button style="background-color: #175D72;color: white; padding: 10px 25px; border: 1px solid #175D72; border-radius: 5px;" id="sync-btn">Sync</button>
        <button style="background-color: #D4403A;color: white; padding: 10px 25px; border: 1px solid #D4403A; border-radius: 5px;" id="delete-all-btn">Delete all listings</button>
    </div>
 
    <script>
        jQuery(document).ready(function($){
            $("#sync-btn").click( function(event){
                console.log('clicked');
                $(this).html('Syncing Data ...');
                $.ajax({
                        type:"POST",
                        url: "<?php echo get_rest_url(null, 'sync/sync');?>",
                        data: "",
                        success:(res)=>{
                              $(this).html('Sync');
                              $("#form_success").html('Data Synced Successfully!!').fadeIn();
                              console.log('data sync successfull!!');
                        },
                        error: function(err){
                            console.log(err, 'err');
                            $("#form_error").html("There was an error while syncing the data!!").fadeIn();
                            console.log('data sync failed!!');
                        }
                  })
            });

            $("#delete-all-btn").click( function(event){
                console.log('clicked');
                $(this).html('Deleting all listing ...');
                $.ajax({
                        type:"POST",
                        url: "<?php echo get_rest_url(null, 'listing/delete-all');?>",
                        data: "",
                        success:(res)=>{
                              $(this).html('Sync');
                              $("#form_success").html('Listing Deletion Success!!').fadeIn();
                              console.log('data deletion successfull!!')

                        },
                        error: function(err){
                            console.log(err, 'err');
                            $("#form_error").html("There was an error while Deleting data!!!").fadeIn();
                            console.log('data deletion failed!!')
                        }


                  })
            });

            // delete-all-btn
        });
    </script>

    <?php
}


function setup_search()
{

      // Only apply filter to listing page

      global $typenow;

      if ($typenow === 'listing') {

            add_filter('posts_search', 'listing_search_override', 10, 2);
      }
}

function listing_search_override($search, $query)
{
      // Override the listing page search to include custom meta data

      global $wpdb;

      if ($query->is_main_query() && !empty($query->query['s'])) {
            $sql    = "
              or exists (
                  select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                  and meta_key in ('name','country','city', 'state')
                  and meta_value like %s
              )
          ";
            $like   = '%' . $wpdb->esc_like($query->query['s']) . '%';
            $search = preg_replace(
                  "#\({$wpdb->posts}.post_title LIKE [^)]+\)\K#",
                  $wpdb->prepare($sql, $like),
                  $search
            );
      }

      return $search;
}

function fill_listing_columns($column, $post_id){

      switch ($column) {

            case 'name':
                  echo esc_html(get_post_meta($post_id, 'name', true));
                  break;

            case 'type':
                    echo esc_html(get_post_meta($post_id, 'type', true));
                    break;
            case 'price':
                echo esc_html(get_post_meta($post_id, 'price', true));
                break;

            case 'currnecy':
                echo esc_html(get_post_meta($post_id, 'currnecy', true));
                break;
            
            case 'address':
                echo esc_html(get_post_meta($post_id, 'address', true));
                break;

            case 'city':
                  echo esc_html(get_post_meta($post_id, 'city', true));
                  break;

            case 'country':
                  echo esc_html(get_post_meta($post_id, 'country', true));
                  break;

            case 'state':
                  echo esc_html(get_post_meta($post_id, 'state', true));
                  break;
      }
}

function custom_listing_columns($columns)
{
      // Edit the columns for the listing table

      $columns = array(

            'cb' => $columns['cb'],
            'name' => __('Name', 'listing_plugin'),
            'type' => __('Type', 'listing_plugin'),
            'price' => __('Price', 'listing_plugin'),
            'currency' => __('Currency', 'listing_plugin'),
            'address' => __('Address', 'listing_plugin'),
            'city' => __('City', 'listing_plugin'),
            'country' => __('Country', 'listing_plugin'),
            'state' => __('State', 'listing_plugin'),
            'date' => 'Date',

      );

      return $columns;
}

function hcf_save_meta_box( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( $parent_id = wp_is_post_revision( $post_id ) ) {
        $post_id = $parent_id;
    }
    $fields = [
        'name',
        'type',
        'description',
        'guest',
        'rooms',
        'beds',
        'baths',
        'address',
        'city',
        'country',
        'amenities',
        'price',
        'currency',
        'feature_image',
        'latitude',
        'longitude',
        'booked_dates'
    ];
    foreach ( $fields as $field ) {
        if ( array_key_exists( $field, $_POST ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
        }
     }
}
add_action( 'save_post', 'hcf_save_meta_box' );

function create_meta_box()
{
      // Create custom meta box to display submission

      add_meta_box('custom_contact_form', 'Listing Details', 'display_listing', 'listing');
}

function display_listing()
{
      // Display individual submission data on it's page

      $postmetas = get_post_meta( get_the_ID() );

      ?>
        <div class="hcf_box">
                <style scoped>
                    .hcf_box{
                        display: grid;
                        grid-template-columns: max-content 1fr;
                        grid-row-gap: 10px;
                        grid-column-gap: 20px;
                    }
                    .hcf_field{
                        display: contents;
                    }
                </style>
      <?php

      foreach($postmetas as $key => $value){
        if($key !== '_edit_lock' && $key !== '_edit_last' && $key !== '_thumbnail_id'){
            if($key !== 'booked_dates'){
        // echo '<li><strong>' . $key . ':</strong> ' . $value[0] . '</li>';
        ?>
                <p class="meta-options hcf_field">
                    <label for="<?php echo $key?>"><?php echo $key?></label>
                    <input id="<?php echo $key?>"
                        type="text"
                        name="<?php echo $key?>"
                        value="<?php echo $value[0];?>">
                </p>
        <?php
            }elseif($key == 'booked_dates'){
        ?>
            <p class="meta-options hcf_field">
                    <label for="<?php echo $key?>"><?php echo $key?></label>
                    <span id="<?php echo $key?>"><?php echo $value[0];?></span>
            </p>
        <?php        
            }
    }}

      echo '</div>';
}

function create_listing_page()
{
      $args = [

            'public' => true,
            'has_archive' => true,
            'menu_position' => 30,
            'labels' => [
                  'name' => 'Listings',
                  'singular_name' => 'Listing',
            ],
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'capability_type' => 'post',
            'capabilities' => array(
                  'create_posts' => false,
            ),
            'map_meta_cap' => true
      ];

    register_post_type('listing', $args);
}

// Register Taxonomy for City
function custom_listing_city_taxonomy() {
    $labels = array(
        'name'                       => 'Cities',
        'singular_name'              => 'City',
        'menu_name'                  => 'Cities',
        'all_items'                  => 'All Cities',
        'parent_item'                => 'Parent City',
        'parent_item_colon'          => 'Parent City:',
        'new_item_name'              => 'New City Name',
        'add_new_item'               => 'Add New City',
        'edit_item'                  => 'Edit City',
        'update_item'                => 'Update City',
        'view_item'                  => 'View City',
        'separate_items_with_commas' => 'Separate cities with commas',
        'add_or_remove_items'        => 'Add or remove cities',
        'choose_from_most_used'      => 'Choose from the most used cities',
        'popular_items'              => 'Popular Cities',
        'search_items'               => 'Search Cities',
        'not_found'                  => 'No cities found',
        'no_terms'                   => 'No cities',
        'items_list'                 => 'Cities list',
        'items_list_navigation'      => 'Cities list navigation',
    );
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
    );
    register_taxonomy( 'city', 'listing', $args );
}

// Register Taxonomy for Country
function custom_listing_country_taxonomy() {
    $labels = array(
        'name'                       => 'Countries',
        'singular_name'              => 'Country',
        'menu_name'                  => 'Countries',
        'all_items'                  => 'All Countries',
        'parent_item'                => 'Parent Country',
        'parent_item_colon'          => 'Parent Country:',
        'new_item_name'              => 'New Country Name',
        'add_new_item'               => 'Add New Country',
        'edit_item'                  => 'Edit Country',
        'update_item'                => 'Update Country',
        'view_item'                  => 'View Country',
        'separate_items_with_commas' => 'Separate countries with commas',
        'add_or_remove_items'        => 'Add or remove countries',
        'choose_from_most_used'      => 'Choose from the most used countries',
        'popular_items'              => 'Popular Countries',
        'search_items'               => 'Search Countries',
        'not_found'                  => 'No countries found',
        'no_terms'                   => 'No countries',
        'items_list'                 => 'Countries list',
        'items_list_navigation'      => 'Countries list navigation',
    );
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
    );
    register_taxonomy( 'country', 'listing', $args );
}

// Register Taxonomy for States
function custom_listing_states_taxonomy() {
    $labels = array(
        'name'                       => 'States',
        'singular_name'              => 'State',
        'menu_name'                  => 'States',
        'all_items'                  => 'All States',
        'parent_item'                => 'Parent State',
        'parent_item_colon'          => 'Parent State:',
        'new_item_name'              => 'New State Name',
        'add_new_item'               => 'Add New State',
        'edit_item'                  => 'Edit State',
        'update_item'                => 'Update State',
        'view_item'                  => 'View State',
        'separate_items_with_commas' => 'Separate states with commas',
        'add_or_remove_items'        => 'Add or remove states',
        'choose_from_most_used'      => 'Choose from the most used states',
        'popular_items'              => 'Popular States',
        'search_items'               => 'Search States',
        'not_found'                  => 'No states found',
        'no_terms'                   => 'No states',
        'items_list'                 => 'States list',
        'items_list_navigation'      => 'States list navigation',
    );
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
    );
    register_taxonomy( 'states', 'listing', $args );
}

function custom_listing_amenity_taxonomy() {
    $labels = array(
        'name'                       => 'Amenities',
        'singular_name'              => 'Amenity',
        'menu_name'                  => 'Amenities',
        'all_items'                  => 'All Amenities',
        'parent_item'                => 'Parent Amenity',
        'parent_item_colon'          => 'Parent Amenity:',
        'new_item_name'              => 'New Amenity Name',
        'add_new_item'               => 'Add New Amenity',
        'edit_item'                  => 'Edit Amenity',
        'update_item'                => 'Update Amenity',
        'view_item'                  => 'View Amenity',
        'separate_items_with_commas' => 'Separate Amenities with commas',
        'add_or_remove_items'        => 'Add or remove Amenities',
        'choose_from_most_used'      => 'Choose from the most used Amenities',
        'popular_items'              => 'Popular Amenities',
        'search_items'               => 'Search Amenities',
        'not_found'                  => 'No Amenities found',
        'no_terms'                   => 'No Amenities',
        'items_list'                 => 'Amenities list',
        'items_list_navigation'      => 'Amenities list navigation',
    );
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
    );
    register_taxonomy( 'amenity', 'listing', $args );
}

function custom_listing_type_taxonomy() {
    $labels = array(
        'name'                       => 'Types',
        'singular_name'              => 'Type',
        'menu_name'                  => 'Types',
        'all_items'                  => 'All Types',
        'parent_item'                => 'Parent Type',
        'parent_item_colon'          => 'Parent Type:',
        'new_item_name'              => 'New Type Name',
        'add_new_item'               => 'Add New Type',
        'edit_item'                  => 'Edit Type',
        'update_item'                => 'Update Type',
        'view_item'                  => 'View Type',
        'separate_items_with_commas' => 'Separate type with commas',
        'add_or_remove_items'        => 'Add or remove types',
        'choose_from_most_used'      => 'Choose from the most used types',
        'popular_items'              => 'Popular types',
        'search_items'               => 'Search types',
        'not_found'                  => 'No types found',
        'no_terms'                   => 'No types',
        'items_list'                 => 'Types list',
        'items_list_navigation'      => 'Types list navigation',
    );
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
    );
    register_taxonomy( 'type', 'listing', $args );
}


function show_listing_form()
{
    // return 'page should load';
    include MY_PLUGIN_PATH . '/includes/templates/listing-form.php';
}

function create_rest_endpoint()
{
    // Create endpoint for front end to connect to WordPress securely to post form data
    register_rest_route('listing-form', 'submit', array(
        'methods' => 'POST',
        'callback' => 'handle_listing_submission'
    ));

    register_rest_route('sync', 'sync', array(
        'methods' => 'POST',
        'callback' => 'handle_sync'
    ));

    register_rest_route('listing', 'delete-all', array(
        'methods' => 'POST',
        'callback' => 'delete_all_listing'
    ));

    register_rest_route('post_meta', 'update', array(
        'methods' => 'POST',
        'callback' => 'update_custom_field'
    ));
}
function update_custom_field(){
    $post_id= $_REQUEST['post_id'];
    $field=$_REQUEST['field'];
    $value=$_REQUEST['value'];
    update_post_meta( $post_id, $field, sanitize_text_field( $value ) );
}
function delete_all_listing(){
    $args = array(
    'numberposts' => - 1,
    'post_type' => 'listing',
    'post_status'=>'any'
    );

    $posts_arr = get_posts($args);
    foreach($posts_arr as $pst) {
        $post_attachments = get_attached_media('',$pst->ID);
        foreach ($post_attachments as $attachment) {
            wp_delete_attachment( $attachment->ID, true );
        }
        
        $term_arr = get_the_terms($pst->ID, array('city', 'country', 'type', 'amenity'));
        if(!empty($term_arr) && !is_wp_error($term_arr)){
            
            foreach ($term_arr as $term) {
                wp_delete_term( $term->term_id, $term->taxonomy);
            }
        }

        wp_delete_post( $pst->ID, true);
    }
    return new WP_Rest_Response('saved', 200);
}

// function remove_all_attachments($pstID){
//     $post_attachments = get_attached_media('',$pstID);
//     foreach ($post_attachments as $attachment) {
//         wp_delete_attachment( $attachment->ID, true );
//     }
// }

// function remove_taxonomies($pstID){
//     $term_arr = get_the_terms($pstID, array('city', 'state', 'country', 'type', 'amenity'));
//     if(!empty($taxonomies)){
//         foreach ($term_arr as $term) {
//             wp_delete_term( $term->term_id, $term->taxonomy);
//         }
//     }
// }

function handle_sync(){
    
    if (($handle = fopen(encodeURI(get_option('siteurl') . '/wp-content/uploads/2023/06/'.'demo-listings.csv '), "r")) !== FALSE) {
        // Read the CSV file line by line
        $fp = fopen(encodeURI(get_option('siteurl') . '/wp-content/uploads/2023/06/'.'demo-listings.csv '), 'r');
        $csvReader = new yidas\csv\Reader($fp, [
            'encoding' => 'UTF-8'
        ]);
        
        $ddata= fgetcsv($handle);
        $firstRow = $csvReader->readRow();
        $remainingRows = $csvReader->readRows();
        foreach ( $remainingRows as $key => $remainingRow ) {
            if(!empty(sanitize_text_field($remainingRow[1]))){
                
                    $postarr = array(
                        'ID'=>sanitize_text_field($remainingRow[0]),
                        'post_title' => sanitize_text_field($remainingRow[1]),
                        'post_content' => sanitize_text_field($remainingRow[3]),
                        'post_type' => 'listing',
                        'post_status' => 'publish',
                        'meta_input'  => [
                            'name' => sanitize_text_field($remainingRow[1]),
                            'type' => sanitize_text_field($remainingRow[2]),
                            'description'=> sanitize_text_field($remainingRow[3]),
                            'guest'=>sanitize_text_field($remainingRow[4]),
                            'rooms'=> sanitize_text_field($remainingRow[5]),
                            'beds'=> sanitize_text_field($remainingRow[6]),
                            'baths'=> sanitize_text_field($remainingRow[7]),
                            'address'=> sanitize_text_field($remainingRow[8]),
                            'city'=> sanitize_text_field($remainingRow[9]),
                            'country'=> sanitize_text_field($remainingRow[10]),
                            'amenities'=> sanitize_text_field($remainingRow[11]),
                            'price'=> sanitize_text_field($remainingRow[12]),
                            'currency'=> sanitize_text_field($remainingRow[13]),
                            'feature_image'=> sanitize_text_field($remainingRow[14]),
                            'latitude'=> sanitize_text_field($remainingRow[16]),
                            'longitude'=> sanitize_text_field($remainingRow[17]),
                        ]
                    );
    
                    
                    if(!post_exists($remainingRow[0])){
                        $post_id = wp_insert_post($postarr);

                        set_featured_image_from_external_url( sanitize_text_field($remainingRow[14]), $post_id, true );
    
                        // wp_set_post_terms( $post_id, sanitize_text_field($remainingRow[9]), 'city', false );
                        // wp_set_post_terms( $post_id, sanitize_text_field($remainingRow[10]), 'country', false );
                        // wp_set_post_terms( $post_id, sanitize_text_field($remainingRow[2]), 'type', false );

                        $country_term= term_exists(sanitize_text_field($remainingRow[10]), 'country');
                        if(!$country_term){
                            $country_term = wp_insert_term($remainingRow[10], 'country');
                        }
                        if (!is_wp_error($country_term) && isset($country_term['term_id'])) {
                            // Assign the term to the listing
                            wp_set_object_terms($post_id, (int) $country_term['term_id'], 'country', true);
                        }

                        $city_term= term_exists(sanitize_text_field($remainingRow[9]), 'city');
                        if(!$city_term){
                            $city_term = wp_insert_term($remainingRow[9], 'city');
                        }
                        if (!is_wp_error($city_term) && isset($city_term['term_id'])) {
                            // Assign the term to the listing
                            wp_set_object_terms($post_id, (int) $city_term['term_id'], 'city', true);
                        }

                        $type_term= term_exists(sanitize_text_field($remainingRow[2]), 'type');
                        if(!$type_term){
                            $type_term = wp_insert_term($remainingRow[2], 'type');
                        }
                        if (!is_wp_error($type_term) && isset($type_term['term_id'])) {
                            // Assign the term to the listing
                            wp_set_object_terms($post_id, (int) $type_term['term_id'], 'type', true);
                        }

                        
                        $amenities_arr = explode(',', sanitize_text_field($remainingRow[11]));
        
                        foreach ($amenities_arr as $amenity) {
                            $amenity = trim($amenity); // Remove leading/trailing spaces
                            if(str_starts_with($amenity, "and"))  $amenity = substr_replace($amenity, '',  0, 3);
        
                            if (!empty($amenity)) {
                                // Create a new term or get the existing term
                                $term = term_exists($amenity, 'amenity');
        
                                if (!$term) {
                                    $term = wp_insert_term($amenity, 'amenity');
                                }
        
                                if (!is_wp_error($term) && isset($term['term_id'])) {
                                    // Assign the term to the listing
                                    wp_set_object_terms($post_id, (int) $term['term_id'], 'amenity', true);
                                }
                            }
                        }
        
                        $imgs_arr= explode(',', sanitize_text_field($remainingRow[15]));
        
                        foreach ($imgs_arr as $imgUrl) {
                            set_featured_image_from_external_url( sanitize_text_field($imgUrl), $post_id, false );
                        };
                    }else{
                        wp_update_post($postarr);
                    }
            }
        }

        fclose($fp);
        return new WP_Rest_Response('saved', 200);
      }
}

function set_featured_image_from_external_url($url, $post_id,  $thumb){
	
	if ( ! filter_var($url, FILTER_VALIDATE_URL) ||  empty($post_id) ) {
		return;
	}
	
	// Add Featured Image to Post
	$image_url 		  = preg_replace('/\?.*/', '', $url); // removing query string from url & Define the image URL here
	$image_name       = basename($image_url);
	$upload_dir       = wp_upload_dir(); // Set upload folder
	$image_data       = file_get_contents($url); // Get image data
    if(!is_wp_error($image_data)){
        $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
        $filename         = basename( $unique_file_name ); // Create image file name

        // Check folder permission and define file location
        if( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Create the image  file on the server
        file_put_contents( $file, $image_data );

        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );

        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

        // Include image.php
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // And finally assign featured image to post
        if($thumb)set_post_thumbnail( $post_id, $attach_id );
    }
}


function encodeURI($URI)
{
    return str_replace(array('%', '^', '+', '{', '[', '}', ']', '"', '|', '\\', '<', '>', ' '),
        array('%25', '%5E', '%2B', '%7B', '%5B', '%7D', '%5D', '%22', '%7C', '%5C', '%3C', '%3E', '%20'), $URI);
}


function handle_listing_submission($data)
{
      // Get all parameters from form
      $params = $data->get_params();
      // Set fields from the form
      $field_name = sanitize_text_field($params['name']);
      $field_city = sanitize_text_field($params['city']);
      $field_country = sanitize_text_field($params['country']);
      $field_state = sanitize_text_field($params['state']);


      // Check if nonce is valid, if not, respond back with error
      if (!wp_verify_nonce($params['_wpnonce'], 'wp_rest')) {

            return new WP_Rest_Response('Listing not saved!', 422);
      }

      // Remove unneeded data from paramaters
      unset($params['_wpnonce']);
      unset($params['_wp_http_referer']);

      // Send the email message
      $headers = [];

      $admin_email = get_bloginfo('admin_email');
      $admin_name = get_bloginfo('name');


      $postarr = [

            'post_title' => $params['name'],
            'post_type' => 'listing',
            'post_status' => 'publish'

      ];

      $post_id = wp_insert_post($postarr);

      // Loop through each field posted and sanitize it
      foreach ($params as $label => $value) {

            add_post_meta($post_id, sanitize_text_field($label), $value);
      }

      return new WP_Rest_Response('saved', 200);
}

add_action('admin_init', 'show_custom_post_images');

function show_custom_post_images() {
  add_meta_box(
    'Pictures ',
    __('Images attached to post ', 'domain'),
    'show_post_images',
    'listing',
    'normal',
    'default'
  );
}

function show_post_images() {
    global $post;
    $arguments = array(
      'numberposts' => - 1,
      'post_type' => 'attachment',
      'post_mime_type' => 'image',
      'post_parent' => $post->ID,
      'exclude' => get_post_thumbnail_id() ,
      'orderby' => 'menu_order',
      'order' => 'ASC'
    );
    $post_attachments = get_posts($arguments);
    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px">';
    foreach ($post_attachments as $attachment) {
      $preview = wp_get_attachment_image_src(
        $attachment->ID, 'wpestate_slider_thumb'
      );
      echo '<img style="width: 100px;" src="' . $preview[0] . '">';
    }
    echo "</div>";
  }
  
add_action('admin_init', 'add_calendar_meta_box');
// add_action('add_meta_boxes', 'add_calendar_meta_box');
function add_calendar_meta_box(){
    add_meta_box(
        'Calendar ',
        __('Listing Availability ', 'domain'),
        'show_calendar',
        'listing',
        'normal',
        'default'
      );
}
function show_calendar(){
    ?>
    <!-- <script type="text/javascript" src="<?php echo plugins_url();?>/listing_plugin/calendar-2/lib/jquery/jquery.js"></script>
    <script type="text/javascript" src="<?php echo plugins_url();?>/listing_plugin/calendar-2/dist/zabuto_calendar.min.js"></script>
    <link href="<?php echo plugins_url();?>/listing_plugin/calendar-2/dist/zabuto_calendar.min.css" rel="stylesheet"> -->

    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        .availability-wrapper{
            width: 100%;
            height: auto;
            padding: 20px;
        }
        .show-availability-container{
            width: 100%;
            text-align: center;
        }

        .show-availability-container .show-set-btn, .input-and-btns-container span{
            padding: 10px 20px;
            font-size: 1rem;
            color: #FFF;
            font-weight: 700;
            border-radius: 5px;
        }

        .input-and-btns-container{
            width: 100%;
            gap: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0,0,0,0.4);
        }

        .input-and-btns-container input{
            padding: 10px;
            font-size: 1rem;
            padding: 3px;
        }

        .bg-view{
            background: #f56a29;
        }

        .bg-add{
            background: #357ebd;
        }
        .bg-remove{
            background: #dc3545;
        }

        .booked{
            color: #fff!important;
            background-color: rgba(237, 48, 32, 0.2)!important;
        }

        .error-range{
            color: #dc3545!important;
            font-weight: 700;
            display: none;
            font-size: 0.8rem!important;
            margin-top: 20px;
        }
    </style>

    <div id="my-calendar"></div>
    <div class="availability-wrapper">
        <input type="hidden" id="hidden_booked_dates" name="booked_dates" value="<?php echo get_post_meta( get_the_ID(), 'booked_dates', true ); ?>">
        <div class="input-and-btns-container">
            <input type="text" name="booking-range" id="booking-range" value="" readonly></input>
            <span class="bg-add" id="add-range">Add To Booked Dates</span>
            <span class="bg-remove">Remove From Booked Dates</span>
        </div>
        <div class="show-availability-container">
            <span id="show-availability" class="bg-view show-set-btn">Show And Set Availability Calendar</span>
            <span class="error-range">Unavailable Date Range.</span>
        </div>
    </div>
    
    

    <script>
        let $el = $('#hidden_booked_dates');
        let e_val = "<?php echo get_post_meta( get_the_ID(), 'booked_dates', true ); ?>"
        var events = [];
        if(e_val){
            events= e_val.split(',').map(function(item) {
                console.log(item, 'dadasds');
                if(item){
                    console.log('dddd')
                    return {
                        start: moment(item.split('-')[0].trim()),
                        end: moment(item.split('-')[1].trim())
                    };
                }
                return;         
            });
        }
        
        console.log(events)
        let dateRangPickerMinDate= new Date();
        let dateRangPickerMaxDate = moment(dateRangPickerMinDate).add(2, 'years');
        var dateRanges = events;

        let DRPArgs={
            opens: 'center',
            autoUpdateInput: false,
            minDate: new Date(),
            maxDate: dateRangPickerMaxDate,
            locale: {
            format: 'YYYY/MM/DD'
            },
            isInvalidDate: function(date) {
                return dateRanges.reduce(function(bool, range) {
                    if(range){
                        return bool || (date >= range.start && date <= range.end);
                    }
                }, false);
            },
            isCustomDate: function(e) {
                if ( checkIfDateisBooked(e) ) {
                    return 'booked';
                } 
            }
        }

        function checkIfDateisBooked(date){
            return dateRanges.reduce(function(bool, range) {
                if(range){
                    return bool || (date >= range.start && date <= range.end);
                }
            }, false)
        }
        $('#show-availability').daterangepicker(DRPArgs)
                    .on('show.daterangepicker', findBookedClass)
                    .on('apply.daterangepicker', applyDateRangeEvent);

        function findBookedClass(ev, picker){
            $(picker.container[0]).find(".booked").attr("title","Unavailable date");
        }

        function applyDateRangeEvent(ev, picker){
            console.log(picker.startDate, picker.endDate);
            if(dateRanges){
                let isAvailableRange = false;
                console.log(dateRanges, 'dateRanges')
                if(dateRanges){
                    isAvailableRange = dateRanges.every(({start, end}, idx) => {
                    if(start && end){
                        if((start >= picker.startDate && start <= picker.endDate) || (end >= picker.startDate && end <= picker.endDate)){
                            return false;
                        }
                    }
                    return true;
                });
                }
                

                if(!isAvailableRange){
                    console.log('unavailable range');
                    $('.error-range').css('display', 'block');
                }

                if(isAvailableRange){
                    // showLoader();
                    console.log('available range');
                    $('#booking-range').val(picker.startDate.format('YYYY/MM/DD')+'-'+picker.endDate.format('YYYY/MM/DD'));
                    // $('#departure').val(picker.endDate.format('YYYY/MM/DD'));

                    //do the ajax call here
                }
            }
            
        }

        $('#add-range').click(updateBookingDates);

        function updateBookingDates(){
            let new_booking_range= $('#booking-range').val();
            e_val = (e_val && (e_val !== "")) ? `,${new_booking_range}` : `${new_booking_range}`;
            $.ajax({
                    method:"POST",
                    url: "<?php echo get_rest_url(null, 'post_meta/update');?>",
                    dataType: "text",
                    data: {
                        post_id: <?php echo get_the_ID();?>,
                        value: e_val,
                        field: 'booked_dates'
                    },
                    success:function(res){
                        console.log('updated field');
                        events.push({
                            start: moment(new_booking_range.split('-')[0]?.trim()),
                            end: moment(new_booking_range.split('-')[1]?.trim())
                        });
                        $('#booking-range').val("");
                    }
            })
        }

        function showLoader(){
            $('.loader-container').css('display', 'flex');
        }

        function hideLoader(){
            $('.loader-container').css('display', 'none')
        };

        
    </script>
    <?php
}