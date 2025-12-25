<?php
/**
 * Plugin Name: ADDTOCRAFT
 * Description: One stop Endpoint for addtocraft functionalities and features.
 * Version: 1.0
 * Author: NBJ
 */

/*****************DO NOT EDIT THIS PART ********************/
function addtocraft_rest_permissions_check( $request ) {
    // return current_user_can( 'manage_options' ); // Only admins

    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_forbidden', 'You must be logged in.', [ 'status' => 401 ] );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'rest_forbidden', 'Access denied.', [ 'status' => 403 ] );
    }else{
		return current_user_can( 'manage_options' ); // Only admins
	}

}
/*****************DO NOT EDIT THIS PART ********************/

//Functions
function samplefunction() {
//Your code here
}


//Sample Creating API route
add_action( 'rest_api_init', function () {
    register_rest_route( 'omise/v1', '/gateway-status', [
        'methods'  => 'GET',
        'callback' => 'samplefunction',
// 		 'permission_callback' => '__return_true',
		'permission_callback' => 'addtocraft_rest_permissions_check',
    ] );
});

