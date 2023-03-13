<?php
declare(strict_types=1);

/*
 * 	<Search> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\HTTP;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\document\field\fldConversationId;
use syncgw\document\field\fldCreated;
use syncgw\document\field\fldLinkId;
use syncgw\document\field\fldAttach;
use syncgw\document\field\fldAttribute;

class masSearch {

	// module version number
	const VER 	  		 = 12;

	// status codes
	const PARM	  		 = '2';
	const SERVER  		 = '3';
	const LINK	  		 = '4';
	const ACCESS  		 = '5';
	const FOUND	  		 = '6';
	const CONNECT 		 = '7';
	const COMPLEX 		 = '8';
	const TIMEOUT 		 = '10';
	const FS	  		 = '11';
	const RANGE	  		 = '12';
	const BLOCKED 		 = '13';
	const CRED	  		 = '14';

	// status description
	const STAT   		 = [ //2
		self::SERVER	 => 'Server error', //2
		self::PARM		 => 'One or more of the client\'s search parameters was invalid', //2
		self::LINK		 => 'A bad link was supplied', //2
		self::ACCESS	 => 'Access was denied to the resource', //2
		self::FOUND		 => 'Resource was not found', //2
		self::CONNECT	 => 'Failed to connect to the resource', //2
		self::COMPLEX	 => 'The query was too complex', //2
		self::TIMEOUT	 => 'The search timed out', //2
		self::FS		 => 'The folder hierarchy is out of date', //2
		self::RANGE		 => 'The requested range has gone past the end of the range of retrievable results', //2
		self::BLOCKED	 => 'Access is blocked to the specified resource', //2
		self::CRED		 => 'To complete this request, basic credentials are required', //2
	]; //2

   /**
     * 	Singleton instance of object
     * 	@var masSearch
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masSearch {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'Search'));
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

		Debug::Msg($in, '<Search> input'); //3
		$out->addVar('Search', NULL, FALSE, $out->setCP(XML::AS_SEARCH));

		$mas = masHandler::getInstance();
		$db  = DB::getInstance();
		$hd  = Util::HID(Util::HID_PREF, DataStore::DATASTORES, TRUE);

		//----------------------------------------------------------------------------------------------------------------------------------------
		// $hids[]		- Specifies the classes that the client wants returned for a given collection
		// $grps[]  	- Specifies folder to search
		// $qry['=']	- Specifies free text to search for
		// $qry['ID']	- Specifies URI that is assigned by the server to certain resources
		// $qry['CV']	- Specifies <ConversationId>
		// $qry['D>=']	- Specifies e-mail <DateReceived> must be greater/equal than value
		// $qry['D<']	- Specifies e-mail <DateReceived> must be less  than value
		// $qry['V>=']	- Specifies [WHICH FIELD?] must be greater/equal than value - UNSUPPORTED
		// $qry['V<']	- Specifies [WHICH FIELD?] must be less than value - UNSUPPORTED
		// $or['=']		- Specifies free text to search for
		// $or['D>=']	- Specifies e-mail <DateReceived> must be greater/equal than value
		// $or['D<']	- Specifies e-mail <DateReceived> must be less  than value
		// $or['V>=']	- Specifies [WHICH FIELD?] must be greater/equal than value - UNSUPPORTED
		// $or['V<']	- Specifies [WHICH FIELD?] must be less than value - UNSUPPORTED
		//----------------------------------------------------------------------------------------------------------------------------------------
		$hids = [];
		$grps = [];
		$qry  = [ '=' => '', 'ID' => 0, 'CV' => 0, 'D>=' => 0, 'D<' => 0, 'V>=' => 0, 'V<' => 0 ];
		$or   = [ '=' => '', 		   			   'D>=' => 0, 'D<' => 0, 'V>=' => 0, 'V<' => 0 ];

		// set default handler
		// GAL				The client specifies "GAL" when it intends to search the Global Address List.
		// Mailbox			The client specifies "Mailbox" when it intends to search the store.
		// Document Library	The client specifies "DocumentLibrary" when it intends to search a Windows SharePoint Services or UNC library.
		$name = $in->getVar('Name', FALSE);
		Debug::Msg('Searching in "'.$name.'"'); //3
		if ($name == 'Mailbox')
			$hids[] = DataStore::MAIL;
		elseif ($name == 'GAL') {
			$hids[] = DataStore::CONTACT;

			// try to find GAL
			foreach ($db->getRIDS(DataStore::CONTACT) as $gid => $typ) {
				if ($typ == DataStore::TYP_GROUP) {
					$xml = $db->Query(DataStore::CONTACT, DataStore::RGID, $gid);
					if ($xml->getVar(fldAttribute::TAG) & fldAttribute::GAL) {
						$grps[] = $xml->getVar('GUID');

						// be sure to synchronize GAL
						$ds  = Util::HID(Util::HID_CNAME, DataStore::CONTACT);
				        $ds  = $ds::getInstance();
						$ds->syncDS($grps[0], TRUE);
						break;
					}
				}
			}
		} elseif ($name == 'DocumentLibrary"')
			$hids[] = DataStore::docLib;
		else {
			// @todo <Search> Document Library
        	$out->addVar('Status', self::FOUND);
			$out->getVar('Search'); //3
			Debug::Msg($out, '<Search> output'); //3
			return TRUE;
		}

		// load options
		// @todo <MIMESupport> Enables MIME support for email items that are sent from the server to the client
		// 		 Debug::Warn('+++ <Search><Option><MIMESupport> not supported'); //3
		// @todo <RightsManagementSupport> How the server returns rights-managed email messages to the client
		// 		 Debug::Warn('+++ <Search><Option><RightsManagementSupport> not supported'); //3
		$mas->loadOptions('Search', $in);
		$opts = $mas->getOption(strval($hids[0]));
		if ($hids[0] & DataStore::CONTACT)
			$opts['DeepTraversal'] = '1';

		// login with different credentioals
		if ($opts['UserName']) {
			$usr  = User::getInstance();
			$http = HTTP::getInstance();
			$http->updHTTPVar(HTTP::RCV_HEAD, 'User', $opts['UserName']);
			$http->updHTTPVar(HTTP::RCV_HEAD, 'Password', $opts['Password']);
			if (!$usr->Login($opts['UserName'], $opts['Password'], $mas->callParm('DeviceId'))) {
				$http->addHeader('WWW-Authenticate', 'Basic realm=masSearch');
				$mas->setHTTP(401);
				return FALSE;
			}
		}

		// <Query> specifies the keywords to use for matching the entries in the store that is being searched
		$qry['='] = $in->getVar('Query');

		// <EqualTo> contains a property and a value that are compared for equality during a search
		if ($in->getVar('EqualTo') !== NULL) {

			// (URI) that is assigned by the server to certain resources
			$p = $in->savePos();
			$qry['ID'] = $in->getVar('LinkId', FALSE);
			$in->restorePos($p);
		   	$qry['='] = $in->getVar('Value', FALSE);
   		}

		// <Store> location, string, and options for the search container - not used
   		// <And> it contains elements that specify items on which to perform an AND operation - not used
   		// If multiple <And> elements are included in the request, the server responds with a Status element value of 8 (SearchTooComplex).

		// <airsync:Class> specifies the classes that the client wants returned for a given collection
		// - Tasks
		// - Email
		// - Calendar
		// - Contacts
		// - Notes
		// - SMS
		if ($in->xpath('Class')) {
			while ($val = $in->getItem()) {
				$hids[] = intval(array_search(substr($val, 0, 1), $hd));
				$name .= ','.$val; //3
			}
		}

		// <FreeText> specifies a string value for which to search
		if (!$qry['='])
			$qry['='] = $in->getVar('FreeText');

		// <airsync:CollectionId> specifies the folder in which to search
		if ($in->xpath('CollectionId')) {
			while ($val = $in->getItem())
				$grps[] = $val;
		}

		// <ConversationId> specifies the conversation for which to search
		$qry['CV'] = $in->getVar('ConversationId');

		// <GreaterThan> specify a property and a value that are compared for a "greater than" condition
		// during a search. The <GreaterThan< element is supported only in mailbox searches. It is not
		// supported for document library searches. The comparison is made between the value of the
		// <Value> element and the date that a mailbox item was received. The <email:DateReceived> element
		// MUST be present before the <Value> element. Typically, this element is used to filter results
		// by the date on which they were received so that the date received is greater than the specified value
		if ($in->getVar('GreaterThan') !== NULL) {
			$p = $in->savePos();
			// <email:DateReceived> specifies the date and time the message was received by the current recipient
			$qry['D>='] = Util::unxTime($in->getVar('DateReceived', FALSE));
			$in->restorePos($p);
		   	$qry['V>='] = $in->getVar('Value', FALSE);
		}

		// <LessThan> is supported only in mailbox searches. It is not supported for document library
		// searches. The comparison is made between the value of the Value element and the date that a
		// mailbox item was received. The email:DateReceived element MUST be present before the Value
		// element. Typically, this element is used to filter results by the date on which they were
		// received so that the date received is less than the specified value.
		if ($in->getVar('LessThan') !== NULL) {
			$p = $in->savePos();
			// <email:DateReceived> specifies the date and time the message was received by the current recipient
			$qry['D<'] = Util::unxTime($in->getVar('DateReceived', FALSE));
			$in->restorePos($p);
		   	$qry['V<'] = $in->getVar('Value', FALSE);
		}

   		// <Or> it contains elements that specify items on which to perform an OR operation
   		if ($in->getVar('Or') !== NULL) {

   			$p = $in->savePos();

			// <FreeText> specifies a string value for which to search
			// $in->restorePos($p1);
			$or['='] = $in->getVar('FreeText', FALSE);

			// <GreaterThan> specify a property and a value that are compared for a "greater than" condition
			// during a search. The <GreaterThan< element is supported only in mailbox searches. It is not
			// supported for document library searches. The comparison is made between the value of the
			// <Value> element and the date that a mailbox item was received. The <email:DateReceived> element
			// MUST be present before the <Value> element. Typically, this element is used to filter results
			// by the date on which they were received so that the date received is greater than the specified value
			$in->restorePos($p);
			if ($in->getVar('GreaterThan', FALSE) !== NULL) {
				$p1 = $in->savePos();
				// <email:DateReceived> specifies the date and time the message was received by the current recipient
				$or['D>='] = Util::unxTime($in->getVar('DateReceived', FALSE));
				$in->restorePos($p1);
				$or['V>='] = $in->getVar('Value', FALSE);
			}

			// <LessThan> is supported only in mailbox searches. It is not supported for document library
			// searches. The comparison is made between the value of the Value element and the date that a
			// mailbox item was received. The email:DateReceived element MUST be present before the Value
			// element. Typically, this element is used to filter results by the date on which they were
			// received so that the date received is less than the specified value.
			$in->restorePos($p);
			if ($in->getVar('LessThan', FALSE) !== NULL) {
				$p1 = $in->savePos();
				// <email:DateReceived> specifies the date and time the message was received by the current recipient
				$or['D<'] = Util::unxTime($in->getVar('DateReceived', FALSE));
				$in->restorePos($p1);
			   	$or['V<'] = $in->getVar('Value', FALSE);
			}
		}

		Debug::Msg('Handler to search "'.$name.'"'); //3
		Debug::Msg('Folder to search "'.implode(', ', $grps).'"'); //3
		Debug::Msg($qry, 'Query to use'); //3

		// -------------------------------------------------------------------------------------------------------------------------------------------

		// $found[] = [ $hid, $grp, $gid ];
		$found = [];
		$gids  = [];

		// load old search results?
		foreach ($hids as $hid)
			$found += $mas->searchId(masHandler::LOAD, $hid);

		if (!count($found) || $opts['RebuildResults']) {

			//----------------------------------------------------------------------------------------------------------------------------------------
			// $hids[] - Specifies the classes that the client wants returned for a given collection
			// $grps[] - Specifies folder to search
			//----------------------------------------------------------------------------------------------------------------------------------------

			$n = 0;

			// folder to search known?
			if (count($grps)) {
				foreach ($grps as $grp) {
					// we assume all <CollectionId> were is same data store
				    foreach ($db->getRIDS($hids[0], $grp, boolval($opts['DeepTraversal'])) as $gid => $typ) {
				    	if ($typ == DataStore::TYP_DATA)
			   				$gids[$n++] = [ $hids[0], $gid ];
						}
				}
			} else {
				foreach ($hids as $hid) {
				    foreach ($db->getRIDS($hid, '', boolval($opts['DeepTraversal'])) as $gid => $typ) {
				    	if ($typ == DataStore::TYP_DATA)
			   				$gids[$n++] = [ $hid, $gid ];
				    	else
				    		// for trace purposes we need to read group record
				    		$db->Query($hid, DataStore::RGID, $gid);
					}
				}
			}
		}

		//----------------------------------------------------------------------------------------------------------------------------------------
		// now perform the search
		//----------------------------------------------------------------------------------------------------------------------------------------

		//----------------------------------------------------------------------------------------------------------------------------------------
		// $hids[]		- Specifies the classes that the client wants returned for a given collection
		// $grps[]  	- Specifies folder to search
		// $qry['=']	- Specifies free text to search for
		// $qry['ID']	- Specifies URI that is assigned by the server to certain resources
		// $qry['CV']	- Specifies <ConversationId>
		// $qry['D>=']	- Specifies e-mail <DateReceived> must be greater/equal than value
		// $qry['D<']	- Specifies e-mail <DateReceived> must be less  than value
		// $qry['V>=']	- Specifies [WHICH FIELD?] must be greater/equal than value - UNSUPPORTED
		// $qry['V<']	- Specifies [WHICH FIELD?] must be less than value - UNSUPPORTED
		// $or['=']		- Specifies free text to search for
		// $or['D>=']	- Specifies e-mail <DateReceived> must be greater/equal than value
		// $or['D<']	- Specifies e-mail <DateReceived> must be less  than value
		// $or['V>=']	- Specifies [WHICH FIELD?] must be greater/equal than value - UNSUPPORTED
		// $or['V<']	- Specifies [WHICH FIELD?] must be less than value - UNSUPPORTED
		//----------------------------------------------------------------------------------------------------------------------------------------

		Debug::Msg($gids, 'Records to scan'); //3

		for ($n=0; isset($gids[$n]); $n++) {

			// get document
			$doc = $db->Query($gids[$n][0], DataStore::RGID, $gids[$n][1]);

			if ($qry['ID']) {
				if ($doc->getVar(fldLinkId::TAG) == $qry['ID'])
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			} elseif ($qry['CV']) {
				if ($doc->getVar(fldConversationId::TAG) == $qry['CV'])
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			} elseif ($qry['D>=']) {
				if ($doc->getVar(fldCreated::TAG) >= $qry['D>='])
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			} elseif ($qry['D<']) {
				if ($doc->getVar(fldCreated::TAG) < $qry['D<'])
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			} elseif ($qry['=']) {
				// text can be somewhere in the record
        		$wrk = strip_tags($doc->saveXML());
        	 	if (stripos($wrk, $qry['=']) !== FALSE)
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			}

			if ($or['D>=']) {
				if ($doc->getVar(fldCreated::TAG) >= $or['D>='])
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			} elseif ($or['D<']) {
				if ($doc->getVar(fldCreated::TAG) < $or['D<'])
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			} elseif ($or['=']) {
        		$wrk = strip_tags($doc->saveXML());
        	 	if (stripos($wrk, $or['=']) !== FALSE)
					$found[] = [ $gids[$n][0], $doc->getVar('Group'), $gids[$n][1] ];
			}
		}

		// -----------------------------------------------------------------------------------------------------------------------------------
		// send back found data

		$out->addVar('Status', masStatus::OK);
		$out->addVar('Response');
		$out->addVar('Store');

		// anything found?
		if (!count($found)) {
            $out->addVar('Status', self::FOUND);
            // There is one <Result> element for each match that is found in the mailbox. If no matches are found,
            // an empty <Result> element is present in the <Store> container element of the response XML.
            if ($hid & DataStore::MAIL)
            	$out->addVar('Result');
			$out->getVar('Search'); //3
			Debug::Msg($out, '<Search> output'); //3
			return TRUE;
		} else
			$out->addVar('Status', masStatus::OK);

		// get range to send
		list($start, $end) = explode('-', $opts['Range']);
		$cnt = $opts['MaxPictures'];

		// get activesync version
		$ver = $mas->callParm('BinVer');

		// start sending
		for ($n=0; $n < $end - $start && isset($found[0]); $n++) {

			$p = $out->savePos();
			$out->addVar('Result', NULL, FALSE, $out->setCP(XML::AS_SEARCH));

			// get found record
			list($hid, $grp, $gid) = array_shift($found);

			if ($ver > 2.5) {
				$out->addVar('Class', Util::HID(Util::HID_ENAME, $hid), FALSE, $out->setCP(XML::AS_AIR));
				$out->addVar('LongId', $hid.'/'.$gid, FALSE, $out->setCP(XML::AS_SEARCH));
				$out->addVar('CollectionId', $grp, FALSE, $out->setCP(XML::AS_AIR));
			}

			$out->addVar('Properties', NULL, FALSE, $out->setCP(XML::AS_SEARCH));

			if ($hid & DataStore::CONTACT)
				$ds = 'syncgw\document\docGAL';
			else
				$ds = Util::HID(Util::HID_CNAME, $hid);
   			$ds = $ds::getInstance();

			$xml = new XML();
			$rec = $db->Query($hid, DataStore::RGID, $gid);
    	   	$ds->export($xml, $rec);

    	   	$xml->getChild('ApplicationData');
    	   	$pic = FALSE;
	        while (($val = $xml->getItem()) !== NULL) {
	        	switch ($xml->getName()) {
	        	case fldAttach::TAG:
	        		if ($ver < 12.0)
	        			break;
	        	}
	           	if ($xml->getName() == 'Picture') {
	           		$pic = TRUE;
					$out->addVar('Picture');
					$p1 = $out->savePos();
					$out->addVar('Status', masStatus::OK);
	           		$size = $opts['MaxSize'];
					$val  = $xml->getVar('Data', FALSE);
	       			if ($size - ($l = strlen($val)) < 0) {
	       				$out->restorePos($p1);
						$out->updVar('Status', masStatus::PICSIZE, FALSE);
						break;
	       			} else {
						$size -= $l;
						if ($cnt && !$cnt--) {
		       				$out->restorePos($p1);
							$out->addVar('Status', masStatus::PICLIMIT, FALSE);
							break;
						}
					}
					$out->addVar('Data', $val);
				} else
					$out->append($xml, FALSE);
	        }
	        // any pitcure found?
	        if ($hid & DataStore::CONTACT && !$pic) {
	        	$out->addVar('Picture', NULL, FALSE, $out->setCP(XML::AS_GAL));
	        	$out->addVar('Status', masStatus::NOPIC);
	        }

    	   	$out->restorePos($p);
		}

		// we do not need to re-login, because session will end here

		$out->addVar('Range', strval($start).'-'.strval($start + $n), FALSE, $out->setCP(XML::AS_SEARCH));
   		$out->addVar('Total', strval($n + count($found)));

		// save remaining results
		$mas->searchId(masHandler::SET, $found);

		$out->getVar('Search'); //3
		Debug::Msg($out, '<Search> output'); //3

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