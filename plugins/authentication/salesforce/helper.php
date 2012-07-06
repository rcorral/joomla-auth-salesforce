<?php
if ( !defined('_JEXEC') ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * @version		$Id: salesforce.php 1 2009-10-27 20:56:04Z rafael $
 * @package		SalesForce Authentication
 * @copyright	Copyright (C) 2010 'corePHP' / corephp.com. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 */

class SF_Helper
{
	/**
	 * Gets the instance of the salesforce object
	 * If one doesn't exist it creates it
	 * 
	 * @since 1.0
	 * 
	 * @param array Parameters for the construction of SF object
	 * @param boolean True will automatically login the user
	 * @return object The instance of the salesforce object
	 */
	function get_instance( $params = array(), $login = true )
	{
		static $instance;

		if ( $instance ) {
			return $instance;
		}

		$construct = array(
			'adminLogin'    => $params->get( 'adminLogin' ),
			'adminPassword' => $params->get( 'adminPassword' ),
			// 'adminToken'    => $params->get( 'adminToken', '' ),
			'wsdl'          => $params->get( 'wsdl' ),
			'_sandbox'      => ( $params->get( 'dev_sandbox' ) ? '_sandbox' : '' )
		);

		require_once( dirname(__FILE__) .DS.'salesforce.class.php' );
		$instance = new SalesForce( $construct );

		if ( true == $login ) {
			$instance->login();
		}

		return $instance;
	}

	/**
	 * Will die out and display an error.
	 * Will also display a debug_print_backtrace()
	 * 
	 * @since 1.0
	 * 
	 * @param string|array An error that accurred
	 * @return void
	 **/
	private function error( $error )
	{
		myPrint( $error );

		debug_print_backtrace();

		die();
	}
}

if ( !function_exists('__sf_convert_chars') ) :
/*** Function for printing data* @return */
function __sf_convert_chars( $str )
{
	return htmlspecialchars( $str, ENT_NOQUOTES );
}
endif;

if ( !function_exists('myPrint') ) :
/*** Function for printing data* @return */
function myPrint( $var, $pre = true )
{
	if ( $pre )
		echo "<pre>";
	print_r( $var );
	if ( $pre )
		echo "</pre>";
}
endif;

?>