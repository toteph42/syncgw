<?php
declare(strict_types=1);

/*
 * 	<Execute> handler class
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

use syncgw\lib\Debug;
use syncgw\lib\XML;
use syncgw\mapi\rops\RopHandler;

class mapiExecute extends mapiWBXML {

	// module version number
	const VER = 1;

	/**
     * 	Singleton instance of object
     * 	@var mapiExecute
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiExecute {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; request/response handler'), 'Execute'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', _('LZ77 compression and decompression'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', _('DIRECT2 Encoding Algorithm'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', '<a href="https://winprotocoldoc.blob.core.windows.net/productionwindowsarchives/MS-XCA/%5bMS-XCA%5d.pdf" target="_blank">[MS-XCA]</a> '.
				      'Xpress Compression Algorithm v9.0');
		$xml->addVar('Stat', _('Implemented'));

	}

	/**
	 * 	Parse <Execute> request / response
	 *
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 * 	@return	- new XML structure or NULL
	 */
	public function Parse(int $mod): ?XML {

		// [MS-OXCMAPIHTTP] 2.2.4.2.1 Execute Request Type Request Body
		// [MS-OXCMAPIHTTP] 2.2.4.2.2 Execute Request Type Success Response Body
		// [MS-OXCMAPIHTTP] 2.2.4.2.3 Execute Request Type Failure Response Body
		// [MS-OXCRPC] 		3.1.4.2 EcDoRpcExt2 Method (Opnum 11)

		// load skeleton
		if (!($xml = parent::_loadSkel('skeleton/Execute', 'Execute', $mod)))
			return NULL;

		$rops = RopHandler::getInstance();

		if ($mod == mapiHTTP::REQ || $mod == mapiHTTP::RESP) {

			// decode firt part
			$xml->setTop();
			parent::Decode($xml, 'Execute');

			// [MS-OXCRPC] 3.1.4.1.1.2 Compression Algorithm
			$xml->xpath('//RPC_HEADER_EXT');
			$xml->getItem();
			$p = $xml->savePos();
			if (strpos($xml->getVar('Flags', FALSE), 'Compressed') !== FALSE) {
				$xml->restorePos($p);

				// save current buffer and position
				$pos  = self::$_pos;
				$head = substr(self::$_wrk, 0, self::$_pos);
				$wrk  = substr(self::$_wrk, self::$_pos, $n = intval($xml->getVar('Size', FALSE)));
				$tail = substr(self::$_wrk, self::$_pos + $n);

				// [MS-OXCRPC] 3.1.4.1.1.2.2 DIRECT2 Encoding Algorithm
				// The basic notion of the DIRECT2 encoding algorithm is that data appears unchanged
				// in the compressed representation
				// [MS-OXCRPC] 3.1.4.1.1.2.1 LZ77 Compression Algorithm
				$wrk = self::_dt2Decode($wrk);
				Debug::Msg($head, 'Heading data ('.strlen($head).')', 0, 1024);
				Debug::Msg($wrk, 'Uncompressed data ('.strlen($wrk).')', 0, 1024);
				Debug::Msg($tail, 'Successive data ('.strlen($tail).')', 0, 1024);

				// restore
				self::$_pos = $pos;
				self::$_wrk = $head.$wrk.$tail;
			}

			// now we can set <RopSize>
			$xml->updVar('RopSize', self::_getInt(2));

			// process Rops
			$xml->getVar('RopsList');
			if (!$rops->Parse($xml, $mod))
				return NULL;

			// process remaining data
			$xml->getVar('Trailer');
			parent::Decode($xml, 'Trailer');
		} else {

			$xml->getVar('RopsList');

			if (!$rops->Parse($xml, $mod))
				return NULL;

			// compute size of produced data
			$xml->setTop();
			$buf = self::Encode($xml, 'RopsList');
			$len = strlen($buf);
			$xml->getVar('RopBuffer');
			$xml->updVar('RopSize', strval($len), FALSE);

			$xml->setTop();
			$buf  = self::Encode($xml, 'ServerObjectHandleTable');
			$len += strlen($buf);
			$xml->getVar('RPC_HEADER_EXT');
			$xml->updVar('Size', strval($len), FALSE);
			$xml->getVar('RPC_HEADER_EXT');
			$xml->updVar('ActualSize', strval($len), FALSE);

			// add to total length size of RPC_HEADER_EXT
			$xml->updVar('RopBufferSize', strval($len + 8));
		}
		$xml->setTop();

		return $xml;
	}

	/**
	 * 	DIRECT2 Encoding Algorithm
	 *
	 * 	@param 	- Encoded input buffer
	 * 	@return - Decoded input buffer
	 */
	private function _dt2Decode(string $in): string {

		// set new input buffer
		$ip  = 0;
		$sip = 0; //3
		$end = strlen($in);
		// test bit mask
		$tst = 0;
		// bit test
		$bit = 0;
		// shared byte
		$shr = 0;

		// output buffer
		$out = '';
		$op  = 0;
		$sop = 0; //3

		// [MS-OXCRPC] 3.1.4.1.1.2.2 DIRECT2 Encoding Algorithm
		// The basic notion of the DIRECT2 encoding algorithm is that data appears unchanged
		// in the compressed representation
		// https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-wusp/2f6ddb6a-9026-43a3-b1d9-d8a19af3f03f

		Debug::Msg($in, 'DIRECT2 encoded buffer ('.$end.') first 1024 bytes', 0, 1024); //3

		while ($ip < $end) {

			// [MS-OXCRPC] 3.1.4.1.1.2.2.1 Bitmask
			if (!$bit) {
				$tst = unpack('V', substr($in, $ip, 4))[1];
				$ip += 4;
				Debug::Msg('Bits ['.sprintf('%032b', $tst).'] In:'.self::$_pos.' Out:0 Len:4'); //3
				// set test mask
				$bit = 32;
			}
			// check whether the bit specified by IndicatorBit is set or not
            // set in Indicator. For example, if IndicatorBit has value 4
            // check whether the 4th bit of the value in Indicator is set
			if ((($tst >> --$bit) & 1) == 0) {
				// data is no metadata
				$out[$op++] = $in[$ip++];
			} else {

				// show regulary swapped data
				if ($sop < $op) { //3
					Debug::Msg('Swap ['.bin2hex(substr($out, $sop)).'] In:'.$sip.' Out:'.$sop.' Len:'.($op - $sop)); //3
					$sip = $ip; //3
					$sop = $op; //3
				} //3

				// [MS-OXCRPC] 3.1.4.1.1.2.2.3 Metadata Offset
				// save start of metadata
				$len = unpack('v', substr($in, $ip, 2))[1];
				$ip += 2;
				$off = intval($len / 8) * -1 - 1;

				// [MS-OXCRPC] 3.1.4.1.1.2.2.4 Match Length
				$len = $len % 8;
				$dbg = ''; //3
				if ($len == 7) { // b'111'
					if (!$shr){
						$shr = unpack('C', substr($in, $ip++, 1))[1];
						$dbg = 'Shared byte ['.sprintf('%08b', $shr).']'; //3
						$len = $shr % 16;
					} else {
						$len = intval($shr / 16);
						$dbg = 'Adding global LEN ['.$len.']'; //3
						$shr = 0;
					}
					if ($len == 15) { // b'1111'
						// additionalbyte
						$len = unpack('C', substr($in, $ip++, 1))[1];
						if ($len == 255) {
							$len = unpack('v', substr($in, $ip, 2))[1];
							// A "full" (all b'1') bit pattern (b'111', b'1111', and b'11111111')
							// means that there is more length in the following 2 bytes
							$ip  += 2;
							$len -= (15 + 7);
							$dbg = 'Next 2 byte [0x'.sprintf('%04X', $len).']'; //3
						}
						else //3
							$dbg = 'Next byte ['.sprintf('%08b', $len).']'; //3
						$len += 15;
					}
					$len += 7;
				}
				// add minimum match is 3 bytes
				$len += 3;

				Debug::Msg('Meta ['.sprintf('%016b', $len).'] Offset:'.$off.' '.$dbg); //3
				$sip  = $ip; //3

				// swap data to output buffer
				while ($len--) {
					$out[$op] = $out[$op + $off];
					$op++;
				}
				if ($sop < $op) { //3
					Debug::Msg('Copy ['.bin2hex(substr($out, $sop)).'] In:'.$sip.' Out:'.$sop.' Len:'.($op - $sop)); //3
					$sop = $op; //3
				} //3
			}
		}

		return $out;
	}

}

?>