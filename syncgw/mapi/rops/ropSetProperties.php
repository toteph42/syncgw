<?php
declare(strict_types=1);

/*
 * 	<RopSetProperties> handler class
 *
 *	@package	sync*gw
 *	@subpackage	Remote Operations (ROP) List and Encoding Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rops;

use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;

class ropSetProperties {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var ropSetProperties
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): ropSetProperties {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; response handler'), 'RopSetProperties'));
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
		// [MS-OXROPS]		2.2.8.6.1 RopSetProperties ROP Request Buffer
		// [MS-OXROPS] 		2.2.8.6.2 RopSetProperties ROP Success Response Buffer
		// [MS-OXROPS] 		2.2.8.6.3 RopSetProperties ROP Failure Response Buffer
		// [MS-OXCPRPT]		2.2.5 RopSetProperties ROP
		// [MS-OXCPRPT]		2.2.5.1 RopSetProperties ROP Request Buffer
		// [MS-OXCPRPT]		2.2.5.2 RopSetProperties ROP Response Buffer
		// [MS-OXCDATA]		2.11.4 TaggedPropertyValue Structure
		// [MS-OXCDATA] 	2.7 PropertyProblem Structure
		// [MS-OXCDATA] 	2.2.1.3.1 LongTermID Structure
		// [MS-OXCDATA] 	2.2.1.3 Global Identifier Structure

		if ($mod == mapiHTTP::MKRESP) {
			$req->xpath('//RopId[text()="SetProperties"]/..');
			$resp->xpath('//RopId[text()="SetProperties"]/..');
			while($req->getItem() !== NULL) {
				$ip = $req->savePos();
				$id = $req->getVar('InputHandleIndex', FALSE);
				$resp->getItem();
				$op = $resp->savePos();
				$resp->updVar('InputHandleIndex', $id, FALSE);
				$req->restorePos($ip);
				$resp->restorePos($op);
			}
			// set in skeleton
			// <ReturnValue>
			// <PropertyProblemCount>
		}

		return TRUE;
	}

}

?>