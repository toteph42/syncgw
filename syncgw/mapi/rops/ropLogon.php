<?php
declare(strict_types=1);

/*
 * 	<RopLogon> handler class
 *
 *	@package	sync*gw
 *	@subpackage	Remote Operations (ROP) List and Encoding Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rops;

use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;

class ropLogon {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var RopLogon
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): RopLogon {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; response handler'), 'RopLogon'));
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
		// [MS-OXROPS] 		2.2.3.1.1 RopLogon ROP Request Buffer
		// [MS-OXCSTOR]		2.2.1.1.1 RopLogon ROP Request Buffer
		// [MS-OXCSTOR]		2.2.1.1.3 RopLogon ROP Success Response Buffer for Private Mailbox
		// [MS-OXNSPI] 		2.2.1.2 Permitted Error Code Values
		// [MS-OXCDATA] 	2.4 Error Codes
		// [MS-OXCDATA]		2.2.1.1 Folder ID Structure

		if ($mod == mapiHTTP::MKRESP) {

			$p = $resp->savePos();
			$d = new \DateTime();
			$resp->getVar('LogonTime');
			$p1 = $resp->savePos();
			$resp->updVar('Seconds', $d->format('s'), FALSE);
			$resp->restorePos($p1);
			$resp->updVar('Minutes', $d->format('i'), FALSE);
			$resp->restorePos($p1);
			$resp->updVar('Hour', $d->format('G'), FALSE);
			$resp->restorePos($p1);
			$resp->updVar('DayOfWeek', $d->format('N'), FALSE);
			$resp->restorePos($p1);
			$resp->updVar('Day', $d->format('d'), FALSE);
			$resp->restorePos($p1);
			$resp->updVar('Month', $d->format('m'), FALSE);
			$resp->restorePos($p1);
			$resp->updVar('Year', $d->format('Y'), FALSE);
			$resp->restorePos($p);
		}

		return TRUE;
	}

}

?>