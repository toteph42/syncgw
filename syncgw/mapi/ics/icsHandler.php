<?php
declare(strict_types=1);

/*
 * 	Bulk Data Transfer Protocol handler class
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\ics;

use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Debug; //3
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\lib\Server;
use syncgw\mapi\mapiHTTP;
use syncgw\mapi\mapiWBXML;
use syncgw\document\field\fldAttribute;

class icsHandler extends mapiWBXML {

	// module version number
	const VER = 1;

	// folder assignments
	const GUIDS = [
		DataStore::CALENDAR			=> 'Calendar',
		DataStore::CONTACT			=> 'Contact',
		//							=> 'FixContact',
		//							=> 'IMContact',
		//							=> 'GAL',
		DataStore::NOTE				=> 'Notes',
		DataStore::TASK				=> 'Task',
		// DataStore::SMS			=> 'SMS',
		// DataStore::DOCLIB		=> 'DocLib',
		// 							=> 'Journal',
		// 							=> 'Config',
		DataStore::MAIL 			=> [
			fldAttribute::MBOX_SENT 	=> 'Sent',
			fldAttribute::MBOX_TRASH 	=> 'Deleted',
			fldAttribute::MBOX_OUT 	=> 'Outbox',
			fldAttribute::MBOX_IN 	=> 'Inbox',
			fldAttribute::MBOX_DRAFT 	=> 'Drafts',
			fldAttribute::MBOX_SPAM	=> 'Spam',
		],
	];
	const EXTRA = [
		#'Config',
		#'Conflicts',
		#'LocalError',
		#'ServerError',
		'SyncProblems',	// must be last!
	];

    /**
     * 	Singleton instance of object
     * 	@var icsHandler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): icsHandler {

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

		$xml->addVar('Name', _('Bulk Data Transfer Protocol handler class'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', '<a href="https://learn.microsoft.Object/en-us/openspecs/exchange_server_protocols/ms-oxcfxics" target="_blank">[MS-OXCFXICS]</a> '.
				     'Bulk Data Transfer Protocol v24.0');
		$xml->addVar('Stat', _('Implemented'));
		$xml->addVar('Opt', '<a href="https://learn.microsoft.Object/en-us/openspecs/exchange_server_protocols/ms-oxosfld" target="_blank">[MS-OXOSFLD]</a> '.
				     'Special Folders Protocol v14.0');
		$xml->addVar('Stat', _('Implemented'));

		$srv = Server::getInstance();
		$srv->getSupInfo($xml, $status, 'mapi\\ics', [ 'icsDefs', 'icsFlags' ]);
	}

	/**
	 * 	Parse Rop request / response
	 *
	 *	@param 	- XML request document
	 *	@param 	- XML response document
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 *	@return - TRUE = Ok; FALSE = Error
	 */
	public function Parse(XML &$req, XML &$resp, int $mod): bool {

		// [MS-AXCXICS] 2.2.4 FastTransfer Stream
		// [MS-AXCXICS] 2.2.4.1 Lexical structure

		if ($mod == mapiHTTP::REQ)
			return TRUE;

		// [MS-AXCXICS] 4.3.2 Downloading a Partial Item

		if ($mod == mapiHTTP::MKRESP) {

			// load skeleton
			$sub = new XML();
			if (!$sub->loadFile(Util::mkPath('mapi/ics/skeleton/Folder.xml')))
			    return FALSE;
			$sub->getVar('Folder');

			$db = DB::getInstance();

			// where to insert data
			$resp->getVar('TransferBuffer');
			$rp = $resp->savePos();

			foreach (Util::HID(Util::HID_CNAME, DataStore::DATASTORES) as $hid => $class) {

				// synchronize all groups
				$ds = $class::getInstance();
				$ds->syncDS();


				// scan through all groups
			    foreach ($db->Query($hid, DataStore::GRPS) as $gid => $typ) {

			    	// read document
					if (!($xml = $db->Query($hid, DataStore::RGID, $gid)) ||
						 $xml->getVar('SyncStat') == DataStore::STAT_DEL)
						continue;

					if ($hid & DataStore::MAIL) {
						$attr = $xml->getVar('Attributes');
						$name = '';
						foreach (self::GUIDS[$hid] as $bit => $n) {
							if ($attr & $bit) {
								$name = $n;
								break;
							}
				    	}
				    	// we don't know type
				    	if (!$name)
				    		continue;
					} else
						$name = self::GUIDS[$hid];

					// swap skeleton
					$resp->restorePos($rp);
			    	$sub->updVar('FolderType', $name);
			    	$sub->getVar('Folder');
					$resp->append($sub, FALSE);

					$resp->xpath('//Value[text()="##name"]', FALSE);
					$resp->getItem();
					$resp->setVal($val = $xml->getVar('GroupName'));

					$resp->xpath('//Count[text()="##name_len"]', FALSE);
					$resp->getItem();
					$resp->setVal(strval((mb_strlen($val) + 1) * 2));

					$resp->xpath('//Value[text()="##created"]', FALSE);
					$resp->getItem();
					$resp->setVal(gmdate('Y-m-d H:i:s', intval($xml->getVar('Created'))));

					$resp->xpath('//Value[text()="##modified"]', FALSE);
					$resp->getItem();
					$resp->setVal(gmdate('Y-m-d H:i:s', $val = intval($xml->getVar('LastMod'))));

					$resp->xpath('//Value[text()="##lastsync"]', FALSE);
					$resp->getItem();
					$resp->setVal(gmdate('Y-m-d H:i:s', $val));

					$resp->xpath('//GlobalCounter[text()="##folder_id"]', FALSE);
					$resp->getItem();
					$resp->setVal($name);
				}
			}

			// add special folders
			foreach (self::EXTRA as $name) {

				$tme = time();

				// swap skeleton
				$resp->restorePos($rp);
		    	$sub->updVar('FolderType', $name);
		    	$sub->getVar('Folder');
				$resp->append($sub, FALSE);

				$resp->xpath('//Value[text()="##name"]', FALSE);
				$resp->getItem();
				$resp->setVal($name);

				$resp->xpath('//Count[text()="##name_len"]', FALSE);
				$resp->getItem();
				$resp->setVal(strval((mb_strlen($name) + 1) * 2));

				$resp->xpath('//Value[text()="##created"]', FALSE);
				$resp->getItem();
				$resp->setVal(gmdate('Y-m-d H:i:s', $tme));

				$resp->xpath('//Value[text()="##modified"]', FALSE);
				$resp->getItem();
				$resp->setVal(gmdate('Y-m-d H:i:s', $tme));

				$resp->xpath('//Value[text()="##lastsync"]', FALSE);
				$resp->getItem();
				$resp->setVal(gmdate('Y-m-d H:i:s', $tme));

				$resp->xpath('//GlobalCounter[text()="##folder_id"]', FALSE);
				while ($resp->getItem() !== NULL)
					$resp->setVal($name);
			}

			$resp->setTop();
			$buf = self::Encode($resp, 'TransferBuffer');
			$len = strlen($buf);
			$resp->updVar('TransferBufferSize', strval($len));
			$resp->getVar('TransferBuffer');
			$resp->setAttr([ 'T' => 'H'.$len ]);

		    return TRUE;
		}

		// swap position
		$wrk		= self::$_wrk;
		$pos		= self::$_pos;
		$end 		= $resp->getVar('TransferBufferSize');
		$base		= $pos - $end;
		self::$_wrk = hex2bin($resp->getVar('TransferBuffer'));
		self::$_pos = 0;
		$sm			= array_flip(icsDefs::STREAM_MARKER);

		// replace tag
		$a			= $resp->getattr();
		$resp->delVar(NULL);
		$resp->addVar('TransferBuffer', NULL, FALSE, $a);

		while (self::$_pos < $end) {

			$op = $resp->savePos();
			$resp->addVar('Element');

			// check marker
			$dp = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
			if (isset($sm[$val = parent::_getInt(4)])) {
				$p  = [ 'T' => 'I', 'S' => '4', 'D' => 'STREAM_MARKER' ];
				$p += [ 'P' => $dp ]; //3
				$resp->addVar('Marker', $cmd = $sm[$val], FALSE, $p);
				$resp->restorePos($op);
				continue;
			}

			// must be something different - reset position
			self::$_pos -= 4;

			$dp  = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
			$typ = parent::_convConst(1, 'DATA_TYP', parent::_getInt(2));
			$p   = [ 'T' => 'I', 'S' => '2', 'D' => 'DATA_TYP' ];
			$p  += [ 'P' => $dp ]; //3
			$resp->addVar('Type', $typ, FALSE, $p);

			$dp  = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
			$val = parent::_convConst(1, 'PID', parent::_getInt(2));
			$p   = [ 'T' => 'I', 'S' => '2', 'D' => 'PID' ];
			$p  += [ 'P' => $dp ]; //3
			$resp->addVar('PropertyId', $val, FALSE, $p);

			$val = NULL;

			$dp = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
			switch (substr($typ, 0, 1)) {
			// Boolean (1 byte)
			case 'B':
				$p  = [ 'T' => 'B' ];
				$p += [ 'P' => $dp ]; //3
				$resp->addVar('Pad', '0', FALSE, $p);
				self::$_pos++;
				$dp  = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
				$val = !self::_getInt(1) ? 'False' : 'True';
				break;

			// Integer
			case 'I':
				$val = self::_getInt(intval(substr($typ, 1)));
				if ($cmd != 'IncrSyncStateBegin')
					break;
				$typ = 'H';
				self::$_pos -= 4;

			// UTF-16LE
			case 'S':
			// ASCII
			case 'A':
			// Hex. string
			case 'H':
				$p  = [ 'T' => 'I', 'S' => '4' ];
				$p += [ 'P' => $dp ]; //3
				// special checck for boolean data type
				$resp->addVar('Count', $len = self::_getInt(4), FALSE, $p);
				$dp = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
				$val = self::_getData($resp, $typ, intval($len))[0];
				$p  = [ 'T' => $typ ];
				if ($typ == 'H')
					$p += [ 'S' => $len ];
				break;

			// Time
			case 'T':
				$val = self::_getData($req, $typ)[0];
				break;

			// Mutiple
			case 'M':
				$p  = [ 'T' => $typ ];
				$p += [ 'P' => $dp ]; //3
				// special checck for boolean data type
				$resp->addVar('Count', $n = self::_getInt(4), FALSE, $p);
				$resp->addVar('ValueList');
				while ($n--) {
					$dp = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
					$vp = $resp->savePos();
					$resp->addVar('Value');
					$p  = [ 'T' => 'I', 'S' => '4' ];
					$p += [ 'P' => $dp ]; //3
					$resp->addVar('Count', $len = self::_getInt(4), FALSE, $p);
					$dp = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
					$val = self::_getData($resp, $typ, intval($len))[0];
					$p  = [ 'T' => substr($typ, 2) ];
					$p += [ 'P' => $dp ]; //3
					if ($typ == 'M_H')
						$p += [ 'S' => $len ];
					$resp->addVar('Data', $val, FALSE, $p);
					$dp = sprintf('0x%X %d', $base + self::$_pos, $base + self::$_pos); //3
					$resp->restorePos($vp);
				}
				break;

			default:
				Debug::Warn('Unsupported property type mapiDefs::DATA_TYP [ '.$typ.' ]'); //3
				$val = '##undef';
				if ($this->_err++ > mapiWBXML::MAX_ERR) { //3
					if (Debug::$Conf['Script']) //3
						exit; //3
					self::$_pos = $end; //3
				} //3
				break;
			}

			if (substr($typ, 0, 1) != 'M') {
				$p += [ 'P' => $dp ]; //3
				$resp->addVar('Value', $val, FALSE, $p);
			}

			$resp->restorePos($op);
		}

		// restore buffer
		self::$_wrk = $wrk;
		self::$_pos = $pos;

		return TRUE;
	}

}

?>