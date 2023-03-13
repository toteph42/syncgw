<?php
declare(strict_types=1);

/*
 * 	Document handler class
 *
 *	@package	sync*gw
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\Debug; //3
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\lib\HTTP;
use syncgw\lib\Lock;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;

class docHandler extends XML {

	// module version number
	const VER 	= 15;

	/**
	 * 	Handler ID
	 * 	@var docHandler
	 */
	protected $_hid;

	/**
	 * 	Parent handler version
	 * 	@var string
	 */
	protected $_docVer;

	/**
	 * 	Supported MIME classes
	 * 	@var array
	 */
	protected $_mimeClass;

	/**
	 *  Construct class
	 */
	protected function _init() {

		// set messages 13001-13100
		$log = Log::getInstance();
		$log->setMsg([
				13001 => _('Unsupported MIME type \'%s\' version \'%s\''),
		        13002 => _('Error exporting record in MIME format \'%s\' version \'%s\''),
		]);
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', sprintf(_('%s document handler'), Util::HID(Util::HID_ENAME, $this->_hid, TRUE)));
		$xml->addVar('Ver', strval($this->_docVer));

		if (!$status) {
			$xml->addVar('Opt', _('Document base handler'));
			$xml->addVar('Ver', strval(self::VER));

			$xml->addVar('Opt', _('Internal data store name'));
			$xml->addVar('Stat', '"'.Util::HID(Util::HID_ENAME, $this->_hid, TRUE).'"');
		} else {
			$xml->addVar('Opt', _('Status'));
			$cnf = Config::getInstance();
			$xml->addVar('Stat', ($cnf->getVar(Config::ENABLED, TRUE) & $this->_hid) ? _('Enabled') : _('Disabled'));
		}

		foreach($this->_mimeClass as $mime)
			$mime->getInfo($xml, $status);
	}

	/**
	 * 	Import external document into internal and external data store
	 *
	 * 	@param 	- External record
	 * 	@param	- Query modus (DataStore::ADD, DataStore::UPD)
	 * 	@param 	- Group name
	 * 	@param	- LUID
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function import(XML &$ext, int $qry, string $grp = '', string $luid = ''): bool {

		$db = DB::getInstance();

		$xqry = $qry;
		if ($qry & DataStore::ADD) {
			// create new skeleton
			$doc = $db->mkDoc($this->_hid, [ 'Group' => $grp, 'LUID' => $luid], TRUE );
			$qry = DataStore::UPD;
			parent::loadXML($doc->saveXML());
		}

		// be sure to set proper location in document
		parent::getVar('Data');

		// figure out MIME type
		$class = 'syncgw\\document\\mime\\mim';
		if ($ext->getVar('ApplicationData', FALSE) !== NULL) {
			$class .= 'As'.Util::HID(Util::HID_TAB, $this->_hid, TRUE);
			$mime = $class::MIME[0];
		} else {
			$data = $ext->getVar('Data');
			if (strpos($data, 'VNOTE')) {
				$class .= 'v'.Util::HID(Util::HID_TAB, $this->_hid, TRUE);
				$mime  = $class::MIME[0];
			} elseif (strpos($data, 'VCALENDAR') || strpos($data, 'VCARD')) {
				if (strpos($data, 'VTODO')) {
					$class .= 'vTask';
				} else
					$class .= strpos($data, 'VCALENDAR') ? 'vCal' : 'vCard';
				$v = substr($data, strpos($data, 'VERSION:') + 8);
				$v = trim(substr($v, 0, strpos($v, "\n")));
				foreach ($class::MIME as $mime)
					if ($mime[1] == $v)
						break;
			} else {
				$class .= 'Plain';
				$mime  = $class::MIME[0];
			}
		}

		$class = $class::getInstance();
		if (!$class->import($mime[0], floatval($mime[1]), $ext, $this)) {
			Debug::Warn('No MIME type found'); //3
			return FALSE;
		}
		else //3
			Debug::Msg('--- Document imported with "'.$mime[0].'" "'.sprintf('%1.1f', $mime[1]).'"'); //3

		// set default group
		if ($grp && !parent::getVar('Group'))
			parent::updVar('Group', $grp);

		// update group?
		if ($qry & DataStore::ADD) {
		    if ($g = $db->Query($this->_hid, DataStore::RGID, $grp))
		    	parent::updVar('extGroup', $g->getVar('extID'));
		} else
			parent::updVar('SyncStat', DataStore::STAT_OK);

		// save/update document in external data store
		if ($qry) {

			if (is_string($xid = $db->Query(DataStore::EXT|$this->_hid, $xqry, $this)))
				$this->updVar('extID', $xid);

			if ($xid === FALSE || $db->Query($this->_hid, $qry, $this) === FALSE) {
			    $this->getVar('syncgw'); //3
    			Debug::Warn($this, 'Never should go here - "'.$qry.'" failed!'); //3
	   		    return FALSE;
		    }
		}

		return TRUE;
	}

	/**
	 * 	Export document to client supported MIME type
	 *
	 * 	@param	- External document
	 * 	@param	- Internal document
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function export(XML &$ext, XML &$int): bool {

		$dev = Device::getInstance();
		if (!$dev->xpath('//DataStore/HandlerID[text()="DataStore::'.strtoupper(Util::HID(Util::HID_TAB, $this->_hid, TRUE)).'"]/../MIME'))
			return FALSE;

		$dev->getItem();
		$p = $dev->savePos();
		$mt = $dev->getVar('Name', FALSE);
		$dev->restorePos($p);
		$mv = floatval($dev->getVar('Version', FALSE));
		$dev->restorePos($p);

		// find MIME handler
		$ok = FALSE;
		foreach ($this->_mimeClass as $class) {
			foreach ($class::MIME as $mime)
				if (!strcasecmp($mime[0], $mt) && $mime[1] == $mv) {
					$ok = TRUE;
					break;
				}
			if ($ok)
				break;
		}

		// mime handler found?
		if (!$ok) {
			$log = Log::getInstance();
			$log->Msg(Log::ERR, 13001, $mt, $mv);
			return FALSE;
		}

		if (!$class->export($mt, floatval($mv), $int, $ext)) {
			// we should never go here!
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 13002, $mt, $mv);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * 	Delete internal (and external) document
	 *
	 * 	@param	- Record ID (GUID or LUID)
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function delete(string $id): bool {

		$db = DB::getInstance();

		// get internal record
		if (!($doc = $db->Query($this->_hid, DataStore::RGID, $id)))
			if (!($doc = $db->Query($this->_hid, DataStore::RLID, $id)))
				return FALSE;

		// first delete external document
		if ($xid = $doc->getVar('extID'))
			$db->Query(DataStore::EXT|$this->_hid, DataStore::DEL, $xid);

		// then delete internal document - needs to be separated because external datastore may be disabled
		return $db->Query($this->_hid, DataStore::DEL, $id);
	}

	/**
	 * 	Synchronize internal with external data store
	 *
	 * 	@param 	- Internal group ID to synchronize
	 * 	@param 	- FALSE = Groups only; TRUE = All records
	 * 	@param	- Runtime check function
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function syncDS(?string $grp = '', bool $all = FALSE, $check = NULL): bool {

		if (Debug::$Conf['Script'] == 'DB' || Debug::$Conf['Script'] == 'DBExt' || Debug::$Conf['Script'] == 'MIME04') { //3
			Debug::Warn('----------------- Caution: syncDS() for "'.Util::HID(Util::HID_ENAME, $this->_hid, TRUE).'" skipped'); //3
			return FALSE; //3
		} //3

		$db   = DB::getInstance();
        $cnf  = Config::getInstance();
		$http = HTTP::getInstance();
        $lck  = Lock::getInstance();

        // we try to lock to disable parallel execution
		if (!$lck->lock($lock = $http->getHTTPVar('REMOTE_ADDR').'-'.$http->getHTTPVar('User')))
			return FALSE;

        // group mapping table
		$gmap = [];

		// load records to process
		if ($grp) {
			if (!($xml = $db->Query($this->_hid, DataStore::RGID, $grp))) {
				$lck->unlock($lock);
				return FALSE;
			}
			$xgrp = $xml->getVar('extID');
		} else
			$xgrp = $grp;

		if ($all)
			$xids = $db->getRIDS(DataStore::EXT|$this->_hid, $xgrp);
		else
			$xids = $db->Query(DataStore::EXT|$this->_hid, DataStore::GRPS, $xgrp);
		if (!count($xids)) //3
			Debug::Msg('No external '.($all ? 'records' : 'groups'). //3
					   ' found in data store "'.Util::HID(Util::HID_ENAME, $this->_hid, TRUE). //3
					   '"'.($xgrp ? 'in group ['.$xgrp.']' : '')); //3
		else //3
    		Debug::Msg($xids, 'List of external '.($all ? 'records' : 'groups'). //3
    				   ' in data store "'.Util::HID(Util::HID_ENAME, $this->_hid, TRUE). //3
    				   '"'.($xgrp ? ' in group ['.$xgrp.']' : '')); //3

		// get list of internal records
		if ($all)
			$iids = $db->getRIDS($this->_hid, $grp);
		else
			$iids = $db->Query($this->_hid, DataStore::GRPS, $grp);
	    if ($iids === FALSE)
	    	$iids = [];

	    if (!count($iids)) //3
			Debug::Msg('No internal '.($all ? 'records' : 'groups'). //3
					   ' found in data store "'.Util::HID(Util::HID_ENAME, $this->_hid, TRUE). //3
					   '"'.($grp ? ' in group ['.$grp.']' : '')); //3
        else //3
	   		Debug::Msg($iids, 'List of internal '.($all ? 'records' : 'groups'). //3
	   				   ' in data store "'.Util::HID(Util::HID_ENAME, $this->_hid, TRUE). //3
	   				   '"'.($grp ? ' in group ['.$grp.']' : '')); //3

    	// external data store available?
        $int = ($int = $cnf->getVar(Config::DATABASE)) == 'mysql' || $int == 'file';

        // get list of supported fields
        $flds = $db->getflds($this->_hid);

        // checking internal data store records
		foreach ($iids as $id => $typ) {

			// read record
		    if (!($idoc = $db->Query($this->_hid, DataStore::RGID, $id))) {
		        Debug::Err('Error reading record ['.$id.'] in data store "'. //3
				           Util::HID(Util::HID_ENAME, $this->_hid, TRUE).'"'); //3
			    continue;
		    }

			// already soft-deleted?
			if ($idoc->getVar('SyncStat') == DataStore::STAT_DEL)
			    continue;

			// external data store available
			if (!$int) {

    			// record ever synchonized?
	       		if (!($xid = $idoc->getVar('extID'))) {
			        Debug::Msg('Record ['.$id.'] in data store "'. //3
					        	Util::HID(Util::HID_ENAME, $this->_hid, TRUE). //3
			        		    '" has no external record reference - deleting record'); //3
    				$db->Query($this->_hid, DataStore::DEL, $id);
    			    continue;
		      	}

		      	// external record available?
    			if (!isset($xids[$xid])) {

    				Debug::Msg('Internal record ['.$id.'] set to DataStore::STAT_DEL '. //3
      						   '- external record ['.$xid.'] does not exist'); //3

	       			// delete external reference
			     	$idoc->updVar('extID', '');
    				// rewrite record
	       			$db->setSyncStat($this->_hid, $idoc, DataStore::STAT_DEL, FALSE, TRUE);
	       			continue;
       			}
			}

			// start compare records

			// load external record
			if (!isset($xid) || !($xdoc = $db->Query(DataStore::EXT|$this->_hid, DataStore::RGID, $xid)))
			    continue;

			// save mapping
			if ($typ != DataStore::TYP_DATA)
				$gmap[$xid] = $id;

			// swap unsupported tags from internal to external record
			$idoc->getVar('Data');
			$idoc->getChild(NULL, FALSE);
		    while (($v = $idoc->getItem()) !== NULL) {
		        $n = $idoc->getName();
		        $a = $idoc->getAttr();
	      		if (!in_array($n, $flds)) {
	 	            $xdoc->getVar('Data');
	 	            if (!$xdoc->getVar($n, FALSE))
			            $xdoc->addVar($n, $v, FALSE, $a);
		        }
		    }

			// get external record
			$xdoc->getVar('Data');
			$xr = preg_replace('|(<[a-zA-Z]+)[^>]*?>|', '$1/>', $xdoc->saveXML(FALSE, FALSE));
			$xr = str_replace('><', ">\n<", $xr);
			$xr = explode("\n", $xr);

			// remove "<Data>"
			array_shift($xr);
			array_pop($xr);

			// serialize
			sort($xr);

			// get internal record
			$idoc->getVar('Data');
			$ir = preg_replace('|(<[a-zA-Z]+)[^>]*?>|', '$1/>', $idoc->saveXML(FALSE, FALSE));
			$ir = str_replace('><', ">\n<", $ir);
			$ir = explode("\n", $ir);

			// remove "<Data>"
			array_shift($ir);
			array_pop($ir);

			// serialize
			sort($ir);

			// check differences
            list($c, $str) = Util::diffArray($ir, $xr);
            $str; // disable Eclipse warning

			// any differences?
            if ($c) {

				Debug::Warn($str, 'Records not equal - replacing internal record ['.$id. //3
							'] with external record ['.$xid.'] ('.($c / 2).' changes)'); //3
                Debug::Msg($xdoc, 'External record'); //3
                Debug::Msg($idoc, 'Internal record'); //3

                // replace document <Data>
				$idoc->delVar(NULL, FALSE);
				$idoc->append($xdoc, FALSE);

				// rewrite record
				$db->setSyncStat($this->_hid, $idoc, DataStore::STAT_REP, FALSE, TRUE);
			}

			// external record is processed
			unset($xids[$xid]);
		}

		// add remaining unknown external records
		foreach ([ DataStore::TYP_GROUP, DataStore::TYP_DATA ] as $chk) {

			foreach ($xids as $xid => $typ) {

				// type to check
				if ($typ != $chk)
					continue;

				// load external record
				if (!($xdoc = $db->Query(DataStore::EXT|$this->_hid, DataStore::RGID, $xid))) {
					$lck->unlock($lock);
					return FALSE;
				}

                // create new document. This must be done in this way to get a proper GUID
				$idoc = $db->mkDoc($this->_hid, [ 'Status' => DataStore::STAT_ADD ], TRUE);

				// replace document <Data>
				$idoc->getVar('Data');
				$idoc->delVar(NULL, FALSE);
				$xdoc->getVar('Data');
				$idoc->append($xdoc, FALSE);

				// swap type
				$idoc->updVar('Type', $typ);

				// swap external record id
				$idoc->updVar('extID', $xdoc->getVar('extID'));

				// swap external group
				$xgid = $xdoc->getVar('extGroup');
				$idoc->updVar('extGroup', $xgid);

				// set group
				$idoc->updVar('Group', isset($gmap[$xgid]) ? $gmap[$xgid] : strval($grp));

				// save mapping
				if ($typ != DataStore::TYP_DATA)
					$gmap[$xid] = $idoc->getVar('GUID');

				// count record
				$id = $idoc->getVar('GUID'); //3
				$idoc->getVar('syncgw'); //3
				Debug::Msg($idoc, 'Creating new internal record ['.$id.'] from unknown external record ['. //3
						   $xid.'] in group ['.$grp.']'); //3

				// add record
				$db->Query($this->_hid, DataStore::UPD, $idoc);
			}
		}

		// map other groups
		if (count($gmap)) //3
			Debug::Msg($gmap, 'Group mapping table'); //3

		$lck->unlock($lock);

		return TRUE;
	}

}

?>