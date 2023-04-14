<?php
declare(strict_types=1);

/*
 * 	<ResolveRecipients> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Config; //2
use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\document\field\fldAttribute;
use syncgw\document\field\fldPhoto;
use syncgw\document\field\fldMailHome;
use syncgw\document\field\fldMailWork;
use syncgw\document\field\fldMailOther;
use syncgw\document\field\fldFullName;

class masResolveRecipients {

	// module version number
	const VER 		= 15;

	// status codes
	const SUGGEST 	= '2';
	const PARTIAL	= '3';
	const CONTACT	= '4';
	const PROT		= '5';
	const SERVER	= '6';

	const C_OK 		= '1';
	const C_NONE 	= '7';
	const C_LIMIT   = '8';

	// status description
	const STAT      = [ //2
		self::SUGGEST		=> 'The recipient was found to be ambiguous. The returned list of recipients are suggestions', //2
    	self::PARTIAL		=> 'The recipient was found to be ambiguous', //2
		self::CONTACT		=> 'The recipient did not resolve to any contact or GAL entry. No certificates were returned', //2
    	self::PROT			=> 'Protocol error. Either an invalid parameter was specified or the range exceeded limits', //2
    	self::SERVER		=> 'An error occurred on the server. The client SHOULD retry the request', //2

		self::C_OK			=> 'Ok', //2
		self::C_NONE		=> 'The recipient does not have a valid S/MIME certificate. No certificates were returned', //2
		self::C_LIMIT		=> 'The global certificate limit was reached and the recipient\'s certificate could not be returned', //2
	]; //2

   /**
     * 	Singleton instance of object
     * 	@var masResolveRecipients
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masResolveRecipients {

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'ResolveRecipients'));
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

		Debug::Msg($in, '<ResolveRecipients> input'); //3

		// resolve a list of supplied recipients, to retrieve their free/busy information, and optionally,
		// to retrieve their S/MIME certificates so that clients can send encrypted S/MIME email messages
		$out->addVar('ResolveRecipients', NULL, FALSE, $out->setCP(XML::AS_RESOLVE));
		$out->addVar('Status', masStatus::OK);

		$mas = masHandler::getInstance();
		$ver = $mas->callParm('BinVer');
		$db  = DB::getInstance();
		$cnf = Config::getInstance(); //2

		// load options
		$mas->loadOptions('ResolveRecipients', $in);
		$opts = $mas->getOption();

		// @todo CERT <CertificateRetrieval> Specifies whether S/MIME certificates are returned by the server for each resolved recipient

		// get start and end time
		list($start, $end) = explode('/', $opts['Availability']);

		// max. number of pictures to show
		$maxpic = $opts['MaxPictures'];

		// specifies one or more recipients to be resolved
		$op = $out->savePos();
		$in->xpath('//To');
		while (($usr = $in->getItem()) !== NULL) {

			// change user name?
			if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) //2
				$usr = $cnf->getVar(Config::DBG_USR); //2

			// perform search on user
			$rc = self::_search(DataStore::USER, $usr, intval($start), intval($end));

			// perform search on contacts
			if (Util::HID(Util::HID_CNAME, DataStore::CONTACT))
				$rc += self::_search(DataStore::CONTACT, $usr, intval($start), intval($end));

			$out->restorePos($op);
			// contains information as to whether the recipient was resolved
			$out->addVar('Response');
			// specifies a recipient to be resolved
			$out->addVar('To', $usr);

			// anything found?
			if (!count($rc)) {
				$out->addVar('Status', self::CONTACT);
				continue;
			} else
				$out->addVar('Status', masStatus::OK);

			// specifies the number of recipients that are returned in the ResolveRecipients command response
			$out->addVar('RecipientCount', strval(count($rc)));

			$max = $opts['MaxAmbiguousRecipients'];
			foreach ($rc as $gid => $r) {

				if (!--$max)
					break;

				$p = $out->savePos();

				// represents a single recipient that has been resolved
				$out->addVar('Recipient');

	 			// either a contact entry (2) or a GAL entry (1)
				$out->addVar('Type', $r[1]);

				$doc = $db->Query ($r[0], DataStore::RGID, $gid);

				// load record
				if ($r[0] & DataStore::USER) {

					// contains the display name of the recipient
					if (($v = $doc->getVar('DisplayName')) !== NULL)
						$out->addVar('DisplayName', $v);

					$doc->xpath('//EMail');
					if ($v = $doc->getItem())
						$out->addVar('EmailAddress', $v);

				} else {

					// contains the display name of the recipient
					if (($v = $doc->getVar(fldFullName::TAG)) !== NULL)
						$out->addVar('DisplayName', $v);

					// email address of the recipient, in SMTP format
					foreach ([ fldMailHome::TAG, fldMailWork::TAG, fldMailOther::TAG ] as $tag) {
						if ($doc->xpath('//'.$tag.'/.')) {
							if ($v = $doc->getItem()) {
						        $out->addVar('EmailAddress', $v);
						        break;
							}
						}
					}
				}

				// indicates to the server that free/busy data is being requested by the client
				// and identifies the start time and end time of the free/busy data to retrieve
				if ($ver >= 14.0 && $start) {
					$p1 = $out->savePos();
					$out->addVar('Availability');
					if ($r[2]) {
						$out->addVar('Status', masStatus::OK);
						// specifies the free/busy information for the users or distribution list identified in the request
						$out->addVar('MergedFreeBusy', $r[2]);
					} else
						// Free/busy data could not be retrieved from the server for a given recipient.
						$out->addVar('Status', masStatus::AVAIL);
					$out->restorePos($p1);
				}

				// @todo CERT <Certificates> - information about the certificates for a recipient
				// contains information about the certificates for a recipient
				//	Debug::Warn('+++ <ResolveRecipients><Option><CertificateRetrieval> not supported'); //3
				// 	Debug::Warn('+++ <ResolveRecipients><Option><MaxCertificates> not supported'); //3
				// @todo CERT <CertificateCount> - number of valid certificates
				// specifies the number of valid certificates that were found for the recipient
				// @opt <RecipientCount> - number of recipient
				// specifies the number of recipients that are returned in the ResolveRecipients command response
				// @todo CERT <Certificate> - X509 certificate
				// contains the X509 certificate binary large object (BLOB) that is encoded with base64 encoding
				// @todo CERT <MiniCertificate> - mini-certificate BLOB
				// contains the mini-certificate BLOB that is encoded with base64 encoding
				if ($opts['CertificateRetrieval'] > 1) {
					$p1 = $out->savePos();
					$out->addVar('Certificates');
					$out->addVar('Status', self::C_NONE);
					$out->restorePos($p1);
				}

				// contains the data related to the contact photos
				if (($r[0] & DataStore::CONTACT) && $ver >= 14.1) {
	       			$out->addVar('Picture');
					$fld = fldPhoto::getInstance();
					$xml = new XML();
					$xml->loadXML('<syncgw><Data/></syncgw>');
					$doc->getVar('Data');
					$xml->getVar('Data');
					$fld->export($mas->MIME[DataStore::CONTACT][0], 1.0, '', $doc, 'Photo', $xml);
					if ($xml->xpath('//Photo/.')) {
					    $size = $opts['MaxSize'];
						$val  = base64_decode($xml->getItem());
	       				if ($size - ($l = strlen($val)) < 0)
						    $out->addVar('Status', masStatus::PICSIZE);
						else {
							$size -= $l;
							if ($maxpic && !$maxpic--)
							    $out->addVar('Status', masStatus::PICLIMIT);
							else {
							    $out->addVar('Status', masStatus::OK);
								$out->addVar('Data', base64_encode($val));
							}
						}
					} else
					    $out->addVar('Status', masStatus::NOPIC);
				}

				$out->restorePos($p);
			}
		}

		$out->getVar('ResolveRecipients'); //3
		Debug::Msg($out, '<ResolveRecipients> output'); //3

		return TRUE;
	}

	/**
	 * 	Perform search
	 *
	 *	@param 	- Handler Id
	 * 	@param	- User to search for
	 *  @param 	- Start time window
	 *  @param  - End time window
	 * 	@return - [ $gid => $hid, $typ (1-GAL, 2-contact), free-busy buffer ]
	 */
	private function _search(int $hid, string $usr, int $start, int $end): array {

		Debug::Msg('Perform search for "'.$usr.'"'); //3

		$db = DB::getInstance();
		$rc = [];

		// @todo https://www.rfc-editor.org/rfc/rfc4515.txt

		// Each digit in the <MergedFreeBusy> element value string indicates the free/busy status for the user or
		// distribution list for every 30 minute interval
		// - 0 Free
		// - 1 Tentative
		// - 2 Busy
		// - 3 Out of Office (OOF)
		// - 4 No data
		// A string value of "32201" would represent that this user or group of users is out of the office for the
		// first 30 minutes, busy for the next hour, free for 30 minutes, and then has a tentative meeting for the
		// last 30 minutes. If the user or group of users has a change in availability that lasts less than the
		// interval value of 30 minutes, the availability value with the higher digit value is assigned to the whole
		// interval period. For example, if a user has a 25 minutes of free time (value 0) followed by 5 minutes
		// of busy time (value 2), the 30 minute interval is assigned a value of 2 in the server response.
		// The server determines the number of digits to include in the MergedFreeBusy element by dividing
		// the time interval specified by the StartTime element (section 2.2.3.176.1) value and the EndTime
		// element (section 2.2.3.61.1) value by 30 minutes, and rounding the result up to the next integer.
		// The MergedFreeBusy element value string is populated from the StartTime element value onwards,
		// therefore the last digit represents between a millisecond and 30 minutes. A query for data from
		// 13:00:00 to 13:30:00 returns a single digit but a query from 12:59:59 to 13:30:00 or 13:00:00 to
		// 13:30:01 returns two digits.
		// Any appointment that ends inside a second of the interval requested shall impact the digit
		// representing that timeframe. For example, given a calendar that contains a 5 minute OOF appointment
		// from 12:00 to 12:05, and is free the rest of the day, queries would result in the following:
		// - If a query is made for 12:00:00 to 13:00:00, the result is "30", where each digit represents
		// 	 exactly 30 minutes.
		// - If a query is made for 12:04:59 to 13:00:00, the result is "30", where the "0" maps to 12:34:59
		//   to 13:00:00.
		// If a query is made for 12:05:00 to 13:00:00, the result is "00" where the second 0 maps the last
		// 25 minutes of the interval.
		// The client MUST consider daylight saving time transitions and might need to add or remove time
		// intervals from the MergedFreeBusy element value string, as there are days that have more or less
		// than 24 hours

		if ($hid & DataStore::USER) {

			$found = FALSE;
			foreach ($db->getRIDS($hid) as $gid => $typ) {

				// load document
				if (!($doc = $db->Query($hid, DataStore::RGID, $gid))) {
					Debug::Err('Error reading record "'.$gid.'"'); //3
					return $rc;
				}

				$found = FALSE;
				if (($email = $doc->getVar('EMailPrime')) !== $usr) {
					$doc->xpath('//EMailSec');
					while($email = $doc->getItem())
						if ($email == $usr) {
							$found = TRUE;
							break;
						}
				} else
					$found = TRUE;

				if ($found)
					break;
			}

			// load document
			if (!$found)
				return $rc;

			// create free/busy array with free time (default)
			for($fb=[], $s=$start; $s < $end; $s+=1800)
				$fb[$s] = 0;

			// get free/busy slots
			$doc->xpath('Slot');
			while ($val = $doc->getItem()) {

				// extract start / end / type from slot
				list($s, $e, $typ) = explode('/', $val);

				// find start time
				if ($start < $s)
					continue;

				while ($start <= $end && $start <= $e)
					$fb[$start] = $typ;
			}

			$rc[$uid] = [ $hid, '2', implode('', $fb) ];

		} else {

			// Global address list GID
			$gal = NULL;

			foreach ($db->getRIDS($hid) as $gid => $typ) {

				if ($typ != DataStore::TYP_DATA) {
					if (!$gal) {
						$xml = $db->Query(DataStore::CONTACT, DataStore::RGID, $gid);
						if ($xml->getVar(fldAttribute::TAG) & fldAttribute::GAL)
							$gal = $xml->getVar('GUID');
					}
					continue;
				}

				// load document
				if (!($xml = $db->Query($hid, DataStore::RGID, $gid))) {
					Debug::Err('Error reading record "'.$gid.'"'); //3
					break;
				}

				// perform search
				if (stripos($xml->saveXML(), '>'.$usr) !== FALSE)
					$rc[$gid] = [ $hid, $xml->getVar('Group') == $gal ? '1' : '2', 0 ];

				// special check
				elseif ($hid & DataStore::USER && $uid) {
					if ($xml = $db->Query($hid, DataStore::RGID, $uid))
						if (stripos($xml->saveXML(), '>'.$uid) !== FALSE)
							$rc[$uid] = [ $hid, $xml->getVar('Group') == $gal ? '1' : '2', 0 ];
				}
			}
		}

		return $rc;
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