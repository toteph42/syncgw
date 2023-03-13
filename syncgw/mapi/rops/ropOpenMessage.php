<?php
declare(strict_types=1);

/*
 * 	<RopOpenMessage> handler class
 *
 *	@package	sync*gw
 *	@subpackage	Remote Operations (ROP) List and Encoding Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rops;

use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;

class ropOpenMessage {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var RopOpenMessage
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): ropOpenMessage {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; response handler'), 'RopOpenMessage'));
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

		// [MS-OXROPS]		2.2.1 ROP Input and Output Buffers
		// [MS-OXCRPC] 		2.2.2.1 RPC_HEADER_EXT Structure
		// [MS-OXROPS]		2.2.6.1.1 RopOpenMessage ROP Request Buffer
		// [MS-OXROPS]

		if ($mod == mapiHTTP::MKRESP) {
			$req->xpath('//RopId[text()="OpenFolder"]/..');
			while($req->getItem() !== NULL) {
				$p = $req->savePos();
				$req->restorePos($p);
				$id = $req->getVar('GlobalCounter', FALSE);
				$resp->setVal($id);
				$req->restorePos($p);
			}
		}

		return TRUE;
	}

}

?>