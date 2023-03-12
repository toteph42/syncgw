<?php
declare(strict_types=1);

/*
 * 	<Ping> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masPing {

	// module version number
	const VER 		= 16;

	// status codes
	const EXPIRED	= '1';
	const CHANGE	= '2';
	const PARM		= '3';
	const FORMAT	= '4';
	const MAXHB		= '5';
	const MAXFOLDER	= '6';
	const HIERARCHY = '7';
	const SERVER	= '8';

	// status description
	const STAT      = [ //2
			self::EXPIRED		=> 	'The heartbeat interval expired before any changes occurred in the folders being monitored.', //2
			self::CHANGE		=>	'Changes occurred in at least one of the monitored folders. The response specifies the changed folders.', //2
			self::PARM			=>	'The Ping command request omitted required parameters.', //2
			self::FORMAT		=>	'Syntax error in Ping command request.', //2
			self::MAXHB			=>	'The specified heartbeat interval is outside the allowed range. For intervals that were too short, the '. //2
									'response contains the shortest allowed interval. For intervals that were too long, the response '. //2
									'contains the longest allowed interval.', //2
			self::MAXFOLDER		=>	'The Ping command request specified more than the allowed number of folders to monitor. The '. //2
									'response indicates the allowed number in the MaxFolders element', //2
			self::HIERARCHY		=>	'Folder hierarchy sync required.', //2
			self::SERVER		=>	'An error occurred on the server.', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masPing
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masPing {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'Ping'));
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

		Debug::Msg($in, '<Ping> input'); //3

		$cnf = Config::getInstance();
		$db  = DB::getInstance();
		$hdl = array_flip(Util::HID(Util::HID_PREF));
		$mas = masHandler::getInstance();

		// unregister shutdown function to protect any <SyncKey> value
		$srv = Server::getInstance();
		$srv->unregShutdown('syncgw\lib\User');

		$out->addVar('Ping', NULL, FALSE, $out->setCP(XML::AS_PING));
		$out->addVar('Status', self::EXPIRED);

		// get max. sleep time we support
   		$end   = $cnf->getVar(Config::HEARTBEAT);
		// sleep time
		$sleep = $cnf->getVar(Config::PING_SLEEP);

   		// it specifies the length of time, in seconds, that the server SHOULD wait before sending a response if no
   		// new items are added to the specified set of folders
		if (($hb = $in->getVar('HeartbeatInterval')) !== NULL) {
		    if ($hb < 10 || $hb > $end) {
				$out->updVar('Status', self::MAXHB);
				$out->addVar('HeartbeatInterval', strval($end));
				$out->getVar('Ping'); //3
				Debug::Msg($out, '<Ping> output'); //3
				return TRUE;
		    }
		} else
		    $hb = $end;

	    // end of time...
	    $end += time();

		// identifies the folder and folder type to be monitored by the client -> must be cached
		$grps = [];
		if ($in->xpath('//Folder')) {
			// get folder information
			while ($in->getItem() !== NULL) {

				// specifies the server ID of the folder to be monitored
				$gid = $in->getVar('Id', FALSE);

				if (!isset($hdl[substr($gid, 0, 1)]) || !($hid = $hdl[substr($gid, 0, 1)])) {
					$out->updVar('Status', self::EXPIRED);
					Debug::Msg('Handler for "'.$gid.'" not available / not enabled. Skipping check.'); //3
					continue;
				}

				// <Class> not used
				$grps[$gid] = $hid;

				// enable existing ping entries
				$mas->PingStat(masHandler::SET, $hid, $gid);
			}
		} else {
			// check all available handlers
			foreach (Util::HID(Util::HID_PREF, DataStore::DATASTORES) as $hid => $unused) {
				foreach ($mas->PingStat(masHandler::LOAD, $hid) as $gid)
					$grps[$gid] = $hid;
			}
		}
		$unused; // disable Eclipse warning

	    Debug::Msg($grps, 'Folder(s) to monitor'); //3

		// change buffer
		$chg = [];

		// process all folders
		while (time() < $end) {

			// process all groups
			foreach ($grps as $gid => $hid) {

				// synhronize data store
				$ds = Util::HID(Util::HID_CNAME, $hid);
				$ds = $ds::getInstance();
				if ($ds->syncDS($gid, TRUE) === FALSE) {
				    // we never should go here!
    				Debug::Warn('SyncDS() failed for ['.$gid.'] - this may be ok'); //3
					$mas->setStat(masHandler::EXIT);
    				return TRUE;
        		}

				// check for changed records
				Debug::Msg('Checking group ['.$gid.'] in '.Util::HID(Util::HID_CNAME, $hid)); //3
				if ($db->Query($hid, DataStore::RNOK, $gid))
					$chg[] = $gid;
			}

			// anything changed?
			if (count($chg))
				break;

			// are we debugging?
			if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) { //2
				Debug::Msg('We do not wait until end of timeout in "'.$hb.'" seconds'); //3
				$mas->setStat(masHandler::EXIT); //2
				return TRUE; //2
			} //2

			// check time to sleep
			if (time() + $sleep > $end)
        	    $sleep = $end - time();

        	// we split sleep into single seconds to catch parallel calls
			for ($i=0; $sleep > 0 && $i < $sleep; $i++)
				Util::Sleep(1);

            // double check at which point in time we were
            // we could go here e.g. if HTTP server has been suspended for a while
            // but we're sure connection from client has been dropped, so we don't need to send anything
       	    if (time() > $end) {
				$mas->setStat(masHandler::EXIT);
				return TRUE;
            }

            // request record refresh in external data base
            if (isset($hid))
	            $db->Refresh($hid);
		}

		// any folder changed?
		if (count($chg)) {
			$out->updVar('Status', self::CHANGE);
			$p = $out->savePos();

			$out->addVar('Folders');
			Debug::Msg($chg, 'Changed folders (at minimum one record has status != "OK")'); //3

			// identifies the folder and folder type to be monitored by the client
			foreach ($chg as $gid)
				$out->addVar('Folder', $gid);
			$out->restorePos($p);
		}

		// <MaxFolders> specifies the maximum number of folders that can be monitored -> we monitor as many as client want
		// The element is returned in a response with a status code of 6

		$out->getVar('Ping'); //3
		Debug::Msg($out, '<Ping> output'); //3

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