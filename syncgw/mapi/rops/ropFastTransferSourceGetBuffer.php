<?php
declare(strict_types=1);

/*
 * 	<RopFastTransferSourceGetBuffer> handler class
 *
 *	@package	sync*gw
 *	@subpackage	Remote Operations (ROP) List and Encoding Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rops;

use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;
use syncgw\mapi\ics\icsHandler;

class ropFastTransferSourceGetBuffer {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var ropFastTransferSourceGetBuffer
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): ropFastTransferSourceGetBuffer {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; response handler'), 'RopFastTransferSourceGetBuffer'));
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
		// [MS-OXROPS]		2.2.12.4.1 RopFastTransferSourceGetBuffer ROP Request Buffer
		// [MS-OXCFOLD]		2.2.12.4.2 RopFastTransferSourceGetBuffer ROP Response Buffer
		// [MS-OXCDATA] 	2.10.1 PropertyTagArray Structure
		// [MS-AXCXICS]		2.2.3.1.1.5.1 RopFastTransferSourceGetBuffer ROP Request Buffer
		// [MS-AXCXICS]		2.2.3.1.1.5.2 RopFastTransferSourceGetBuffer ROP Response Buffer
		// [MS-AXCXICS]		2.2.4 FastTransfer Stream

		if ($mod != mapiHTTP::REQ) {
			$ics = icsHandler::getInstance();
			if (!$ics->Parse($req, $resp, $mod))
				return FALSE;
			$len = $resp->getVar('TransferBufferSize');
			$resp->updVar('InProgressCount', $len);
			$resp->updVar('TotalStepCount', $len);
		}
		if ($mod == mapiHTTP::MKRESP) {
			$req->xpath('//RopId[text()="FastTransferSourceGetBuffer"]/..');
			$resp->xpath('//RopId[text()="FastTransferSourceGetBuffer"]/..');
			while($req->getItem() !== NULL) {
				$ip = $req->savePos();
				$id = $req->getVar('InputHandleIndex', FALSE);
				$resp->getItem();
				$op = $resp->savePos();
				$resp->updVar('InputHandleIndex', $id, FALSE);
				$req->restorePos($ip);
				$resp->restorePos($op);
			}
		}

		return TRUE;
	}

}

?>