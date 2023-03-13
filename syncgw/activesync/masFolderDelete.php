<?php
declare(strict_types=1);

/*
 * 	<FolderDelete> handler class
 *
 * 	@package	sync*gw
 * 	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masFolderDelete {

	// module version number
	const VER 		     = 8;

	// status codes
	const SYSTEM  		 = '3';
	const EXIST	  		 = '4';
	const SERVER  		 = '6';
	const SYNCKEY 		 = '9';
	const FORMAT  		 = '10';
	const UNKNOWN 		 = '11';
	// status description
	const STAT    		 = [ //2
		self::SYSTEM	 => 'The specified folder is a special system folder and cannot be deleted by the client', //2
		self::EXIST		 => 'The specified folder does not exist', //2
		self::SERVER	 => 'An error occurred on the server', //2
		self::SYNCKEY	 =>	'Synchronization key mismatch or invalid synchronization key', //2
		self::FORMAT	 =>	'Incorrectly formatted request', //2
		self::UNKNOWN	 =>	'An unknown error occurred', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masFolderDelete
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masFolderDelete {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'FolderDelete'));
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

		Debug::Msg($in, '<FolderDelete> input'); //3

		// deletes a folder from the server
		$out->addVar('FolderDelete', NULL, FALSE, $out->setCP(XML::AS_FOLDER));

		// get last sync key
		$usr = User::getInstance();
		$key = $usr->syncKey('All');

		$rc = masStatus::OK;

		// represent the synchronization state of a collection
		if (($k = $in->getVar('SyncKey')) === NULL) {
			Debug::Warn('<SyncKey> missing'); //3
			$rc = self::FORMAT;
		} elseif ($k != $key) {
    		Debug::Warn('<SyncKey> "'.$k.'" does not match "'.$key.'"'); //3
    		$rc = self::SYNCKEY;
    	}

		$out->addVar('Status', $rc);

		if ($rc != masStatus::OK) {
    		$out->getVar('FolderDelete'); //3
    		Debug::Msg($out, '<FolderDelete> output'); //3
		    return TRUE;
		}

		// specifies the folder on the server to be deleted, and it is a unique identifier assigned by the server
		// to each folder that can be synchronized
		if (($hid = array_search(substr($fid = $in->getVar('ServerId'), 0, 1), Util::HID(Util::HID_PREF))) === NULL) {
			Debug::Warn('Data store for folder "'.$fid.'" not found'); //3
			$rc = self::REQUEST;
		}

		// represent the synchronization state of a collection
		// If the <FolderCreate> command, <FolderDelete> command, or <FolderUpdate> command is not successful,
		// the server MUST NOT return a <SyncKey> element
		if ($rc == masStatus::OK) {
		    $out->addVar('SyncKey', $usr->syncKey('All', 1));

    		// delete folder
			$db = DB::getInstance();
			if (!$db->Query($hid, DataStore::DEL, $fid)) {
				Debug::Warn('Record "'.$fid.'" does not exist'); //3
				$rc = self::EXIST;
			}

			// delete folder itself
			if (!$db->Query($hid, DataStore::DEL, $fid)) {
				Debug::Warn('Record "'.$fid.'" does not exist'); //3
				$rc = self::EXIST;
			}
		}

		// update status
		if ($rc != masStatus::OK)
			$out->updVar('Status', $rc);

		$out->getVar('FolderDelete'); //3
		Debug::Msg($out, '<FolderDelete> output'); //3

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