<?php
declare(strict_types=1);

/*
 * 	Notes data store handler class
 *
 *	@package	sync*gw
 *	@subpackage	myApp handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\myapp;

use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\XML;
use syncgw\document\field\fldSummary;
use syncgw\document\field\fldBody;
use syncgw\document\field\fldCategories;

class Notes extends XML {

 	const MAP = [
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
 		'title'						=> fldSummary::TAG,
	    'text'						=> fldBody::TAG,
	    'cats'						=> fldCategories::TAG,
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
 	];

 	/**
	 * 	Record mapping table
	 * 	@var array
	 */
	private $_ids = [];

	/**
	 * 	Perform query on external data base
	 *
	 * 	@param	- Handler ID
	 * 	@param	- Query command:<fieldset>
	 * 			  DataStore::ADD 	  Add record                             $parm= XML object<br>
	 * 			  DataStore::UPD 	  Update record                          $parm= XML object<br>
	 * 			  DataStore::DEL	  Delete record or group (inc. sub-recs) $parm= GUID<br>
	 * 			  DataStore::RGID     Read single record       	             $parm= GUID<br>
	 * 			  DataStore::GRPS     Read all group records                 $parm= None<br>
	 * 			  DataStore::RIDS     Read all records in group              $parm= Group ID or '' for record in base group
	 * 	@return	- According  to input parameter<fieldset>
	 * 			  DataStore::ADD 	  New record ID or FALSE on error<br>
	 * 			  DataStore::UPD 	  TRUE=Ok; FALSE=Error<br>
	 * 			  DataStore::DEL	  TRUE=Ok; FALSE=Error<br>
	 * 			  DataStore::RGID	  XML object; FALSE=Error<br>
	 * 			  DataStore::GRPS	  [ "GUID" => Typ of record ]<br>
	 * 			  DataStore::RIDS     [ "GUID" => Typ of record ]
	 */
	public function Query(\mysqli &$db, int $uid, int $cmd, $parm = '') {

		// set default return value
		$out = TRUE;

		// fill array with all available primary keys from external data store
		if (!$this->_ids) {
			$obj = $db->query('SELECT `id` FROM `myapp_notestable` WHERE `user` = "'.$uid.'"');
			while($row = $obj->fetch_row())
				$this->_ids[DataStore::TYP_DATA.$row[0]] = DataStore::TYP_DATA;
		}

		switch ($cmd) {
		case DataStore::ADD:
		case DataStore::UPD:

            // adding / updates on groups not supported
			if ($parm->getVar('Type') != DataStore::TYP_DATA) {
				$out = FALSE;
		        break;
			}

		    if ($cmd & DataStore::UPD && ($out = $parm->getVar('extID')))
				$qry = 'UPDATE';
			else {
				$cmd = DataStore::ADD;
				$qry = 'INSERT';
			}

		    // build query
			$qry .= ' `myapp_notestable` SET `user` = '.$uid.', ';
			$parm->getChild('Data');
			foreach (self::MAP as $rid => $tag) {

				if (!$parm->xpath('//Data/'.$tag))
					continue;

					$val = '';
				while (($v = $parm->getItem()) !== NULL) {
					if ($rid == 'cats')
						$val .= ($val ? ',' : '').$v;
					else
						$val = $v;
				}

				$qry .= '`'.$rid.'` = "'.$db->real_escape_string(utf8_decode($val)).'", ';
			}

			// strip off trailer
   			$qry = substr($qry, 0, -2);
			// add reference to record
			if ($cmd & DataStore::UPD) {
				$parm->getVar('Data');
				$qry .= ' WHERE `id` = '.$db->real_escape_string(strval(substr($out = $parm->getVar('extID'), 1)));
				// we don't take care about groups
			}

			// execute query
			if (!$db->query($qry))
				$out = '';

			// get record ID
			elseif ($cmd & DataStore::ADD) {
				$out = DataStore::TYP_DATA.strval($db->insert_id);
				$parm->updVar('extID', $out);
				$this->_ids[$out] = DataStore::TYP_DATA;
			}
			break;

		case DataStore::DEL:

			// is record avaialable?
			if (!isset($this->_ids[$parm])) {
				$out = FALSE;
				break;
			}

			// delete record
			$db->query('DELETE FROM `myapp_notestable` WHERE `id` = "'.
						$db->real_escape_string(substr($parm, 1)).'" AND `user` = "'.$uid.'"');
			unset($this->_ids[$parm]);
			break;

		case DataStore::GRPS:

			// we do not support any groups
			$out = [];
			break;

		case DataStore::RGID:

			// load record from external data store
			$obj = $db->query('SELECT * FROM `myapp_notestable` WHERE `id` = "'.
							   $db->real_escape_string(strval(substr($parm, 1))).
							   '" AND `user` = "'.$uid.'"');
			if (!($r = $obj->fetch_assoc())) {
			    $out = FALSE;
			    break;
			}

			// create XML object
			$idb = DB::getInstance();
			$out = $idb->mkDoc(DataStore::NOTE, [
						'GID' 		=> '',
						'extID' 	=> $parm,
						'extGroup'	=> '',
			]);
			foreach (self::MAP as $rid => $tag) {

				if (!isset($r[$rid]) || !($val = utf8_encode($r[$rid])))
			    	continue;

			    if ($rid != 'cats')
		    		$out->addVar($tag, $val, FALSE, $rid == 'text' ? [ 'X-TYP' => fldBody::TYP_TXT ] : []);
	    		else {
		    		foreach (explode(',', $val) as $cat)
			    		$out->addVar($tag, trim($cat));
	    		}
			}

			// set pointer back to <Data>
			$out->getVar('Data');
            break;

		case DataStore::RIDS:

			$out = $this->_ids;
			break;

		default:
			break;
		}

		return $out;
	}

	/**
	 * 	Get list of supported fields in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- [ field name ]
	 */
	public function getflds(int $hid): array {

		$rc = [];
		foreach (self::MAP as $k => $v)
			$rc[] = $v;
		$k; // disable Eclipse warning

		return $rc;
	}

	/**
	 * 	Reload any cached record information in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- TRUE=Ok; FALSE=Error
	 */
	public function Refresh(int $hid): bool {

		$this->_ids = NULL;

		return TRUE;
	}

	/**
	 * 	Check trace record references
	 *
	 *	@param 	- Handler ID
	 * 	@param 	- External record array [ GUID ]
	 * 	@param 	- Mapping table [HID => [ GUID => NewGUID ] ]
	 */
	public function chkTrcReferences(int $hid, array $rids, array $maps): void {
	}

}

?>