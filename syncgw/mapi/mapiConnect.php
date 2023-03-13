<?php
declare(strict_types=1);

/*
 * 	<Connect> handler class
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

use syncgw\lib\Debug; //3
use syncgw\lib\User;
use syncgw\lib\XML;

class mapiConnect extends mapiWBXML {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var mapiConnect
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiConnect {

	   	if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; request/response handler'), 'Connect'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', '<a href="https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-lcid" target="_blank">[MS-LCID]</a> '.
				      'Windows Language Code Identifier (LCID) Reference v15.0');
		$xml->addVar('Stat', _('Implemented'));

	}

	/**
	 * 	Parse <Connect> request / response
	 *
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 * 	@return	- new XML structure or NULL
	 */
	public function Parse(int $mod): ?XML {

		// [MS-OXCMAPIHTTP] 2.2.4.1.1 Connect Request Type Request Body
		// [MS-OXCMAPIHTTP]	2.2.4.1.2 Connect Request Type Success Response Body
		// [MS-OXCMAPIHTTP]	2.2.4.1.3 Connect Request Type Failure Response Body
		// [MS-OXCRPC] 		3.1.4.1 EcDoConnectEx Method (Opnum 10)
		// [MS-OXNSPI] 		2.2.1.2 Permitted Error Code Values
		// [MS-OXCDATA] 	2.4 Error Codes
		// [MS-OXCRPC] 		2.2.2.1 RPC_HEADER_EXT Structure
		// [MS-OXCRPC] 		2.2.2.2 AUX_HEADER Structure
		// [MS-OXCRPC] 		2.2.2.2.17 AUX_EXORGINFO Auxiliary Block Structure
		// [MS-OXCRPC] 		2.2.2.2.15 AUX_CLIENT_CONTROL Auxiliary Block Structure
		// [MS-OXCRPC] 		2.2.2.2.20 AUX_ENDPOINT_CAPABILITIES Auxiliary Block Structure
		// [MS-OXCRPC] 		3.1.4.1.1 <AuxiliaryBuffer> Extended Buffer Handling
		// [MS-OXCRPC] 		3.1.4.1.1.1.1 rgbAuxIn Input Buffer

		// load skeleton
		if (!($xml = parent::_loadSkel('skeleton/Connect', 'Connect', $mod)))
			return NULL;

		if ($mod == mapiHTTP::REQ || $mod == mapiHTTP::RESP)
			parent::Decode($xml, 'Connect');

		// specifies the display name of the user who is specified in the UserDn field of
        // the Connect request type request body
		else {
			$usr = User::getInstance();
        	if ($val = $usr->getVar('DisplayName'))
        		$val = $usr->getVar('EMailPrime');
        	if (Debug::$Conf['Script']) //3
        		$val = 'dummy@xxx.com'; //3
			$xml->updVar('DisplayName', $val);

			$xml->setTop();
		}

		return $xml;
	}

}

?>