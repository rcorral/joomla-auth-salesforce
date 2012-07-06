<?php
if ( !defined('_JEXEC') ) { die( 'Direct Access to this location is not allowed.' ); }
/**
 * @version		$Id: salesforce.php 1 2009-10-27 20:56:04Z rafael $
 * @package		SalesForce Authentication
 * @copyright	Copyright (C) 2010 'corePHP' / corephp.com. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 */

require_once(dirname(__FILE__) . '/soapclient/SforcePartnerClient.php');
require_once(dirname(__FILE__) . '/soapclient/SforceEnterpriseClient.php');

class SalesForce
{
	private $sforceConnection, $sforceLogin = NULL, $soapClient;
	private $nodb = false;	// do not actually execute queries, only print them
	private $nosf = false;   // Do not call upsert() on SalesForce
	private $noemail = false;
  
	private $adminLogin = NULL;
	private $adminPassword = NULL;
	private $adminToken = NULL;
	private $wsdl = 'partner';	// 'partner' or 'enterprise'
	private $bccEmail = NULL;	// email where BCC of users' messages will go to

	private $configFields = array('mapGroups', 'tokenURL', 'wsdlURL');

	public $error;
	public $_sandbox;
	private $emailsSent = 0;

	public $sfFieldMappings = array(
#		array('AccountId', 'cb_AccountId'),
		array('LastName', 'lastname'),
		array('FirstName', 'firstname'),
	);

	/* TODO: empty */
	public static $groupMappings = array(
		'Default web access' => 'Default web access',
	);

	private static $groupIds = NULL;
	private static $roleIds = NULL;
	private /*string*/ $sfFields;

	private $sfpubkey = "-----BEGIN PUBLIC KEY-----
MIIEIjANBgkqhkiG9w0BAQEFAAOCBA8AMIIECgKCBAEAxTiKrtVxfZ+4nUaiYW7N
9sN0GDOX1qJigZJ78JsWWvIs5efFKy7H4bEzWOMvZYIiPZb0LEMWH9h8+Xung3fh
fVeET64Is2xBrr4Y0pTRwdBK+vILGTqa3xYc77nGluMqIq6sLfOmeNN8SzITyqJi
T0I/37gDZFRO/5JC3zmE49LwD5Itm3ICOajqmBPoYDj0EVZF9ViuQMHmmtLIxsOC
IKb07DAm0NPBHeWhGVsPVQ5/2QWK+XWPqVcWDikatDffscOFCkvH626M4mxTH2pv
96t+VG8ptyyZg0OTGN7tHYEFV8jyNbjX2FMg8PWn/niR5fsynk/Nye0bIiToVNZS
Ij1cEOGshk6iNSMOdZZTLlbR0fMJ44A8Xf4jXbIq3N8wTvrJBjBVKyvalis1lkfd
4yDeIOA9F6qnYOizGACuPv40yZNNCC3c8Vwccn8lt2tgpFGupO6f1tSM8YOgmh01
3ebkpfnsTgWuz14vwkmtZnysNQg++3wfpb4xEP6aaCpInyg2XnTHKbqrtLBgnVqj
wu+mWjyRaeoLeQNfbrEF+KH4G/2xShtOLuEYhpSm4pMdhnqAkm5toYiaMx7qo7WI
AZzmDW/hpH7kcejJgI7MtUW1KeqnfAbXEGsoME0NzrJYFXFGj2IRRX+QScFzue23
Mr4PFxGd4iDEK0p4KFy53p6EHmmuWtgNRZOSP1Z1LnPscUDBFAc6nKegVo9+HoqA
udkiYeWOQ6HhlD9bRQ40NOk5sQokTrdZY6KGpNcKjpfNybmXOQ2kh6+oMqbQiqaw
wiGh4Ud3L1Cps+0FPw4mhODIsGFBw3SrS6Lh2nCUrSGtJ7aho/ULI4MUWC7QlQGh
X3211vmDGu2Icj/StnfqeI2mnbzgQnO6ups3JDcCI9zjQs1MZhMWIgi5Zb9BDulh
8N2Thb9TGl4QqI5aGYK85MqVNKhA/lEV4T1jOGSXG0PS7OnRE5b/NuJboeaI2bP0
Ejv/Cw5N/jLtxV3NCm24nh2m8mYL1hCgKZLM95dPSwKLhF1k64fs5NHouh1SMDG+
FtJ08bYb56O1cY1gwG4yiS/r8ta0qerf0uMtm3CCMyZWDBqYDImqKvZgkqyN7omi
RAyUfzh7s7vPyls93iGzVWl4f7bapf0FvfOoQxBxHkFpXpLin6Kj3bLIV9TIaowy
BKzI5VilUoUJ6dW5SE4iSh9L0SkQ3YwMWXGlf0IRBk++PxLAquG/hmSIhv099NEX
yQHWg/5iz/tag2nu+DO/eAz9BoEG5bbWDU2kE33Nq/GguMU3Cbq0Q7CW8EGMtNw6
K0oGGCMNil3m2n+dCDC24DRpdAq4Z+CAK17nyumjiDaVxPVCTDZwW+cLSH2rCvAs
pQIDAQAB
-----END PUBLIC KEY-----
";

	private $debug = false;

	function __construct( $params )
	{
		foreach ( array( 'adminLogin', 'adminPassword', 'adminToken',
			'wsdl', '_sandbox' ) as $param
		) {
			if( isset( $params[$param] ) ) {
				$this->$param = $params[$param];
			}
		}

		if ( $this->wsdl == 'enterprise' ) {
			$this->sforceConnection = new SforceEnterpriseClient();
			$this->soapClient = $this->sforceConnection->createConnection(
				dirname(__FILE__) . DS.'soapclient'.DS."enterprise{$this->_sandbox}.wsdl.xml");
		} elseif ( $this->wsdl == 'partner' ) {
			$this->sforceConnection = new SforcePartnerClient();
			$this->soapClient = $this->sforceConnection->createConnection(
				dirname(__FILE__) . DS.'soapclient'.DS."partner{$this->_sandbox}.wsdl.xml");
		} else {
			// echo 'Neither enterprise or partner = wtf?';
		}


		/* Create a list od fields to pull from SF */
		$sfFieldArr = array();
		foreach ( $this->sfFieldMappings as $map ) {
			if ( strtolower($map[0]) != 'email' ) {
				$sfFieldArr[] = $map[0];
			}
		}
		$this->sfFields = implode( ', ', $sfFieldArr );

		/* Check if we're in debug mode */
		$cfg = new JConfig();
		$this->debug = ($cfg->debug > 0);
	}

	/*
	 * @param user, password - SalesForce.com credentials
	 */
	function login( $user = NULL, $password = NULL )
	{
		if ( $user === NULL || $password === NULL ) {
			$user = $this->adminLogin;
			$password = $this->adminPassword . $this->adminToken;
		}

		$this->error = false;

		if ( $this->sforceLogin !== NULL ) {
			return $this->sforceLogin;
		}

		try {
			if ( !$this->sforceConnection ) {
				$this->error = 'Connection is null';
				return false;
			}
			$result = $this->sforceLogin = $this->sforceConnection->login($user, $password);

			return $this->sforceLogin;
		} catch ( SoapFault $ex ) {
			$this->error = $ex->getMessage();
			if ( $this->debug ) {
				$this->error .= ' USING: user="' . $user . '" password="' . $password . '"';
			}

			return false;
		}

	}

	function query( $query )
	{
		$r = $this->sforceConnection->query( $query );
		$qr = new QueryResult($r);

		return $qr;
	}

	function upsert( $field_name, $sObject )
	{
		$r = $this->sforceConnection->upsert( $field_name, $sObject );

		return $r;
	}

	function create( $sObject, $field_name )
	{
		$r = $this->sforceConnection->create( $sObject, $field_name );

		return $r;
	}

	function setAssignmentRuleHeader( $header )
	{
		$this->sforceConnection->setAssignmentRuleHeader( $header );
	}

	function update( $sObjects )
	{
		$r = $this->sforceConnection->update( $sObjects );

		return $r;
	}

	function get_property( $property )
	{
		if ( isset( $this->$property ) ) {
			return $this->$property;
		} else {
			return '';
		}
	}

	function test()
	{
		// myPrint($this->sforceConnection->describeGlobal());
		die();
	}
}
