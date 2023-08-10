<?php

if (!defined('ABSPATH')) {
      die('You cannot be here');
}

if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}

add_action('init', 'init_reservation_post_type');

add_action('rest_api_init', 'reservations_endpoint');

add_action('add_meta_boxes', 'create_reservation_meta_box');

// add_filter('manage_listing_posts_columns', 'reservations_columns');

// add_action('manage_listing_posts_custom_column', 'reservations_columns', 10, 2);

function init_reservation_post_type()
{
      $r_args = [
            'public' => true,
            'has_archive' => true,
            'menu_position' => 30,
            'labels' => [
                'name' => 'Reservations',
                'singular_name' => 'Reservation',
            ],
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false,
            ),
            'map_meta_cap' => true
    ];

    register_post_type('reservation', $r_args);
}

function reservations_endpoint(){
    register_rest_route('reservation', 'add', array(
        'methods' => 'POST',
        'callback' => 'handle_add_reservation'
    ));

    function handle_add_reservation(){
        $listing_id= $_REQUEST['listing_id'];
        $listing_name= $_REQUEST['listing_name'];
        $reservation_date= $_REQUEST['reservation_date'];
        $no_of_guests= $_REQUEST['no_of_guests'];
        $total= $_REQUEST['total'];
        $t_s= $_REQUEST['t_s'];
    
        $reservation_id= $listing_id.'-'.$t_s;
        
        echo 'listing_id: '.$listing_id;
        echo 'listing_name: '.$listing_name;
        echo 'reservation_date: '.$reservation_date;
        echo 'no_of_guests: '.$no_of_guests;
        echo 'total: '.$total;
        echo 'reservation_id: '.$reservation_id;

        $args = array(
            'ID'=> $reservation_id,
            'post_title' => $reservation_id,
            'post_content' => 'Reservation details here....',
            'post_type' => 'reservation',
            'post_status' => 'publish',
            'meta_input'  => [
                'reservation_date' => $reservation_date,
                'status' => 'pending',
                'total_bill'=> $total,
                'no_of_guests'=>$no_of_guests,
            ]
        );
    
        
        if(!post_exists($reservation_id)){
            $post_id = wp_insert_post($args);
        }else{
            wp_update_post($args);
        }
    
        return new WP_Rest_Response('reservation saved!', 200);
    }
}

function create_reservation_meta_box()
{
      // Create custom meta box to display submission

      add_meta_box('reservation_meta_box', 'Reservatation Details', 'display_reservation', 'reservation');
}

function display_reservation()
{
      // Display individual submission data on it's page

      $postmetas = get_post_meta( get_the_ID() );
    ?>
      <div><?php echo get_the_ID()?></div>
    <?php
}

function reservations_columns($columns)
{
      // Edit the columns for the listing table

      $columns = array(

            'cb' => $columns['cb'],
            'name' => __('Name', 'Reservation'),
            'arrival' => __('arrival', 'Reservation'),
            'departure' => __('departure', 'Reservation'),
            'total' => __('total', 'Reservation'),

      );

      return $columns;
}
?>