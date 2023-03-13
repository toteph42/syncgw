<?php
declare(strict_types=1);

/*
 * 	<DNToMId> handler class
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

use syncgw\lib\HTTP;
use syncgw\lib\XML;

class mapiDNToMId extends mapiWBXML {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var mapiDNToMId
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiDNToMId {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; request/response handler'), 'DnToMid'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse <DNToMId> request / response
	 *
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 * 	@return	- new XML structure or NULL
	 */
	public function Parse(int $mod): ?XML {

		// [MS-OXCMAPIHTTP] 2.2.5.4.1 DnToMinId Request Type Request Body
		// [MS-OXCMAPIHTTP] 2.2.5.4.2 DnToMinId Request Type Success Response Body
		// [MS-OXCMAPIHTTP] 2.2.5.4.3 DnToMinId Request Type Failure Response Body
		// [MS-OXNSPI] 		2.2.1.2 Permitted Error Code Values
		// [MS-OXCDATA] 	2.4 Error Codes
		// [MS-OXNSPI] 		2.2.9.1 MinimalEntryID
		// [MS-OXCRPC] 		3.1.4.1.1 <AuxiliaryBuffer> Extended Buffer Handling
		// [MS-OXCRPC] 		3.1.4.1.1.1.1 rgbAuxIn Input Buffer

		// load skeleton
		if (!($xml = parent::_loadSkel('skeleton/DNToMId', 'DNToMId', $mod)))
			return NULL;

		if ($mod == mapiHTTP::REQ || $mod == mapiHTTP::RESP)
			parent::Decode($xml, 'DNToMId');
		else {
			$http = HTTP::getInstance();
			$req = $http->getHTTPVar(HTTP::RCV_BODY);

			$p = $xml->savePos();
			$xml->updVar('MinimalIdCount', strval($req->xpath('//Name')));
			while ($name = $req->getItem()) {
				$xml->restorePos($p);
				if (stripos($name, '/CN=RECIPIENT') !== FALSE || stripos($name, '/CN=CONFIGURATION') !== FALSE) {
					$xml->updVar('MinimalEntryID', 'Handle01', FALSE);
					continue;
				}
				$xml->restorePos($p);
				if (stripos($name, '/o=') !== FALSE)
					$xml->updVar('MinimalEntryID', 'GlobalAddressList', FALSE);
			}
			$xml->setTop();
		}

		return $xml;
	}

}

?>