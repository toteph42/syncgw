<?php
declare(strict_types=1);

/*
 * 	<FolderSync> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\document\field\fldGroupName;
use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\document\field\fldAttribute;

class masFolderSync {

	// module version number
	const VER 			 = 12;

	// status codes
	const SERVER		 = '6';
	const SYNCKEY		 = '9';
	const FORMAT		 = '10';
	const UNKNOWN   	 = '11';
	const CODE	    	 = '12';

	// status description
	const STAT     		 = [ //2
		self::SERVER		=> 'An error occurred on the server', //2
		self::SYNCKEY		=> 'Synchronization key mismatch or invalid synchronization key', //2
		self::FORMAT		=> 'Incorrectly formatted request', //2
		self::UNKNOWN		=> 'An unknown error occurred', //2
		self::CODE			=> 'Code unknown', //2
	]; //2

	// type translation
	const TYPE_STD 		 = [
		DataStore::CALENDAR	=> masFolderType::F_CALENDAR,
		DataStore::CONTACT	=> masFolderType::F_CONTACT,
		DataStore::TASK		=> masFolderType::F_TASK,
		DataStore::NOTE		=> masFolderType::F_NOTE,
		DataStore::MAIL     => masFolderType::F_INBOX,
	];

	const TYPE_USR 		 = [
		DataStore::CALENDAR	=> masFolderType::F_UCALENDAR,
		DataStore::CONTACT	=> masFolderType::F_UCONTACT,
		DataStore::TASK		=> masFolderType::F_UTASK,
		DataStore::NOTE		=> masFolderType::F_UNOTE,
		DataStore::MAIL     => masFolderType::F_UMAIL,
	];

	const TYPE_MAIL 	 = [
		fldAttribute::MBOX_IN    	=> masFolderType::F_INBOX,
		fldAttribute::MBOX_DRAFT 	=> masFolderType::F_DRAFT,
		fldAttribute::MBOX_TRASH 	=> masFolderType::F_DELETED,
		fldAttribute::MBOX_SENT  	=> masFolderType::F_SENT,
		fldAttribute::MBOX_SPAM  	=> masFolderType::F_GENERIC,
		fldAttribute::MBOX_ARCH	=> masFolderType::F_GENERIC,
		fldAttribute::MBOX_OUT 	=> masFolderType::F_OUTBOX,
		fldAttribute::MBOX_USER 	=> masFolderType::F_GENERIC,
	];

    /**
     * 	Singleton instance of object
     * 	@var masFolderSync
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masFolderSync {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'FolderSync'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse XML node
	 *
	 * 	@param	- Input document
	 * 	@param	- Output document
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function Parse(XML &$in, XML &$out): bool {

		Debug::Msg($in, '<FolderSync> input'); //3

		// synchronizes the collection hierarchy but does not synchronize the items in the collections themselves
		$out->addVar('FolderSync', NULL, FALSE, $out->setCP(XML::AS_FOLDER));

		// default status
		$rc  = masStatus::OK;
        $add = FALSE;
		$usr = User::getInstance();
        $key = $usr->syncKey('All');

		// <SyncKey> represent the synchronization state of a collection
		if (($k = $in->getVar('SyncKey')) === NULL) {
			Debug::Warn('<SyncKey> missing'); //3
			$rc = self::FORMAT;
		} elseif (!$k)
		    $add = TRUE;
		elseif ($k != $key) {
    		Debug::Warn('<SyncKey> "'.$k.'" does not match "'.$key.'"'); //3
    		$rc = self::SYNCKEY;
    	}

    	$out->addVar('Status', $rc);

		if ($rc != masStatus::OK) {
    		$out->getVar('FolderSync'); //3
    		Debug::Msg($out, '<FolderSync> output'); //3
		    return TRUE;
		}

		// At any point, the client can repeat the initial FolderSync command, sending a
        // SyncKey element value of zero (0), and resynchronizing the entire hierarchy. Existing
        // folderhierarchy:ServerId values do not change when the client resynchronizes
		$out->addVar('SyncKey', $key);

		// <Changes> contains changes to the folder hierarchy
		$out->addVar('Changes');

		// <Count> specifies the number of added, deleted, and updated folders on the server since the last folder synchronization
		$out->addVar('Count', strval($cnt = 0));

		$db = DB::getInstance();
		$op = $out->savePos();

		// scan through all enabled data stores
	    foreach (Util::HID(Util::HID_CNAME, DataStore::DATASTORES) as $hid => $class) {

	    	// synchronize all groups only
			$ds = $class::getInstance();
			$ds->syncDS();

			// scan through all groups
		    foreach ($db->Query($hid, DataStore::GRPS) as $gid => $typ) {

				// read document
				if (!($xml = $db->Query($hid, DataStore::RGID, $gid)))
					continue;

				// check for deleted records
				switch ($stat = $xml->getVar('SyncStat')) {
				case DataStore::STAT_DEL:

					// specifies that a folder on the server was deleted since the last folder synchronization
					$out->addVar('Delete');
					// it specifies the server-unique identifier for a folder on the server
					$out->addVar('ServerId', $gid);
					$out->restorePos($op);
					// count delete action
					$cnt++;
					break;

				case DataStore::STAT_OK:

					if (!$add)
						break;

					$stat = DataStore::STAT_ADD;

				case DataStore::STAT_ADD:
				case DataStore::STAT_REP:

					// identifies a folder on the server that has been updated (renamed or moved)
					$out->addVar($stat == DataStore::STAT_REP ? 'Update' : 'Add');

					// it specifies the server-unique identifier for a folder on the server
					$out->addVar('ServerId', $gid);

					// specifies the server ID of the parent folder of the folder on the server that has been updated or added
					$n = $xml ? $xml->getVar('Group') : 0;
					$out->addVar('ParentId', $n ? $n : '0');

					// specifies the name of the folder that is shown to the user
					$n = $xml ? $xml->getVar(fldGroupName::TAG) : _('Default');
					$out->addVar('DisplayName', html_entity_decode($n));

					// specifies the type of the folder that was updated (renamed or moved) or added on the server
					if ($hid & DataStore::MAIL)
						$typ = self::TYPE_MAIL[$xml->getVar(fldAttribute::TAG) & fldAttribute::MBOX_TALL];
					else {
						if ($xml->getVar(fldAttribute::TAG) & fldAttribute::DEFAULT)
							$typ = self::TYPE_STD[$hid];
						else
							$typ = self::TYPE_USR[$hid];
					}
					$out->addVar('Type', $typ);
					$out->restorePos($op);

					$cnt++;

				default:
					break;
				}

				// change synchronization status
				if ($stat != DataStore::STAT_OK)
					$db->setSyncStat($hid, $xml, DataStore::STAT_OK);
		    }
	    }

	    if ($cnt) {
			$out->updVar('SyncKey', $usr->syncKey('All', 1));
  		    $out->updVar('Count', strval($cnt));
	    }

      	$out->getVar('FolderSync'); //3
    	Debug::Msg($out, '<FolderSync> output'); //3

		return TRUE;
	}

	/**
	 * 	Get status comment
	 *
	 *  @param  - Path to status code
	 * 	@param	- Return code
	 * 	@return	- Textual equation
	 */
	static public function status(string $path, string $rc): string {  //2

		if (isset(self::STAT[$rc])) //2
			return self::STAT[$rc]; //2
		if (isset(masStatus::STAT[$rc])) //2
			return masStatus::STAT[$rc]; //2
		return 'Unknown return code "'.$rc.'"'; //2
	} //2

}

?>