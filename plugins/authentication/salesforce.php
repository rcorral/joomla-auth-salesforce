<?php
if ( !defined('_JEXEC') ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * @version		$Id: salesforce.php 1 2009-10-27 20:56:04Z rafael $
 * @package		SalesForce Authentication
 * @copyright	Copyright (C) 2010 'corePHP' / corephp.com. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 */

jimport( 'joomla.plugin.plugin' );

/**
 * SalesForce Authentication Plugin
 */
class plgAuthenticationSalesForce extends JPlugin
{
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.0
	 */
	function plgAuthenticationGMail(& $subject, $config) {
		parent::__construct($subject, $config);
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param   array 	$credentials Array holding the user credentials
	 * @param 	array   $options     Array of extra options
	 * @param	object	$response	Authentication response object
	 * @return	boolean
	 * @since 1.0
	 */
	function onAuthenticate( $credentials, $options, &$response )
	{
		require( JPATH_ROOT.DS.'plugins'.DS.'authentication'.DS.'salesforce'.DS.'helper.php');
		$message = '';
		$success = 0;

		// Everything has to be in a try otherwise, fatal stuff... yes death
		try {
			$this->params->set( 'adminLogin', $credentials['username'] );
			$this->params->set( 'adminPassword', $credentials['password'] );
			$sf = SF_Helper::get_instance( $this->params );

			$user = $sf->get_property( 'sforceLogin' );
			$response->status        = JAUTHENTICATE_STATUS_SUCCESS;
			$response->error_message = '';
			$response->email         = $user->userInfo->userEmail;
			$response->fullname      = $user->userInfo->userFullName;
			$response->username      = $credentials['username'];
		} catch( Exception $e ) {
			$response->email         = '';
			$response->fullname      = '';
			$response->username      = '';
			$response->status        = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = 'Failed to authenticate';

		}
	}
}
