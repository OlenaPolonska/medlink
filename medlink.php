<?php
/*
Plugin Name: Medlink test plugin
Description: The test plugin for Medlink Students
Text Domain: ml
Version: 1.0
Author: Olena Polonska
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'admin_notices', function() {
  if( !is_plugin_active('advanced-custom-fields/acf.php') )
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Warning: The Medlink test plugin needs Advanced Custom Fields to function', 'ml' ) . '</p></div>';
} );

require_once( 'medlink_class.php' );

add_shortcode( 'medlink', function( $args ) {
	global $Medlink;
	
	$html = $Medlink->render();	
	$html .= $Medlink->get_form();
    return $html;
} );

