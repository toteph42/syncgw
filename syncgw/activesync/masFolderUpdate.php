<?php
declare(strict_types=1);

/*
 * 	<FolderUpdate> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\document\field\fldDescription;
use syncgw\document\field\fldGroupName;
use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masFolderUpdate {

	// module version number
	const VER 	  		 = 10;

	// status codes
	const EXIST	  		 = '2';
	const RIP	  		 = '3';
	const FOUND	  		 = '4';
	const PARENT  		 = '5';
	const SERVER  		 = '6';
	const SYNCKEY 		 = '9';
	const FORMAT  		 = '10';
	const UNKNOWN 		 = '11';

	// status description
	const STAT    = [ //2
	    self::EXIST		 =>	'A folder with that name already exists or the specified folder is a special folder', //2
		self::RIP		 =>	'The specified folder is the Recipient information folder, which cannot be updated by the client', //2
		self::FOUND		 =>	'The specified folder does not exist', //2
		self::PARENT	 =>	'The specified parent folder was not found', //2
		self::SERVER	 => 'An error occurred on the server', //2
		self::SYNCKEY	 =>	'Synchronization key mismatch or invalid synchronization key', //2
		self::FORMAT	 =>	'Incorrectly formatted request', //2
		self::UNKNOWN	 =>	'An unknown error occurred', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masFolderUpdate
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masFolderUpdate {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'FolderUpdate'));
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

		Debug::Msg($in, '<FolderUpdate> input'); //3

		// moves a folder from one location to another on the server
		$out->addVar('FolderUpdate', NULL, FALSE, $out->setCP(XML::AS_FOLDER));
		$out->addVar('Status', $rc = masStatus::OK);

		// get last sync key
		$usr = User::getInstance();
		$key = $usr->syncKey('All');

		// check for key
		if (($k = $in->getVar('SyncKey')) === NULL) {
			Debug::Warn('<SyncKey> missing'); //3
			$rc = self::FORMAT;
		} elseif ($k != $key) {
    		Debug::Warn('<SyncKey> "'.$k.'" does not match "'.$key.'"'); //3
    		$rc = self::SYNCKEY;
    	}

		$out->addVar('Status', $rc);

		if ($rc != masStatus::OK) {
    		$out->getVar('FolderUpdate'); //3
    		Debug::Msg($out, '<FolderUpdate> output'); //3
		    return TRUE;
		}

		// identifies the folder on the server to be renamed or moved
		if (($hid = array_search(substr($fid = $in->getVar('ServerId'), 0, 1), Util::HID(Util::HID_PREF))) === NULL) {
			Debug::Warn('Data store for folder "'.$fid.'" not found'); //3
			$rc = self::REQUEST;
		}

		if ($rc == masStatus::OK) {

		    $db = DB::getInstance();

		    if ($par = $in->getVar('ParentId')) {
    			if (substr($par, 0, 1) != substr($fid, 0, 1)) {
    				Debug::Warn('Data store for parent folder "'.$par.'" not found'); //3
    				$rc = self::REQUEST;
    			} elseif (!$db->Query($hid, DataStore::RGID, $par)) {
    				Debug::Warn('Parent record "'.$par.'" not found'); //3
    				$rc = self::PARENT;
    			}
		    }

		    $nam = $in->getVar('DisplayName');

    		// represent the synchronization state of a collection
    		// If the <FolderCreate> command, <FolderDelete> command, or <FolderUpdate> command is not successful,
			// the server MUST NOT return a <SyncKey> element
			if ($rc == masStatus::OK) {
    		    $out->addVar('SyncKey', $usr->syncKey('All', 1));

		        // move/update folder

			    // need to reload record
    			$doc = $db->Query($hid, DataStore::RGID, $fid);
    			if ($par)
    				$doc->updVar('Group', '('.$par.')');
    			$doc->updVar(fldGroupName::TAG, $nam);
    			$dev = Device::getInstance();
    			$doc->updVar(fldDescription::TAG, 'Group provided by "'.$dev->getVar('GUID').'"' );
    			if (!$db->Query($hid, DataStore::UPD, $doc))
    				$rc = self::SERVER;
    		}
		}

		// update status
		if ($rc != masStatus::OK)
			$out->updVar('Status', $rc);

		$out->getVar('FolderUpdate'); //3
		Debug::Msg($out, '<FolderUpdate> output'); //3

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