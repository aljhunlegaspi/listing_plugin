<?php

function api_custom_settings_menu_page() {

	add_menu_page(
		__( 'API settings', 'textdomain' ),
		__( 'API Settings', 'textdomain' ),
		'manage_options',
		'api_settings',
		'api_settings_page_callback',
		'',
		6
	);
}

add_action( 'admin_menu', 'api_custom_settings_menu_page' );

function api_settings_page_callback() {
	?>

	<div class="wrap">
		<h1><?php echo __( 'API Settings', 'textdomain' ); ?></h1>
		<form method="post" action="options.php" novalidate="novalidate">
			<?php settings_fields( 'api_settings' ); ?>
			<table class="form-table" role="presentation">
			<?php do_settings_fields( 'api_settings', 'default' ); ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>

	<?php
}



function register_additional_settings(){
    $args= array(
        'type'=> 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => null
    );

    //stripe keys
    register_setting('api_settings', 'stripe_secret_key', $args);
    register_setting('api_settings', 'stripe_publishable_key', $args);

    //paypal keysss
    register_setting('api_settings', 'paypal_secret_key', $args);
    register_setting('api_settings', 'paypal_client_id', $args);

    add_settings_field(
        'stripe_secret_key', 
        esc_html__('Stripe secret key', 'default'),
        'add_stripe_secret_key_field_field_callback',
        'api_settings'
    );

    add_settings_field(
        'stripe_publishable_key', 
        esc_html__('Stripe publishable key', 'default'),
        'add_stripe_publishable_key_field_field_callback',
        'api_settings'
    );

    //paypal

    add_settings_field(
        'paypal_secret_key', 
        esc_html__('Paypal secret key', 'default'),
        'add_paypal_secret_key_field_field_callback',
        'api_settings'
    );

    add_settings_field(
        'paypal_client_id', 
        esc_html__('Paypal client ID', 'default'),
        'add_paypal_client_id_field_field_callback',
        'api_settings'
    );
}

add_action('admin_init', 'register_additional_settings');

function add_stripe_secret_key_field_field_callback(){
    $value = get_option( 'stripe_secret_key' );
    echo '<input type="text" name="stripe_secret_key" value="' . esc_attr( $value ) . '" />';
}

function add_stripe_publishable_key_field_field_callback(){
    $value = get_option( 'stripe_publishable_key' );
    echo '<input type="text" name="stripe_publishable_key" value="' . esc_attr( $value ) . '" />';
}

function add_paypal_secret_key_field_field_callback(){
    $value = get_option( 'paypal_secret_key' );
    echo '<input type="text" name="paypal_secret_key" value="' . esc_attr( $value ) . '" />';
}

function add_paypal_client_id_field_field_callback(){
    $value = get_option( 'paypal_client_id' );
    echo '<input type="text" name="paypal_client_id" value="' . esc_attr( $value ) . '" />';
}

?>