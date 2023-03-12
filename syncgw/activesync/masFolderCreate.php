<?php
declare(strict_types=1);

/*
 * 	<FolderCreate> handler class
 *
 *	@package	sync*gw
 * 	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\lib\User;
use syncgw\lib\XML;
use syncgw\document\field\fldDescription;
use syncgw\document\field\fldGroupName;

class masFolderCreate {

	// module version number
	const VER 	  		 = 10;

	// status codes
	const EXIST	  		 = '2';
	const SYSTEM  		 = '3';
	const PARENT  		 = '5';
	const SERVER  		 = '6';
	const SYNCKEY 		 = '9';
	const FORMAT  		 = '10';
	const UNKNOWN 		 = '11';
	const CODE	  		 = '12';
	// status description
	const STAT    		 = [ //2
		self::EXIST		 =>	'A folder that has this name already exists', //2
		self::SYSTEM	 =>	'The specified parent folder is a special system folder', //2
		self::PARENT	 =>	'The specified parent folder was not found', //2
		self::SERVER	 => 'An error occurred on the server', //2
		self::SYNCKEY	 =>	'Synchronization key mismatch or invalid synchronization key', //2
		self::FORMAT	 =>	'Malformed request', //2
		self::UNKNOWN	 =>	'An unknown error occurred', //2
		self::CODE		 =>	'Code unknown', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masFolderCreate
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masFolderCreate {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'FolderCreate'));
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

		Debug::Msg($in, '<FolderCreate> input'); //3

		// creates a new folder as a child folder of the specified parent folder
		$out->addVar('FolderCreate', NULL, FALSE, $out->setCP(XML::AS_FOLDER));

		// get last sync key
		$usr = User::getInstance();
		$key = $usr->syncKey('All');

		$rc = masStatus::OK;

		// <SyncKey> represent the synchronization state of a collection
		if (($k = $in->getVar('SyncKey')) === NULL) {
			Debug::Warn('<SyncKey> missing'); //3
			$rc = self::FORMAT;
		} elseif ($k != $key) {
    		Debug::Warn('<SyncKey> "'.$k.'" does not match "'.$key.'"'); //3
    		$rc = self::SYNCKEY;
    	}

		$out->addVar('Status', $rc);

		if ($rc != masStatus::OK) {
    		$out->getVar('FolderCreate'); //3
    		Debug::Msg($out, '<FolderCreate> output'); //3
		    return TRUE;
		}

    	// <ParentId> specifies the server ID of the parent folder
		// A parent ID of 0 (zero) signifies the mailbox root folder
		if (($pid = $in->getVar('ParentId')) == '0')
			$pid = '';

		// <DisplayName> specifies the name of the folder that is shown to the user
		$nam = $in->getVar('DisplayName');

		$cnf = Config::getInstance();
		$ena = $cnf->getVar(Config::ENABLED);

		// <Type> specifies the type of the folder to be created
		switch ($in->getVar('Type')) {
		case masFolderType::F_UTASK:
			if (!($ena & ($hid = DataStore::TASK))) {
				$rc = self::PARENT;
				Debug::Warn('Task data store not enabled'); //3
			}
			break;

		case masFolderType::F_UCALENDAR:
			if (!($ena & ($hid = DataStore::CALENDAR))) {
			 	$rc = self::PARENT;
				Debug::Warn('Calendar data store not enabled'); //3
			}
			break;

		case masFolderType::F_UCONTACT:
			if (!($ena & ($hid = DataStore::CONTACT))) {
			 	$rc = self::PARENT;
				Debug::Warn('Contact data store not enabled'); //3
			}
			break;

		case masFolderType::F_UNOTE:
			if (!($ena & ($hid = DataStore::NOTE))) {
			 	$rc = self::PARENT;
				Debug::Warn('Note data store not enabled'); //3
			}
			break;

		case masFolderType::F_UMAIL:
			if (!($ena & ($hid = DataStore::MAIL))) {
			 	$rc = self::PARENT;
				Debug::Warn('Mail data store not enabled'); //3
			}
			break;

		// masFolderType::F_GENERIC
		default:
			Debug::Warn('The requested folder type "'.$in->getVar('Type').'" is not supported'); //3
			$rc = self::SYSTEM;
			break;
		}

		// check parent folder
		if ($rc == masStatus::OK && $pid) {
			$db = DB::getInstance();
			if (!$db->Query($hid, DataStore::RGID, $pid)) {
				Debug::Warn('Parent "'.$pid.'" not found'); //3
				$rc = self::PARENT;
			}
		}

		// If the <FolderCreate> command, <FolderDelete> command, or <FolderUpdate> command is not successful,
		// the server MUST NOT return a <SyncKey> element
		if ($rc == masStatus::OK)
			$out->addVar('SyncKey', $usr->syncKey('All', 1));

		// uniquely identifies a new folder on a server
		if ($rc == masStatus::OK) {
			$db  = DB::getInstance();
			$dev = Device::getInstance();
			$xml = $db->mkDoc($hid, [ fldGroupName::TAG 	=> $nam,
									  fldDescription::TAG => 'Folder provided by "'.$dev->getVar('GUID').'"',
									  'Group' 	  			=> $pid ], TRUE);
			$out->addVar('ServerId', $xml->getVar('GUID'));
		} else
			$out->updVar('Status', $rc);

		$out->getVar('FolderCreate'); //3
		Debug::Msg($out, '<FolderCreate> output'); //3

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