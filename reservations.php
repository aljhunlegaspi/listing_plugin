<?php

add_action('init', 'init_reservation_post_type');

add_action('rest_api_init', 'reservations_endpoint');

add_action('add_meta_boxes', 'create_reservation_meta_box');

add_filter('manage_reservation_posts_columns', 'reservations_columns');

add_action('manage_reservation_posts_custom_column', 'fill_reservation_columns', 10, 2);

function init_reservation_post_type()
{
      $r_args = [
            'public' => true,
            'has_archive' => true,
            'menu_position' => 30,
            'labels' => [
                'name' => 'Reservations',
                'singular_name' => 'Reservation',
                'slug'=>'reservation'
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
}
function handle_add_reservation(){
    $step= $_REQUEST['step'];
    $listing_id= $_REQUEST['listing_id'];
    $listing_name= $_REQUEST['listing_name'];
    $arrive= $_REQUEST['arrive'];
    $depart= $_REQUEST['depart'];
    $no_of_guests= $_REQUEST['no_of_guests'];
    $total= $_REQUEST['total'];
    $duration= $_REQUEST['duration'];
    $agree_polices_and_terms= $_REQUEST['agree_polices_and_terms'];
    $payment_status= $_REQUEST['payment_status'];
    $currency= $_REQUEST['currency'];

    //user details
    $fname= $_REQUEST['fname'];
    $lname= $_REQUEST['lname'];
    $phone= $_REQUEST['phone'];
    $email= $_REQUEST['email'];

    // echo $fname.' '.$lname;

    // check user first, save user if user does not exists

    
    $user = email_exists($email);
    if(!$user){
        $password= 'P@ssword';
        $user = wp_insert_user( array(
            'user_login' => $email,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $fname,
            'last_name' => $lname,
            'display_name' => $fname.' '.$lname,
            'role' => 'subscriber'
        ));
    }
    

    //check if user has exsisting reservation with the same listing ang has a status of pending
    $reservations = get_posts(array(
        'post_type'=> 'reservation',
        'meta_query'=> array(
            array(
                'key'=> 'user_id',
                'value'=> $user,
                'compare'=> '='
            ),
            array(
                'key'=> 'listing_id',
                'value'=> $listing_id,
                'compare'=> '='
            ),
            array(
                'key'=> 'status',
                'value'=> 'pending',
                'compare'=> '='
            ),
            array(
                'key'=> 'arrival',
                'value'=> $arrive,
                'compare'=> '='
            ),
            array(
                'key'=> 'departure',
                'value'=> $depart,
                'compare'=> '='
            ),
            array(
                'key'=> 'total_bill',
                'value'=> $total,
                'compare'=> '='
            )
        )
    ));

    // echo $listing_record.'listing_record';

    if(!$reservations){
        $args = array(
            'post_type' => 'reservation',
            'post_status' => 'publish',
            'meta_input'  => [
                'listing_id' => $listing_id,
                'arrival' => $arrive,
                'departure'=>$depart,
                'status' => 'pending',
                'payment_status' => $payment_status,
                'total_bill'=> $total,
                'duration'=>$duration,
                'user_id'=> $user,
                'fname'=> $fname,
                'lname'=> $lname,
                'user_phone'=> $phone,
                'user_email'=> $email,
                'agree_polices_and_terms'=> $agree_polices_and_terms, 
                'no_of_guests'=> $no_of_guests
            ]
        );

        if(($step !== 'three') && !checkListingDatesAvailability($listing_id, $arrive, $depart)){
            return new WP_Rest_Response('1 Selected Booking Dates is not available anymore please select another set of dates for your booking to re-start the reservation process, this reservation will be marked as canceled!', 400);
            die();
        }

        $reservation_id = wp_insert_post($args);

        $res_update_title_args = array(
            'ID'         => $reservation_id,
            'post_title' => 'Reservations-'.$reservation_id
        );
        
        wp_update_post( $res_update_title_args );
        // return new WP_Rest_Response('new reservation saved!', 200);
        return new WP_Rest_Response(array(
            'message'=> 'new reservation saved!',
            'data'=>array(
                'reservation_id'=> $reservation_id,
                'user_id'=> $user
            )
        ), 200);
    }

    $reservation_id= '';
    if($reservations){
        foreach ($reservations  as $key => $reservation) {
            $reservation_id= $reservation->ID;
            if(($step !== 'three') && !checkListingDatesAvailability($listing_id, $arrive, $depart)){
                $res_update_details_args = array(
                    'ID'         => $reservation->ID,
                    'meta_input'=>array(
                        'status' => 'cancelled',
                    )
                );
                wp_update_post($res_update_details_args);
                return new WP_Rest_Response('2 Selected Booking Dates is not available anymore please select another set of dates for your booking to re-start the reservation process, this reservation will be marked as canceled!', 400);     
            }

            if($step == 'one'){
                $res_update_details_args = array(
                    'ID'         => $reservation->ID,
                    'meta_input'=>array(
                        'user_id'=> $user,
                        'fname'=> $fname,
                        'lname'=> $lname,
                        'user_phone'=> $phone,
                        'user_email'=> $email,
                        'agree_polices_and_terms'=> $agree_polices_and_terms
                    )
                );
            }
            if($step == 'two' ){
                $res_update_details_args = array(
                    'ID'         => $reservation->ID,
                    'meta_input'=>array(
                        'agree_polices_and_terms'=> $agree_polices_and_terms
                    )
                );
            }

            if($step == 'three' ){
                if(isset($_REQUEST['payment_obj'])){
                    $payment_obj = $_REQUEST['payment_obj'];
                }

                if(isset($_REQUEST['payments'])){
                    $payments = $_REQUEST['payments'];
                }

                $res_update_details_args = array(
                    'ID'         => $reservation->ID,
                    'meta_input'=>array(
                        'payment_status'=> 'Paid',
                        'paid_ammount' => $payment_obj['paid_amount'],
                        'remaining_balance' => 0,
                        'payment_intent_id' => $payment_obj['payment_intent_id'],
                        'payment_method_id' => $payment_obj['payment_method_id'],
                        'payment_method' => 'card',
                        'paid_via'=> $payment_obj['paid_via'],
                        'status' => 'complete',
                        'payments' => $payments
                    )
                );

                if($payment_obj['partial'] === true || $payment_obj['partial'] === 'true'){
                    $res_update_details_args = array(
                        'ID'         => $reservation->ID,
                        'meta_input'=>array(
                            'payment_status'=> 'Partially paid',
                            'paid_ammount' => $payment_obj['paid_amount'],
                            'remaining_balance' => $payment_obj['full_ammount'] - $payment_obj['paid_amount'],
                            'payment_intent_id' => $payment_obj['payment_intent_id'],
                            'payment_method_id' => $payment_obj['payment_method_id'],
                            'payment_method' => 'card',
                            'paid_via'=> $payment_obj['paid_via'],
                            'status' => 'pending',
                            'payments' => $payments
                        )
                    );
                }

            //-----update booking dates in listing sec
                $listing_booked_dates = get_post_meta($listing_id, 'booked_dates', true);

                $listing_update_args= array(
                    'ID'         => $listing_id,
                    'meta_input'=>array(
                        'booked_dates'=> $listing_booked_dates.','.$arrive.'-'.$depart,
                    )
                );

                wp_update_post($listing_update_args);
            //-----update booking dates in listing sec end

            //-----sending email to admin and user sec

                $full_name= $fname.' '.$lname;

                emailToUser($reservation->ID, $arrive, $depart, $currency, $total, $no_of_guests, $full_name, $email);
                emailToAdmin($reservation->ID, $arrive, $depart, $currency, $total, $no_of_guests, $full_name, $email);

            //-----sending email to admin and user sec end

            }
            wp_update_post($res_update_details_args);
        }

        return new WP_Rest_Response(array(
            'message'=> 'reservation updated!',
            'data'=>array(
                'reservation_id'=> $reservation_id,
                'user_id'=> $user
            )
        ), 200);
        die();
    }
}

function checkListingDatesAvailability($listing_id, $arrive, $depart){
    $booked_dates= get_post_meta( $listing_id, 'booked_dates', true );
    $exploded_booked_dates = explode(",",$booked_dates);

    $valid_date= true;
    foreach($exploded_booked_dates as $dates) {
        $exp_dates = explode('-', trim($dates));
        if(($exp_dates[0] >= $arrive && $exp_dates[0] <= $depart) || ($exp_dates[1] >= $arrive && $exp_dates[1] <= $depart)){
            $valid_date= false;
            break;
        }
    }
    return $valid_date;
}

function emailToUser($reservation_id, $arrive, $depart, $currency, $total, $no_of_guests, $full_name, $email){
    // echo $reservation_id.'--'.$arrive.'--'.$depart.'--'.$currency.'--'.$total.'--'.$no_of_guests.'--'.$full_name.'--'.$email;
    $admin_email = get_option( 'admin_email' );
        $subject = 'Reservation Confirmation';

        $message = "
        <div style='background-color: #f3f6f9; padding: 4rem 0;'>
            <div
            style='
                width: 720px;
                margin: auto;
            '
            class='container'
            >
            <div class='header' style='padding: 2rem 0; width: 100%; text-align: center'>
                <img
                src='https://boostly.co.uk/wp-content/uploads/2022/04/Boostly-Alt-Logo-RGB-Transparent-Background-1024x240.png'
                style='width: 100%; max-width: 250px'
                />
            </div>
            <div class='body' style='
                padding: 1rem 0;
                flex-direction: column; 
                align-items: center; 
                justify-items:center;
                border-radius: 10px;
                box-shadow: 0px 2px 1px -1px rgba(0,0,0,0.2), 0px 1px 1px 0px rgba(0,0,0,0.14), 0px 1px 3px 0px rgba(0,0,0,0.12);
                background: #FFF;
                border-top: 10px solid #f16522;
            '>
                <div style='
                    width: 100%;
                    text-align: center;
                '>
                    <div style='font-size: 3rem; font-weight: bold; color: #049dd9'>THANK YOU</div>
                    <div style='
                        font-size: 0.9rem;
                        font-weight: 600;
                        color: rgba(0,0,0,0.4);
                    '>Your reservation has been received. Check your reservation details below.</div>
                </div>
                <div style='
                    position: relative;
                    padding: 2rem 0;
                '>
                    <div style='
                        width: 450px;
                        left: 0;
                        right: 0;
                        margin: 0 auto;
                        border: 2px solid rgba(241, 101, 34, 0.5);
                        border-radius: 5px;
                        padding: 20px;
                    '>
                    <ul style='
                        column-count: 1;
                        width: 100%;
                        list-style-type: none;
                        padding-left: 0px;
                    '>
                        <li style='margin-left: 0px!important'>
                            <div style='
                                display: flex;
                                align-items: center;
                                flex-direction: row;
                                width: 100%;
                                font-size: 1rem;
                                border-bottom: 1px solid rgba(0,0,0,0.2);
                                padding: 10px 0;
                                font-weight: 700;
                                color: #818181!important;
                            '>
                                <div style='width: 80%;'>Reservation ID</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$reservation_id</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%;'>Arrive Date</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$arrive</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%;'>Depart Date</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$depart</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%;'>Number of guest</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$no_of_guests</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%'>Total Price</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$currency $total</div>
                            </div>
                        </li>
                    </ul>
                        <p style='text-align:center'>Contact $admin_email for further inquiries.</p>
                    </div>
                    
                </div>
            </div>
            </div>
        </div>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);
}

function emailToAdmin($reservation_id, $arrive, $depart, $currency, $total, $no_of_guests, $full_name, $email){
    $admin_email = get_option( 'admin_email' );
        $subject = 'New Reservation';

        $message = "
        <div style='background-color: #f3f6f9; padding: 4rem 0;'>
            <div
            style='
                width: 720px;
                margin: auto;
            '
            class='container'
            >
            <div class='header' style='padding: 2rem 0; width: 100%; text-align: center'>
                <img
                src='https://boostly.co.uk/wp-content/uploads/2022/04/Boostly-Alt-Logo-RGB-Transparent-Background-1024x240.png'
                style='width: 100%; max-width: 250px'
                />
            </div>
            <div class='body' style='
                padding: 1rem 0;
                flex-direction: column; 
                align-items: center; 
                justify-items:center;
                border-radius: 10px;
                box-shadow: 0px 2px 1px -1px rgba(0,0,0,0.2), 0px 1px 1px 0px rgba(0,0,0,0.14), 0px 1px 3px 0px rgba(0,0,0,0.12);
                background: #FFF;
                border-top: 10px solid #f16522;
            '>
                <div style='
                    width: 100%;
                    text-align: center;
                '>
                    <div style='font-size: 3rem; font-weight: bold; color: #049dd9'>NEW RESERVATION</div>
                    <div style='
                        font-size: 0.9rem;
                        font-weight: 600;
                        color: rgba(0,0,0,0.4);
                    '>A new reservation has been added. Check your reservation details below.</div>
                </div>
                <div style='
                    position: relative;
                    padding: 2rem 0;
                '>
                    <div style='
                        width: 450px;
                        left: 0;
                        right: 0;
                        margin: 0 auto;
                        border: 2px solid rgba(241, 101, 34, 0.5);
                        border-radius: 5px;
                        padding: 20px;
                    '>
                       <ul style='
                        column-count: 1;
                        width: 100%;
                        list-style-type: none;
                        padding-left: 0px;
                       '>
                        <li style='margin-left: 0px!important'>
                            <div style='
                                display: flex;
                                align-items: center;
                                flex-direction: row;
                                width: 100%;
                                font-size: 1rem;
                                border-bottom: 1px solid rgba(0,0,0,0.2);
                                padding: 10px 0;
                                font-weight: 700;
                                color: #818181!important;
                            '>
                                <div style='width: 80%;'>Reservation ID</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$reservation_id</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%;'>Arrive Date</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$arrive</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%;'>Depart Date</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$depart</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%;'>Number of guest</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$no_of_guests</div>
                            </div>
                        </li>
                        <li style='margin-left: 0px!important'>
                            <div style='
                            display: flex;
                            align-items: center;
                            flex-direction: row;
                            width: 100%;
                            font-size: 1rem;
                            border-bottom: 1px solid rgba(0,0,0,0.2);
                            padding: 10px 0;
                            font-weight: 700;
                            color: #818181!important;
                        '>
                                <div style='width: 80%'>Total Price</div>
                                <div style='width: 30%;text-align: right;color: #f16522;font-weight: bolder;'>$currency $total</div>
                            </div>
                        </li>
                       </ul>
                        <p style='text-align:center'>Check reservation page for more details</p>
                    </div>
                    
                </div>
            </div>
            </div>
        </div>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, $message, $headers);
}

function create_reservation_meta_box($post)
{
      // Create custom meta box to display submission

      add_meta_box('reservation_meta_box', 'Reservatation Details', 'display_reservation', 'reservation');
}

function display_reservation($post)
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
            ?>
            <p class="meta-options hcf_field">
                <label for="<?php echo $key?>"><?php echo $key?></label>
                <input id="<?php echo $key?>"
                    type="text"
                    name="<?php echo $key?>"
                    value="<?php echo $value[0];?>">
            </p>
        
            <?php 
             }// end of for loop
    echo "</div>";
}

function reservations_columns($columns)
{
      // Edit the columns for the listing table

      $columns = array(
            'cb' => $columns['cb'],
            'name' => __('Name', 'reservation'),
            'arrival' => __('Arrival', 'reservation'),
            'departure' => __('Departure', 'reservation'),
            'duration' => __('Duration', 'reservation'),
            'status' => __('Status', 'reservation'),
            'total_bill' => __('Total bill', 'reservation'),
            'payment_status' => __('Payment status', 'reservation'),
      );

      return $columns;
}

function fill_reservation_columns($column, $post_id){

    switch ($column) {

            case 'name':
                echo esc_html(get_the_title($post_id));
                break;

            case 'arrival':
                echo esc_html(get_post_meta($post_id, 'arrival', true));
                break;
            case 'departure':
                echo esc_html(get_post_meta($post_id, 'departure', true));
                break;

            case 'status':
                echo esc_html(get_post_meta($post_id, 'status', true));
                break;

            case 'duration':
                echo esc_html(get_post_meta($post_id, 'duration', true)).' Days';
                break;

            case 'total_bill':
                echo esc_html(get_post_meta($post_id, 'currency', true)).'<strong>'.esc_html(get_post_meta($post_id, 'total_bill', true)).'</strong>';
                break;

            case 'payment_status':
                echo esc_html(get_post_meta($post_id, 'payment_status', true));
                break;
    }
}
?>