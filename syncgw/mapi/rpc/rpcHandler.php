<?php
declare(strict_types=1);

/*
 * 	Wire Format Protocol handler class
 *
 *	@package	sync*gw
 *	@subpackage	Wire Format Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rpc;

use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\lib\Server;
use syncgw\mapi\mapiWBXML;

class rpcHandler extends mapiWBXML {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var rpcHandler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): rpcHandler {

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

		$xml->addVar('Name', _('Wire Format Protocol handler class'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', '<a href="https://learn.microsoft.Object/en-us/openspecs/exchange_server_protocols/ms-oxcrpc" target="_blank">[MS-OXCRPC]</a> '.
				      'Wire Format Protocol v23.1');
		$xml->addVar('Stat', _('Implemented'));

		$srv = Server::getInstance();
		$srv->getSupInfo($xml, $status, 'mapi/rpc', [ 'rpcDefs' ]);
	}

	/**
	 * 	Process RPC request / response
	 *
	 *	@param 	- XML document
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	public function Process(XML &$xml): bool {

 		$op   = $xml->savePos();
		$size = $xml->getVar('AuxiliaryBufferSize');
		$xml->restorePos($op);
		if (!$size)
			return TRUE;

		$sub = new XML();
		if (!$sub->loadFile(Util::mkPath('mapi/rpc/skeleton/rpc.xml')))
		    return FALSE;

		$rpc = new XML();

		// [MS-OXCRPC] 3.1.4.1.1.1.1 rgbAuxIn Input Buffer
		// [MS-OXCRPC] 2.2.2.1 RPC_HEADER_EXT Structure
		// [MS-OXCRPC] 2.2.2.2 AUX_HEADER Structure
		$sub->getVar('RPC_HEADER');
		$xml->append($sub, FALSE);
		if (!self::Decode($xml, 'RPC_HEADER', TRUE))
			return FALSE;

		// RPC_HEADER processed
		$size -= 8;

		while ($size > 0) {

			$pos = parent::$_pos;
			$rpc->loadXML('<syncgw/>');
			$rpc->getVar('syncgw');

			// [MS-OXCRPC] 2.2.2.2 AUX_HEADER Structure
			$sub->getVar('AUX_HEADER');
			$rpc->append($sub, FALSE);
			if (!self::Decode($rpc, 'AUX_HEADER', TRUE))
				return FALSE;

			if ($sub->getVar($tag = $rpc->getVar('AuxType')) !== NULL) {

				if ($tag == 'PERF_SESSIONINFO') {
					if ($rpc->getVar('AuxVersion') == 2)
						$tag .= '2';
					$rpc->updVar('AuxType', $tag);
				}

				$rpc->getVar('syncgw');
				$sub->getVar($tag);
				$rpc->append($sub, FALSE);
				if (!self::Decode($rpc, $tag, TRUE))
					return FALSE;
			} else
				$size = 0;

			$rpc->getChild('syncgw');
			while($rpc->getItem() !== NULL)
				$xml->append($rpc, FALSE, FALSE);

			$size -= parent::$_pos - $pos;
		}

		return TRUE;
	}

}

?>