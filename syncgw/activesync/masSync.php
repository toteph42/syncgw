<?php
declare(strict_types=1);

/*
 * 	<Sync> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\document\field\fldAttribute;
use syncgw\document\field\fldExceptions;
use syncgw\document\field\fldStartTime;
use syncgw\document\field\fldStatus;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\document\field\fldConversationId;

class masSync {

	// module version number
	const VER 		= 23;

	// status codes
	const SYNCKEY  	= '3';
	const PROTOCOL 	= '4';
	const SERVER   	= '5';
	const CONV	   	= '6';
	const MATCH	   	= '7';
	const EXIST	   	= '8';
	const PROCESS  	= '9';
	const FOLDER   	= '12';
	const COMPLETE 	= '13';
	const WAIT	   	= '14';
	const CMD	   	= '15';
	const RETRY	   	= '16';

	// status description
	const STAT     	= [ //2
			self::SYNCKEY		=> 'Invalid or mismatched synchronization key. -or- Synchronization state corrupted on server', //2
			self::PROTOCOL		=> 'Protocol error. There was a semantic error in the synchronization request. The client '. //2
								   'is issuing a request that does not comply with the specification requirements', //2
			self::SERVER		=> 'Server error. Server misconfiguration, temporary system issue, or bad item', //2
			self::CONV			=> 'Error in client/server conversion', //2
			self::MATCH			=> 'Conflict matching the client and server object', //2
			self::EXIST			=> 'Object not found', //2
			self::PROCESS		=> 'The Sync command cannot be completed', //2
			self::FOLDER		=> 'The folder hierarchy has changed', //2
			self::COMPLETE		=> 'The Sync command request is not complete', //2
			self::WAIT			=> 'Invalid Wait or HeartbeatInterval value', //2
			self::CMD			=> 'Invalid Sync command request. Too many collections are included in the Sync request', //2
			self::RETRY			=> 'Retry. Something on the server caused a retriable error', //2
	]; //2

   	/**
     * 	Mail trash folder group
     *  @var [ Internal <GUID> => External <GID> ]
     */
    private $_trash = NULL;

    /**
     * 	Locked groups for command processing
     * 	@var array [ HID.'#'.GUID ] = 1
     */
    private $_lock;

    /**
     * 	Singleton instance of object
     * 	@var masSync
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masSync {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'Sync'));
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

		Debug::Msg($in, '<Sync> input'); //3

		// <Sync> synchronizes changes in a collection between the client and the server
		$out->addVar('Sync', NULL, FALSE, $out->setCP(XML::AS_AIR));

		$mas = masHandler::getInstance();
		$ver = $mas->callParm('BinVer');
		$dev = Device::getInstance();
		$cnf = Config::getInstance();
		$rc  = masStatus::OK;
		$usr = User::getInstance();
		$act = $usr->getVar('ActiveDevice');

		// -----------------------------------------------------------------------------------------------------------------------------------

		// number of received records
		$rcv = 0;
		// number of send records
		$snd = 0;
		// folder list
		$grps = [];
		// get all available short handler ID names
		$hids = Util::HID(Util::HID_PREF, DataStore::DATASTORES, TRUE);
		// no groups locked
		$this->_lock = [];

		// -----------------------------------------------------------------------------------------------------------------------------------

		// <Wait> specifies the number of minutes that the server SHOULD delay a response if no new items are added
		// to the included folders
		if (($wait = $in->getVar('Wait')) !== NULL)
		    $wait *= 60;
		// <HeartbeatInterval> specifies the number of seconds that the server SHOULD delay a response if no new items
		// are added to the included folders
		elseif (($wait = $in->getVar('HeartbeatInterval')) === NULL)
       		$wait = 0;

		// check max. sleep time we support
   		$hb = $cnf->getVar(Config::HEARTBEAT);
   		if ($wait > $hb) {
   			$out->addVar('Status', self::WAIT);

			// <Limit> specifies either the maximum number of collections that can be synchronized or the maximum/minimum
			// value that is allowed for the <Wait> interval or <HeartbeatInterval> interval
			// The <Limit> element is returned in a response with a status code of 14 or 15
   			$out->addVar('Limit', strval($hb));

   			$out->getVar('Sync'); //3
	    	Debug::Msg($out, '<Sync> output'); //3

			return TRUE;
   		}

   		// It is an optional child element of the Sync element in Sync command responses.
		$out->addVar('Status', $rc);

   		// -----------------------------------------------------------------------------------------------------------------------------------

		// <Options> control certain aspects of how the synchronization is performed
		$mas->loadOptions('Sync', $in);

		// -----------------------------------------------------------------------------------------------------------------------------------
		// <Collections> - skipped

		// build base structure

		if ($in->xpath('//Collection')) {

			// <Collections> serves as a container for the Collection element
			$out->addVar('Collections');

			while ($in->getItem() !== NULL) {

				$ip = $in->savePos();

				// <Collection> contains commands and options that apply to a particular collection
				$out->addVar('Collection', NULL, FALSE, $out->setCP(XML::AS_AIR));
	           	$op = $out->savePos();

				// <CollectionId> specifies the server ID of the folder to be synchronized
				// @opt <CollectionId> - value "RI" (recipient information cache)
				// specifies the recipient information cache
				if (($grp = $in->getVar('CollectionId', FALSE)) == 'RI')
					$rc = self::EXIST;
				else {
					// select handler ID
					if (!($hid = array_search(substr($grp, 0, 1), $hids)))
						$rc = self::EXIST;
				}

				// check status
				$skey = $usr->syncKey($grp);
				$in->restorePos($ip);
				$ckey = $in->getVar('SyncKey', FALSE);

				// we do not check <SyncKey>
				if ($ckey && $ckey != $skey)
					$rc = self::SYNCKEY;

				// <Class> identifies the class of the item being added, deleted from or changed in the collection
				if ($ver <= 12.1)
					$out->addVar('Class', Util::HID(Util::HID_ENAME, $hid));

				if ($rc == masStatus::OK) {

					// save group
					$grps[$grp] = $hid;

					// <Supported> specifies which contact and calendar elements in a <Sync> request
					// are managed by the client and therefore not ghosted
					$in->restorePos($ip);
					if ($in->getVar('Supported', FALSE) !== NULL) {
					    $in->getChild(NULL, FALSE);
				        $val = '';
					    while ($in->getItem() !== NULL)
					        $val .= $in->getName().';';
						$dev->updVar('ClientManaged', $val, FALSE);
					}
				}


				// @todo <ConversationMode> conversation modality within the result
				// whether to include items that are included within the conversation modality within the results
				// of the <Sync> response

				$in->restorePos($ip);
				if (!$ckey) {
					$skey = $usr->syncKey($grp, 1);
					$rcv++;
				}

				$out->addVar('SyncKey', $skey);
				$out->addVar('CollectionId', $grp, FALSE, $out->setCP(XML::AS_AIR));
				$out->addVar('Status', $rc);

				if ($rc != masStatus::OK) {
		   			$out->getVar('Sync'); //3
			    	Debug::Msg($out, '<Sync> output'); //3
					return TRUE;
				}

				// should we get changes?
				if (!$ckey) {
					// we do NOT reset records!
					// 3.1.5.4 Synchronizing Inbox, Calendar, Contacts, and Tasks Folders
					// The server responds with airsync:Add, airsync:Change, or airsync:Delete elements for items
					// in the collection.
				    $mas->setOption($grp, 'GetChanges', '0');

				    // reset group to be send next time
					$db = DB::getInstance();
					if ($xml = $db->Query($hid, DataStore::RGID, $grp))
						$db->setSyncStat($hid, $xml, DataStore::STAT_ADD, TRUE);
				} else
				    $mas->setOption($grp, 'GetChanges', '1');

				$in->restorePos($ip);
				$out->restorePos($op);
			}

			// -----------------------------------------------------------------------------------------------------------------------------------
			// now start command (input) processing
			// -----------------------------------------------------------------------------------------------------------------------------------

			// <Commands> contains operations that apply to a collection. Available operations are
			// add, delete, change, fetch, and soft delete
			$in->restorePos($ip);
			$in->getChild('Commands', FALSE);
			while ($rc == masStatus::OK && $in->getItem() !== NULL) {

				// <Responses> contains responses to operations that are processed by the server
				$op1 = $out->savePos();
				if ($out->getVar('Responses') === NULL) {
					$out->restorePos($op1);
					$out->addVar('Responses', NULL, FALSE, $out->setCP(XML::AS_AIR));
				}

				$rcv++;

				if (!self::_chkCmd($in, $out, $hid, $grp))
					break;

				// special hack to delete optional return parameter
				if ($cnf->getVar(Config::HACK) & Config::HACK_WINMAIL) {
					$ip1 = $in->savePos();
					if ($in->getVar('Change') !== NULL) {
						if ($out->xpath('/CollectionId[text()="'.$grp.'"}/Responses')) {
							$out->getItem();
							$out->delVar(NULL, FALSE);
						}
					}
					$in->restorePos($ip1);
				}
			}
			$in->restorePos($ip);
			$out->restorePos($op);
		}

		// -----------------------------------------------------------------------------------------------------------------------------------
		// start output processing
		// -----------------------------------------------------------------------------------------------------------------------------------

		// <Partial> sent a partial list of collections
		// indicates to the server that the client sent a partial list of collections, in which case
		// the server obtains the rest of the collections from its cache
		// The <Partial> element is an empty tag element, meaning it has no value or data type
		// is already taken care abbout, as we think it is an empty <Sync>

       	// if we do not get <Collections> it's a empty <Sync> and we load data from cache

		if ((!$rcv && !count($grps)) || $in->getVar('Partial') !== NULL) {
			if (!$rcv && !count($grps)) //3
				Debug::Msg('No <Sync> received - restoring from cache'); //3
			foreach (Util::HID(Util::HID_TAB, DataStore::DATASTORES, FALSE) as $hid => $unused) {
				if ($usr->xpath('//Device[DeviceId="'.$act.'"]/DataStore[HandlerID="'.$hid.'"]/Sync/Group/.')) {
					while (($grp = $usr->getItem()) !== NULL) {

						// already known?
						if (isset($grps[$grp]))
							continue;

						$grps[$grp] = $hid;

						if ($out->getVar('Collections')) {
							$out->getVar('Sync');
							$out->addVar('Collections');
						}

						$out->addVar('Collection', NULL, FALSE, $out->setCP(XML::AS_AIR));
						$out->addVar('SyncKey', $usr->syncKey($grp));
						$out->addVar('CollectionId', $grp, FALSE, $out->setCP(XML::AS_AIR));
						$out->addVar('Status', $rc);
					}
				}
			}
			$unused; // disable Eclipse warning
		}
		Debug::Msg($grps, 'Folders to check'); //3

		// save sync requests
		$don = [];
		foreach ($grps as $grp => $hid) {

			// handler id alreay processed?
			if (!isset($don[$hid])) {

				// delete any cached request
				if ($usr->xpath('//Device[DeviceId="'.$act.'"]/DataStore[HandlerID="'.$hid.'"]/Sync/Group/.')) {
					while ($usr->getItem() !== NULL)
						$usr->delVar(NULL, FALSE);
				}
				// handler processed
				$don[$hid] = 1;
			}

			// cache request
			$usr->xpath('//Device[DeviceId="'.$act.'"]/DataStore[HandlerID="'.$hid.'"]/Sync/.');
			$usr->getItem();
			$usr->addVar('Group', $grp);
		}

		// check all groups specified
		foreach ($grps as $grp => $hid) {
			$osnd = $snd;
			if (!self::_chkGrp($hid, $grp, $out, $snd))
				break;
			// did we neither received or send something for this group?
			if ($snd == $osnd && !$rcv)
				// invalidate synchronization key
				$usr->syncKey($grp, -1);
		}

		// could we send something?
		if (!$snd && $wait) {

	        // are we debugging?
	        if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) //3
  				Debug::Warn('We do not wait until end of timeout "'.$wait.'" seconds'); //3
			else { //3

				// sleep time
				$sleep = $cnf->getVar(Config::PING_SLEEP);

			   	// <Sync> cancelled?
			   	while ($wait > 0) {

				    // we wait a while
				    $wait -= $sleep;
				   	Util::Sleep($sleep);

					// check all groups specified
				   	foreach ($grps as $grp => $hid) {
				   		$osnd = $snd;
						if (!($rc = self::_chkGrp($hid, $grp, $out, $snd)))
							break;
						// did we send something for this group?
						if ($snd == $osnd)
							$usr->syncKey($grp, -1);
				   	}

				   	// did we found any changes?
				   	if ($rc)
				   		break;
			   	}
			} //3
		}

		// empty Sync Request and Response
		if (!($rcv + $snd)) {
   	    	Debug::Msg('<Sync> is EMPTY'); //3
   	    	$out = NULL;
           	return TRUE;
		}

		$out->getVar('Sync'); //3
    	Debug::Msg($out, '<Sync> output'); //3

		return TRUE;
	}

	/**
	 * 	Command input processing
	 *
	 * 	@param 	- Input Document
	 * 	@param 	- Ouutput Document
	 * 	@param 	- Handler id
	 * 	@param 	- Group id
	 * 	@return - TRUE=Ok, FALSE=Error
	 */
	private function _chkCmd(XML $in, XML &$out, int $hid, ?string $grp): bool {

		$mas = masHandler::getInstance();
		$db  = DB::getInstance();
		$usr = User::getInstance();

		// save input / output position
		$ip  = $in->savePos();
		$op  = $out->savePos();

		// set default status code
		$rc  = masStatus::OK;

		// get document handler
		// load data store handler
		if (!($ds = Util::HID(Util::HID_CNAME, $hid))) {
			$out->addVar('Status', self::PROTOCOL, FALSE, $out->setCP(XML::AS_AIR));
			return TRUE;
		}
		$ds = $ds::getInstance();

		// <GUID> of mail to send
		$send = NULL;

		// get MAS version
		$ver  = $mas->callParm('BinVer');

		// load options
		$opts = $mas->getOption(strval($grp ? $grp : $hid));

		switch ($tag = $in->getName()) {
		case 'Change':

			// <Change> modifies properties of an existing object on the device or the server
			$out->addVar($tag, NULL, FALSE, $out->setCP(XML::AS_AIR));

			// <Class> identifies the class of the item being added to the collection
			// - Tasks
			// - Email
			// - Calendar
			// - Contacts
			// - SMS
			// - Notes
			if ($ver >= 14)
				$out->addVar('Class', Util::HID(Util::HID_TAB, $hid));

			// replace record allowed?
			$in->restorePos($ip);
			if ($gid = $in->getVar('ServerId', FALSE)) {

			    // <ServerId> It represents a unique identifier that is assigned by the server to each object
				// that can be synchronized
				$out->addVar('ServerId', $gid);

				// lock record for <Command> processing
				$this->_lock[$hid.'#'.$gid] = 1;

				if ($doc = $db->Query($hid, DataStore::RGID, $gid)) {
					if ($doc->getVar('SyncStat') == DataStore::STAT_REP && $opts['Conflict'])
						$rc = self::MATCH;
				} else {
					$rc = self::EXIST;
					break;
				}
			}

			$in->restorePos($ip);

			// <Response>
		 	// When protocol version 16.0 or 16.1 is used and the changed object is a calendar item, the object is
	 		// identified by both the ServerId element of the master item as well as the airsyncbase:InstanceId element
			// of the specific occurrence.

			// for instance id we need to extract exception
			if ($ver >= 16.0 && ($iid = $in->getVar('InstanceId', FALSE))) {

				// convert to internal time
				$iid = Util::unxTime($iid);

				// create dummy document
				$xml = new XML();
				$xml->loadXML('<syncgw><Data/></syncgw>');

				// find instance
				if (!$doc->xpath('//['.fldExceptions::SUB_TAG[2].'="'.$iid.'"]', FALSE)) {

					// swap existing data
					$doc->getChild('Data');
					$xml->getVar('Data');
					while ($doc->getItem() !== NULL)
						$xml->append($doc, FALSE);

					// check for <InstanceId>
					if (!$xml->xpath('Data/'.fldExceptions::SUB_TAG[2])) {
						$xml->getVar('Data');
						$xml->addVar(fldExceptions::SUB_TAG[2], $iid);
					}

					if ($doc->getVar(fldExceptions::TAG) === NULL) {
						$doc->getVar('Data');
						$doc->addVar(fldExceptions::TAG);
					}
				} else {

					// swap existing data
					$dp = $doc->savePos();
					$doc->getChild('', FALSE);
					$xml->getVar('Data');
					while ($doc->getItem() !== NULL)
						$xml->append($doc, FALSE);

					$doc->restorePos($dp);
					$doc->delVar();
					$doc->getVar(fldExceptions::TAG);
				}

				$xml->setTop(); //3
				Debug::Msg($xml, 'Dummy document for exception "'.$iid.'" created'); //3
				$ds->loadXML($xml->saveXML());

				// Data elements from the content classes. For details about the content classes, see
				// [MS-ASCAL], [MS-ASCNTC], [MS-ASEMAIL], [MS-ASMS], [MS-ASNOTE], and [MS-ASTASK].
				$in->setTop();
				if (!$ds->import($in, 0, $grp)) {
					$rc = self::EXIST;
					break;
				}

				// swap data
				$doc->addVar(fldExceptions::SUB_TAG[0]);
				$ds->getChild('Data');
				while ($ds->getItem() !== NULL)
					$doc->append($ds, FALSE);

		        $doc->getVar('syncgw'); //3
		    	Debug::Msg($doc, 'Changed document'); //3

				// read external record to get it into trace
				$db->Query(DataStore::EXT|$hid, DataStore::RGID, $doc->getVar('extGroup'));
		    	$db->Query(DataStore::EXT|$hid, DataStore::RGID, $doc->getVar('extID'));
				$db->Query(DataStore::EXT|$hid, DataStore::UPD, $doc);

		    	// save/update document in internal data store
		        if ($db->Query($hid, DataStore::UPD, $doc) === FALSE) {
					$rc = self::PROTOCOL;
				    break;
			    }

			} else {
				$ds->loadXML($doc->saveXML());

				// Data elements from the content classes. For details about the content classes, see
				// [MS-ASCAL], [MS-ASCNTC], [MS-ASEMAIL], [MS-ASMS], [MS-ASNOTE], and [MS-ASTASK].
				if (!$ds->import($in, DataStore::UPD, $grp)) {
  					$rc = self::EXIST;
  					break;
				}
			}

			// <Send> optional element that specifies whether an email is to be saved as a draft or sent
			$in->restorePos($ip);
			if ($in->getVar('Send', FALSE) !== NULL)
				$send = $gid;

			// <airsyncbase:InstanceId> - particular instance of a recurring series
            // specifies the original, unmodified, UTC date and time of a particular instance of a recurring series
			if ($iid)
               	$out->addVar('InstanceId', $iid, FALSE, $out->setCP(XML::AS_BASE));
			break;

		case 'Delete':

			// <Delete> deletes an object on the device or the server
			$out->addVar($tag, NULL, FALSE, $out->setCP(XML::AS_AIR));

			// <ServerId> It represents a unique identifier that is assigned by the server to each object that
			// can be synchronized
			$out->addVar('ServerId', $gid = $in->getVar('ServerId', FALSE));

			// lock record for <Command> processing
			$this->_lock[$hid.'#'.$gid] = 1;

			// <airsyncbase:InstanceId> - particular instance of a recurring series
            // specifies the original, unmodified, UTC date and time of a particular instance of a recurring series
   			$in->restorePos($ip);
			if ($iid = $in->getVar('InstanceId', FALSE)) {

   				// add to output data
   				$out->addVar('InstanceId', $iid, FALSE, $out->setCP(XML::AS_BASE));

    			// get full record
    			if (!($xml = $db->Query($hid, DataStore::RGID, $gid)))
    				break;

    			// add exception
    			if ($xml->getVar(fldExceptions::TAG) === NULL) {
    				$xml->getVar('Data');
   					$xml->addVar(fldExceptions::TAG);
    			}
   				$xml->addVar(fldExceptions::SUB_TAG[0]);
   				$xml->addVar(fldExceptions::SUB_TAG[1], NULL);
   				$xml->addVar(fldExceptions::SUB_TAG[2], Util::unxTime($iid));
   				$xml->getVar('syncgw'); //3
   				Debug::Msg($xml, 'Deleted instance added'); //3
    			$db->Query($hid|DataStore::EXT, DataStore::UPD, $xml);
    			$db->Query($hid, DataStore::UPD, $xml);
   			}
			// do not delete -> move to trash folder
			elseif ($hid & DataStore::MAIL && $opts['DeletesAsMoves']) {

				// do we know trash folder?
				if (!$this->_trash)
					$this->_trash = $ds->getBoxID(fldAttribute::MBOX_TRASH);

				// move to trash folder
				if ($xml = $db->Query($hid, DataStore::RGID, $gid)) {
			       	$xml->updVar('Group', $this->_trash[0]);
					$xml->updVar('extGroup', $this->_trash[1]);
					$db->Query($hid, DataStore::UPD, $xml);
				}
			} else {
				// delete record
				if (!$ds->delete($gid))
					$rc = self::EXIST;
			}
			break;

		case 'Add':

			// <Add> It creates a new object in a collection on the client or on the server
			$out->addVar($tag, NULL, FALSE, $out->setCP(XML::AS_AIR));

			// <Class> identifies the class of the item being added to the collection
			// - Tasks
			// - Email
			// - Calendar
			// - Contacts
			// - SMS
			// - Notes
			// The Class element is not included in <Sync> Add responses when the class of the collection
			// matches the item class.
			// if ($ver >= 14)
			// 	$out->addVar('Class', Util::HID(Util::HID_TAB, $hid));

			$lid = $in->getVar('ClientId', FALSE);

			// we need to check parent, since <Attachments> also contains a <ClientId>
			$in->setParent();
			$in->setParent();
			if ($in->getName() != 'Commands')
			    $lid = '';

			// special check added for clients which send add request for same record mutiple times
			// e.g. Nine
			if ($lid && ($xml = $db->Query($hid, DataStore::RLID, $lid))) {
				$out->addVar('ClientId', $lid);
				$out->addVar('ServerId', $gid = $xml->getVar('GUID'));

				// lock record for <Command> processing
				$this->_lock[$hid.'#'.$gid] = 1;
				break;
			}

			if (!$ds->import($in, DataStore::ADD, $grp, $lid)) {
   				$rc = self::PROTOCOL;
   				break;
			}

			// <ClientId> contains a unique identifier (typically an integer) that is generated by the client to
			// temporarily identify a new object that is being created by using the <Add> element
			if ($lid)
				$out->addVar('ClientId', $lid);

			// <Send> optional element that specifies whether an email is to be saved as a draft or sent
   			$in->restorePos($ip);
			if ($in->getVar('Send', FALSE) !== NULL)
				$send = $gid;

			// <ServerId> It represents a unique identifier that is assigned by the server to each object
   			// that can be synchronized
			$out->addVar('ServerId', $gid = $ds->getVar('GUID'));

			// lock record for <Command> processing
			$this->_lock[$hid.'#'.$gid] = 1;
			break;

		case 'Fetch':

			// <Fetch> is used to request the application data of an item that was truncated in a
			// synchronization response from the server
			$out->addVar($tag);

			// <ServerId> It represents a unique identifier that is assigned by the server to each object
			// that can be synchronized
			$out->addVar('ServerId', $gid = $in->getVar('ServerId', FALSE));

			// lock record for <Command> processing
			$this->_lock[$hid.'#'.$gid] = 1;

			// contains data for a particular object, such as a contact, email message, calendar appointment, or task item
           	if ($doc = $db->Query($hid, DataStore::RGID, $gid))
				if (!$ds->export($out, $doc))
	               	$rc = self::EXIST;

		default:
			break;
		}

		// <Send> The presence of the tag in a Sync command request indicates that the email is to be sent;
		// the absence of the tag indicates that the email is to be saved as a draft.
		if ($send && $hid & DataStore::MAIL) {

			// load record
			$xml = $doc = $db->Query($hid, $tag == 'Add' ? DataStore::RGID : DataStore::RLID, $send);

			// set new group
			$id = $ds->getBoxID('Sent');
			$doc->updVar('Group', $id[0]);
			$doc->updVar('extGroup', $id[1]);
			if (!$db->Query($hid, DataStore::ADD, $doc))
				$rc = masStatus::SERVER;
			// send mail
			elseif ($usr->getVar('SendDisabled') || !$db->SendMail(FALSE, $doc))
				$rc = masStatus::SUBMIT;
			// delete old document
			elseif (!$db->Query($hid, DataStore::DEL, $xml))
				$rc = masStatus::SERVER;

			// check for <MoveAlways>
			if ($rc == masStatus::OK && $hid & DataStore::MAIL && ($tag == 'Change' || $tag == 'Add') &&
				($val = $doc->getVar(fldConversationId::TAG)) && $usr->xpath('//MoveAlays/CID[text()="'.$val.'"]')) {
				$usr->getItem();
				$attr = $usr->getAttr();
				if (isset($attr['Int']))
					$doc->updVar('Group', $attr['Int']);
				if (isset($attr['Ext']))
					$doc->updVar('extGroup', $attr['Ext']);
				// add new document
				if ($rc == masStatus::OK && !$db->Query($hid, DataStore::ADD, $doc))
					$rc = masStatus::SERVER;
				// delete old document
				elseif (!$db->Query($hid, DataStore::DEL, $xml))
					$rc = masStatus::SERVER;
			}
		}

		$out->addVar('Status', $rc, FALSE, $out->setCP(XML::AS_AIR));

		$in->restorePos($ip);
		$out->restorePos($op);

		return TRUE;
	}

	/**
	 * 	Check a folder for changes
	 *
	 * 	@param 	- Handler ID
	 * 	@param 	- Group ID
	 * 	@param 	- Output document
	 * 	@param 	- Send counter
	 * 	@return - TRUE=Ok; FALSE; Stop
	 */
	private function _chkGrp(int $hid, string $grp, XML &$out, int &$snd): bool {

		$mas = masHandler::getInstance();
		$db  = DB::getInstance();
		$cnf = Config::getInstance(); //2

		// get options for group
		$opts = $mas->getOption($grp);

		// we need to resync data stores here to get in sync. with trace processing
	    if (!($ds = Util::HID(Util::HID_CNAME, $hid)))
	    	return FALSE;

	    // check for locked record
	    if (isset($this->_lock[$hid.'#'.$grp]))
	    	return TRUE;

	    $ds = $ds::getInstance();
		if (!$ds->syncDS($grp, TRUE))
			return FALSE;

		// locate collection in output document
		$out->xpath('CollectionId[text()="'.$grp.'"]/Status');
		$out->getItem();
		$op = $out->savePos();

		// If the synchronization is successful, the server responds by sending all objects in the collection.
		// The response includes a new synchronization key value that the client MUST use on the next
		// synchronization of the collection.
		$usr = User::getInstance();
		$out->updVar('SyncKey', $usr->syncKey($grp, 1));

		// we send back collection ID if <SyncKey> was '0'
		if (!$opts['GetChanges']) {
			$snd++;
			return TRUE;
		}

		// command tag inserted?
		$ctag = FALSE;

		// read all record to process
		if (!($recs = $db->Query($hid, DataStore::RNOK, $grp)))
			$recs = [];

		if ($hid & DataStore::TASK) //3
			$df = $opts['FilterType'] == -1 ? 'Incomplete task' : 'All task'; //3
		else $df = gmdate('D Y-m-d G:i:s', time() - $opts['FilterType']); //3
			Debug::Msg('Read modified records in group "'.$grp.'" with filter "'.$df.'" in "'.Util::HID(Util::HID_CNAME, $hid).'"'); //3

		foreach ($recs as $rid => $typ) {

		    // check for locked record
		    if (isset($this->_lock[$hid.'#'.$rid]))
	    		continue;

			// indicates there are more changes than the number that are requested in the WindowSize element
			if ($snd++ == $opts['WindowSize']) {
				$out->getVar('Collection');
				// indicates there are more changes than the number that are requested in the WindowSize element
				$out->addVar('MoreAvailable', NULL, FALSE, $out->setCP(XML::AS_AIR));
				break;
			}

			// skip folders
			if ($typ != DataStore::TYP_DATA)
				continue;

			// load record
			if (!($doc = $db->Query($hid, DataStore::RGID, $rid)))
				continue;

			if (!$ctag) {
				$ctag = TRUE;
				// <Commands> that contains operations that apply to a collection. Available operations are add,
				// delete, change, fetch, and soft delete
				$out->addVar('Commands');
				$op = $out->savePos();
			}

			// check filter
			if ($opts['FilterType']) {
			    $p = $out->savePos();
				if ($hid & DataStore::TASK) {
				    if ($opts['FilterType'] == -1 && $doc->getVar(fldStatus::TAG) == 'COMPLETED') {
				        // deletes an object from the client when it falls outside the <FilterType>
						$out->addVar('SoftDelete', NULL, FALSE, $out->setCP(XML::AS_AIR));
						$out->addVar('ServerId', $rid);
						$db->setSyncStat($hid, $doc, DataStore::STAT_OK);
   						$out->restorePos($p);
     					// jump to next folder
						continue;
					}
				} elseif ($hid & DataStore::CALENDAR) {
					$t = $doc->getVar(fldStartTime::TAG);
                    if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) { //2
                    	Debug::Msg('Disabling filter for calendar debugging purpose'); //3
                        $t = $opts['FilterType']; //2
					} //2
					if ($t < $opts['FilterType']) {
						// deletes an object from the client when it falls outside the <FilterType>
						$out->addVar('SoftDelete', NULL, FALSE, $out->setCP(XML::AS_AIR));
						$out->addVar('ServerId', $rid);
						$db->setSyncStat($hid, $doc, DataStore::STAT_OK);
   						$out->restorePos($p);
      					// jump to next folder
   						continue;
					}
				}
			}

			$p = $ds->savePos();

			// check status of document
			switch ($doc->getVar('SyncStat')) {
			case DataStore::STAT_ADD:
				// create a new object in a collection on the client or on the server
				$out->addVar('Add', NULL, FALSE, $out->setCP(XML::AS_AIR));
				$out->addVar('ServerId', $rid);

				// It contains data for a particular object, such as a contact, email message, calendar appointment, or task item
				$doc->restorePos($p);
				if (!$ds->export($out, $doc))
					return TRUE;
				$db->setSyncStat($hid, $doc, DataStore::STAT_OK);
				break;

			case DataStore::STAT_REP:
				// modifies properties of an existing object on the device or the server
				$out->addVar('Change', NULL, FALSE, $out->setCP(XML::AS_AIR));
				$out->addVar('ServerId', $rid);

				// It contains data for a particular object, such as a contact, email message, calendar appointment, or task item
				$doc->restorePos($p);
				if (!$ds->export($out, $doc))
					return TRUE;
				$db->setSyncStat($hid, $doc, DataStore::STAT_OK);
				break;

			case DataStore::STAT_DEL:
				// that deletes an object on the device or the server
				$out->addVar('Delete', NULL, FALSE, $out->setCP(XML::AS_AIR));
				$out->addVar('ServerId', $rid);
				// now delete record
				$db->Query($hid, DataStore::DEL, $rid);
				break;

			default:
				break;
			}
			$out->restorePos($op);
		}

		// read document
		if ($doc = $db->Query($hid, DataStore::RGID, $grp))
			// set sync. status to folder
			$db->setSyncStat($hid, $doc, DataStore::STAT_OK);

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