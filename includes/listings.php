<?php

if (!defined('ABSPATH')) {
      die('You cannot be here');
}

add_shortcode('listing', 'show_listing_form');

add_action('rest_api_init', 'create_rest_endpoint');

add_action('init', 'create_listing_page');

add_action( 'init', 'custom_listing_country_taxonomy', 0 );

add_action( 'init', 'custom_listing_city_taxonomy', 0 );

add_action( 'init', 'custom_listing_states_taxonomy', 0 );

add_action('add_meta_boxes', 'create_meta_box');

add_filter('manage_listing_posts_columns', 'custom_listing_columns');

add_action('manage_listing_posts_custom_column', 'fill_listing_columns', 10, 2);

add_action('admin_init', 'setup_search');

// add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

function enqueue_custom_scripts()
{

      // Enqueue custom css for plugin

      wp_enqueue_style('contact-form-plugin', MY_PLUGIN_URL . 'assets/css/contact-plugin.css');
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

function fill_listing_columns($column, $post_id)
{
      // Return meta data for individual posts on table

      switch ($column) {

            case 'name':
                  echo esc_html(get_post_meta($post_id, 'name', true));
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
            'city' => __('city', 'listing_plugin'),
            'country' => __('country', 'listing_plugin'),
            'state' => __('state', 'listing_plugin'),
            'date' => 'Date',

      );

      return $columns;
}

function create_meta_box()
{
      // Create custom meta box to display submission

      add_meta_box('custom_contact_form', 'Listing', 'display_listing', 'listing');
}

function display_listing()
{
      // Display individual submission data on it's page

      // $postmetas = get_post_meta( get_the_ID() );

      // echo '<ul>';

      // foreach($postmetas as $key => $value)
      // {

      //       echo '<li><strong>' . $key . ':</strong> ' . $value[0] . '</li>';

      // }

      // echo '</ul>';


      echo '<ul>';

      echo '<li><strong>Name:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'name', true)) . '</li>';
      echo '<li><strong>City:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'city', true)) . '</li>';
      echo '<li><strong>Country:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'country', true)) . '</li>';
      echo '<li><strong>State:</strong><br /> ' . esc_html(get_post_meta(get_the_ID(), 'state', true)) . '</li>';

      echo '</ul>';
}

function create_listing_page()
{

      // Create the submissions post type to store form submissions

      $args = [

            'public' => true,
            'has_archive' => true,
            'menu_position' => 30,
            'publicly_queryable' => false,
            'labels' => [

                  'name' => 'Listings',
                  'singular_name' => 'Listing',
                  'edit_item' => 'View Listings'

            ],
            'supports' => [false],
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
        'hierarchical'      => false,
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
        'hierarchical'      => false,
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
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => true,
    );
    register_taxonomy( 'states', 'listing', $args );
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
}


function handle_listing_submission($data)
{
      // Handle the form data that is posted

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


    //   wp_mail($recipient_email, $subject, $message, $headers);

      return new WP_Rest_Response('saved', 200);
}