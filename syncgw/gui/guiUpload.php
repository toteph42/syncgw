<?php
declare(strict_types=1);

/*
 *	Upload data record
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\lib\Attachment;

class guiUpload {

	// module version
	const VER = 11;

    /**
     * 	Singleton instance of object
     * 	@var guiUpload
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiUpload {

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

		$xml->addVar('Opt', _('Upload record plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		$gui = guiHandler::getInstance();

		switch ($action) {
		case 'ExpUpLoad':
			// handler
			$hid = 0;
			// file extension
			$ext = strtolower(substr($_FILES['ExpUploadFile']['name'], strrpos($_FILES['ExpUploadFile']['name'], '.') + 1));
			// get data
			$wrk = file_get_contents($_FILES['ExpUploadFile']['tmp_name']);
			if ($ext != 'zip' && $ext != 'png')
				$wrk = str_replace([ "\r", '<', '>' ], [ '', '&lt;', '&gt;' ], $wrk);

			switch ($ext) {
			case 'zip':
				$hid = DataStore::TRACE;
				break;

			case 'jpg':
				$hid = DataStore::USER;
				break;

			case 'ics':
				$hid = DataStore::CALENDAR;
				break;

			case 'tsk':
				$hid = DataStore::TASK;
				break;

			case 'vcf':
				$hid = DataStore::CONTACT;
				break;

			case 'vnt':
				$hid = DataStore::NOTE;
				break;

			case 'xml':
				$hid = DataStore::DATASTORES;
				break;

			default:
				$gui->putMsg(sprintf(_('The file format \'*.%s\' is not support in this data store'), $ext), Util::CSS_ERR);
				break;
			}

			if (!$hid)
				break;

			$ds = Util::HID(Util::HID_CNAME, $hid);
			$ds = $ds::getInstance();

			if ($hid & DataStore::DATASTORES) {
				// supported input mime types
				$mime = [
					DataStore::CONTACT 	=> [ 'text/vcard',							'4.0' ],
					DataStore::CALENDAR => [ 'text/calendar',  					    '2.0' ],
					DataStore::TASK		=> [ 'text/calendar',  				    	'2.0' ],
					DataStore::NOTE		=> [ 'text/x-vnote',						'1.1' ],
				];

				$recs = [];
				if ($hid & DataStore::CONTACT) {
		          	$w1 = []; // disable Eclipse warning

					// get all contacts
					preg_match_all('/(?<=BEGIN:VCARD).*(?=END:VCARD)/Us', $wrk, $w1);
					foreach ($w1[0] as $w2)
						$recs[] = 'BEGIN:VCARD'.$w2.'END:VCARD'."\n";
					if (substr($w2, 9, 3) == '2.1') {
						$mime[$hid][0] = 'text/x-vcard';
						$mime[$hid][1] = '2.1';
					}
				} elseif ($hid & DataStore::CALENDAR) {
					// extract any time zone data
					preg_match_all('/(?<=BEGIN:VTIMEZONE).*(?=END:VTIMEZONE)/Us', $wrk, $w1);
					$tz = [];
					$w3 = []; // disable Eclipse warning
					foreach ($w1[0] as $w2) {
						preg_match('/(?<=TZID:).*(?=\n)/Us', $w2, $w3);
						$tz[$w3[0]] = 'BEGIN:VTIMEZONE'.$w2.'END:VTIMEZONE'."\n";
					}
					// get all events
					preg_match_all('/(?<=BEGIN:VEVENT).*(?=END:VEVENT)/Us', $wrk, $w1);
					foreach ($w1[0] as $w2) {
						$w3 = 'BEGIN:VCALENDAR'."\n".'VERSION:'.$mime[$hid][1]."\n".'BEGIN:VEVENT';
						$w6 = '';
						$ok = [];
						$w4 = []; // disable Eclipse warning
						// check for time zones
						if (preg_match_all('/(?<=TZID=").*(?=")/Us', $w2, $w4)) {
							foreach ($w4[0] as $w5) {
								if (!isset($ok[$w5])) {
									$w6 .= isset($tz[$w5]) ? $tz[$w5] : '';
									$ok[$w5] = TRUE;
								}
							}
						}
						$recs[] = $w3.$w2.'END:VEVENT'."\n".$w6."\n".'END:VCALENDAR'."\n";
					}
				} elseif ($hid & DataStore::TASK) {
					// get all to dos
					preg_match_all('/(?<=BEGIN:VTODO).*(?=END:VTODO)/Us', $wrk, $w1);
					foreach ($w1[0] as $w2) {
						$w3 = 'BEGIN:VCALENDAR'."\n".'VERSION:'.$mime[$hid][1]."\n".'BEGIN:VTODO';
						// check for time zones
						$w6 = '';
						$ok = [];
						if (preg_match_all('/(?<=TZID=").*(?=")/Us', $w2, $w4)) {
							foreach ($w4[0] as $w5) {
								if (!isset($ok[$w5])) {
									$w6 .= isset($tz[$w5]) ? $tz[$w5] : '';
									$ok[$w5] = TRUE;
								}
							}
						}
						$recs[] = $w3.$w2.'END:VTODO'."\n".$w6."\n".'END:VCALENDAR'."\n";
					}
				} elseif ($hid & DataStore::NOTE) {
					// get all notes
					preg_match_all('/(?<=BEGIN:VNOTE).*(?=END:VNOTE)/Us', $wrk, $w1);
					foreach ($w1[0] as $w2)
						$recs[] = 'BEGIN:VNOTE'.$w2.'END:VNOTE'."\n";
				}

				// import all records
				$xml = new XML();
				foreach ($recs as $rec) {
						$xml->loadXML(
							'<Add>'.
								'<CmdID>1</CmdID>'.
								'<Meta>'.
									'<Type xml-ns="syncml:MetInf">'.$mime[$hid][0].'</Type>'.
									'<Ver>'.$mime[$hid][1].'</Ver>'.
								'</Meta>'.
								'<Item xml-ns="syncml:SyncML">'.
									'<Data>'.$rec.'</Data>'.
								'</Item>'.
							'</Add>'
					);
					// set position
					if ($xml->getVar('Add') === NULL) {
						$gui->putMsg(_('Error loading data object'), Util::CSS_ERR);
						$xml = NULL;
						break;
					}
					$ds->importSyncML($xml, $gui->getVar('ExpGRP'));
				}
				if (!$xml)
					break;
			} elseif ($hid & DataStore::USER) {
				$db  = DB::getInstance();
				$xml = $db->Query(DataStore::USER, DataStore::RGID, $gui->getVar('ExpGID'));
				$att = Attachment::getInstance();
				$pic = $att->create($wrk);
    			if (!$xml->xpath('//Data/Photo')) {
					$xml->getVar('Data');
					$xml->addVar('Photo', $pic);
    			} else {
		    		$xml->getItem();
					$xml->setVal($pic);
    			}
				$db->Query(DataStore::USER, DataStore::UPD, $xml);
			} else {

				// must be trace
				$cnf = Config::getInstance();
				if ($path = $cnf->getVar(Config::TRACE_DIR)) {
					$n = substr($_FILES['ExpUploadFile']['name'], 0, strrpos($_FILES['ExpUploadFile']['name'], '.'));
					if (file_exists($path.$n)) {
						$gui->putMsg(sprintf(_('Trace [%s] already exists'), $n), Util::CSS_WARN);
						break;
					}
					mkdir($path.$n);
					$zip = new \ZipArchive();
					if (($rc = $zip->open($_FILES['ExpUploadFile']['tmp_name'])) !== TRUE) {
						$gui->putMsg(sprintf(_('Error opening file [%s] (%s)'), $_FILES['ExpUploadFile']['tmp_name'], $rc), Util::CSS_ERR);
						break;
					}
					$zip->extractTo($path.$n);
					$zip->close();
				}
			}
			$gui->updVar('Action', 'Explorer');
			$gui->clearAjax();

			return guiHandler::RESTART;

		default:
			break;
		}

		$hid = intval($gui->getVar('ExpHID'));
		if (substr($gui->getVar('Action'), 0, 3) == 'Exp' && ($hid & DataStore::TRACE|DataStore::USER|DataStore::DATASTORES)) {
			$cmd = $gui->getVar('Button').
				   '<input type="file" name="ExpUploadFile" size="1" style="opacity: 0; z-index: 2; position: relative; '.
				   'filter: alpha(opacity=0); width:70px; font-size:80%;" '.
				   'onchange="document.getElementById(\'Action\').value=\'ExpUpLoad\';sgwAjaxStop(1);document.syncgw.submit();" '.
				   'title="';
			if ($hid & DataStore::TRACE)
				$cmd .= _('Upload *.zip file into trace data store');
			if ($hid & DataStore::USER)
				$cmd .= _('Upload *.jpg file as photo for selected user');
			if ($hid & DataStore::CALENDAR)
				$cmd .= _('Upload *.ics or *.xml file into calendar data store');
			if ($hid & DataStore::TASK)
				$cmd .= _('Upload *.tsk or *.xml file into task data store');
			if ($hid & DataStore::CONTACT)
				$cmd .= _('Upload *.vcf or *.xml file into contact data store');
			if ($hid & DataStore::NOTE)
				$cmd .= _('Upload *.vnt or *.xml file into notes data store');
			$gui->setVal($cmd.'" /><input type="button" style="position: relative; left: -70px; width:70px; font-size:80%;" '.
						'value="'._('Upload').'" />');

		}

		return guiHandler::CONT;
	}

}

?>