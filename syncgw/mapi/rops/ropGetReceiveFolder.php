<?php
declare(strict_types=1);

/*
 * 	<RopGetReceiveFolder> handler class
 *
 *	@package	sync*gw
 *	@subpackage	Remote Operations (ROP) List and Encoding Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rops;

use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;

class ropGetReceiveFolder {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var RopGetReceiveFolder
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): RopGetReceiveFolder {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; response handler'), 'RopGetReceiveFolder'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse Rop request / response
	 *
	 *	@param 	- XML request document or binary request body
	 *	@param 	- XML response document
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 *	@return - TRUE = Ok; FALSE = Error
	 */
	public function Parse(&$req, XML &$resp, int $mod): bool {

		// [MS-OXCROPS]		2.2.1 ROP Input and Output Buffers
		// [MS-OXCRPC] 		2.2.2.1 RPC_HEADER_EXT Structure
		// [MS-OXCROPS] 	2.2.3.2.1 RopGetReceiveFolder ROP Request Buffer
		// [MS-OXCROPS] 	2.2.3.2.2 RopGetReceiveFolder ROP Success Response Buffer

		if ($mod == mapiHTTP::MKRESP) {

			// get decoded request body
			// set <ServerObjectHandleTable><Object>
			$req->xpath('//RopId[text()="GetReceiveFolder"]/..');
			$req->getItem();

			$handle = $req->getVar('InputHandleIndex', FALSE);

			$resp->xpath('//RopId[text()="GetReceiveFolder"]/..');
			$resp->getItem();

			$resp->updVar('InputHandleIndex', $handle);
		}

		return TRUE;
	}

}

?>