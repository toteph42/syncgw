<?php
declare(strict_types=1);

/*
 * 	<GetItemEstimate> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\document\field\fldConversationId;
use syncgw\document\field\fldStartTime;
use syncgw\document\field\fldStatus;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masGetItemEstimate {

	// module version number
	const VER   		 = 12;

	// status codes
	const CID			 = '2';
	const PRIME 		 = '3';
	const KEY			 = '4';
	// status description
	const STAT  		 = [ //2
		self::CID		 => 'A collection was invalid or one of the specified collection IDs was invalid', //2
		self::PRIME		 => 'The synchronization state has not been primed', //2
		self::KEY		 => 'The specified synchronization key was invalid', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masGetItemEstimate
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masGetItemEstimate {

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

		$xml->addVar('Opt', '<a href="https://learn.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-ascmd" target="_blank">[MS-ASCMD]</a> '.
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'GetItemEstimate'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse XML node
	 *
	 *	The GetItemEstimate command gets an estimate of the number of items in a collection or folder on
	 *	the server that have to be synchronized.
	 *
	 * 	@param	- Input document
	 * 	@param	- Output document
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function Parse(XML &$in, XML &$out): bool {

		Debug::Msg($in, '<GetItemEstimate> input'); //3

		// gets an estimate of the number of items in a collection or folder on the server that have to be synchronized
		$out->addVar('GetItemEstimate', NULL, FALSE, $out->setCP(XML::AS_ESTIMATE));
		$out->addVar('Status', masStatus::OK);

		// <Collections> - not used

		// contains elements that describe estimated changes
		$out->addVar('Response');
		$op = $out->savePos();
		$out->addVar('Status', masStatus::OK);

		$usr = User::getInstance();
		$mas = masHandler::getInstance();
		$db  = DB::getInstance();

		// contains elements that apply to a particular collection
		$in->xpath('//Collection/.', FALSE);
		while ($in->getItem() !== NULL) {

			$ip = $in->savePos();

			// contains elements that apply to a particular collection
			$out->addVar('Collection');

			// specifies the server ID of the collection from which the item estimate is being obtained this is the group...
			$gid = $in->getVar('CollectionId', FALSE);

			// @opt <CollectionId> value "RI" (recipient information cache)
			if ($gid == 'RI') //3
				Debug::Err('<CollectionId> "RI" not supported'); //3

			// <Options> control certain aspects of how the synchronization is performed
			$in->restorePos($ip);
			$mas->loadOptions('GetItemEstimate', $in);

			// compile handler ID from $gid
			if (!($hid = array_search(substr($gid, 0, 1), Util::HID(Util::HID_PREF, DataStore::ALL, TRUE)))) {
				Debug::Err('Cannot compile class name from "'.$gid.'"'); //3
				$opts = $mas->getOption($gid);
			} else
				$opts = $mas->getOption(strval($hid));

			// <SyncKey> will not be updated!
			$in->restorePos($ip);
			if ($usr->syncKey($gid) != $in->getVar('SyncKey', FALSE)) {
				$out->restorePos($op);
				$out->updVar('Status', self::KEY, FALSE);
				break;
			}

			$out->addVar('CollectionId', $gid);

			// specifies whether to include items that are included within the conversation modality
			// 0/1 whether to include conversations
			$in->restorePos($ip);
			if ($cmod = $in->getVar('ConversationMode', FALSE)) {

				// only allowed for MailBoxes
				if (!($hid & DataStore::MAIL) && !is_numeric($cmod)) {

					if ($mas->callParm('BinVer') < 14) {
						$mas->setHTTP(400);
						return FALSE;
					}
					$out->restorePos($op);
					$out->updVar('Status', masStatus::XML, FALSE);
					break;
				}
			}

			// max. # of items to return
			$max = $opts['MaxItems'];

			// estimated number of records
			$cnt = 0;

			// no conversation id identified
			$cid = '';

			if ($hid & DataStore::TASK) //3
				$df = $opts['FilterType'] == -1 ? 'Incomplete task' : 'All task'; //3
			else $df = gmdate('D Y-m-d G:i:s', time() - $opts['FilterType']); //3
				Debug::Msg('Read modified records in group "'.$gid.'" with filter "'.$df.'" in "'.Util::HID(Util::HID_CNAME, $hid).'"'); //3

			// load all records in group
			foreach ($db->Query($hid, DataStore::RIDS, $gid) as $id => $typ) {

				// don't exeed limit
				if ($max && ++$cnt == $max)
					break;

				// we do not count groups
				if ($typ & DataStore::TYP_GROUP)
					continue;

				// get record
				if (!($doc = $db->Query($hid, DataStore::RID, $id)))
					continue;

				// we do not care about records which were ok
				if ($doc->getVar('SyncStat') == DataStore::STAT_OK) {
					$cnt--;
					continue;
				}

				// check for filter
				if ($opts['FilterType']) {
					if ($hid & DataStore::TASK) {
						if ($doc->getVar(fldStatus::TAG) == 'COMPLETED') {
							$cnt--;
							continue;
						}
					} elseif ($hid & (DataStore::CALENDAR|DataStore::MAIL)) {
						if ($doc->getVar(fldStartTime::TAG) <= time() - $opts['FilterType']) {
							$cnt--;
							continue;
						}
					}
				}

				// check <ConversationId>
				// Setting the <ConversationMode> element to 0 (FALSE) in a GetItemEstimate request results in an Estimate element
				// value that only includes items that meet the <FilterType> element value.
				// Setting the value to 1 (TRUE) expands the result set to also include items with identical <ConversationId> element
				// in the <FilterType> result set.
				if ($cmod) {
					if (!$cid)
						$cid = $doc->getVar(fldConversationId::TAG);
					elseif ($doc->getVar(fldConversationId::TAG) != $cid) {
						$cnt--;
						continue;
					}
				}
			}

			// specifies the estimated number of items in the collection or folder that have to be synchronized
			$out->addVar('Estimate', strval($cnt));
			$out->restorePos($op);
		}

		$out->getVar('GetItemEstimate'); //3
		Debug::Msg($out, '<GetItemEstimate> output'); //3

		return TRUE;
	}

	/**
	 * 	Get status comment
	 *
	 *  @param  - Path to status code
	 * 	@param	- Return code
	 * 	@return	- Textual equation
	 */
	static public function status(string $path, string $rc): string { //2

		if (isset(self::STAT[$rc])) //2
			return self::STAT[$rc]; //2
		if (isset(masStatus::STAT[$rc])) //2
			return masStatus::STAT[$rc]; //2
		return 'Unknown return code "'.$rc.'"'; //2
	} //2

}

?>