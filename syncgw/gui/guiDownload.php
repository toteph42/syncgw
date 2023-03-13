<?php
declare(strict_types=1);

/*
 * 	Download data
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Attachment;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiDownload {

	// module version
	const VER = 11;

	// MIME format definition
	const FORMAT = [
			DataStore::CALENDAR  => [ 'ics', [ 'text/calendar',  	'2.0' ], ],
			DataStore::CONTACT   => [ 'vcf', [ 'text/vcard',		'4.0' ], ],
			DataStore::NOTE 	 => [ 'vnt', [ 'text/x-vnote',		'1.1' ], ],
			DataStore::TASK 	 => [ 'tsk', [ 'text/calendar',  	'2.0' ], ],
	];

    /**
     * 	Singleton instance of object
     * 	@var guiDownload
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiDownload {

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
	public function getInfo(XML &$xml, bool $status = FALSE): void {

		$xml->addVar('Opt', _('Download record plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		// file format
		$gui = guiHandler::getInstance();
		$gid = $gui->getVar('ExpGID');
		$hid = intval($gui->getVar('ExpHID'));
		$ct  = '';

		switch ($action) {
		case 'ExpDownload':
			$gui->updVar('Action', 'Explorer');

			$cnf = Config::getInstance();
			$db  = DB::getInstance();

			// save trace records
			if ($hid & DataStore::TRACE) {
				// locate trace directory
				$cnf  = Config::getInstance();
	       		$path = $cnf->getVar(Config::TRACE_DIR).$gid.DIRECTORY_SEPARATOR;
				// create tmp file
				$dest = Util::getTmpFile($ext = 'zip');
				$zip = new \ZipArchive();
				if (($rc = $zip->open($dest, \ZipArchive::CREATE|\ZipArchive::OVERWRITE)) !== TRUE) {
					$gui->putMsg(sprintf(_('Error opening file [%s] (%s)'), $dest, $rc), Util::CSS_ERR);
					break;
				}
				// swap all files
				if (!($dir = opendir($path))) {
					$zip->close();
					$gui->putMsg(sprintf(_('Error opening file [%s]'), $path), Util::CSS_ERR);
					break;
				}
				while($file = readdir($dir)) {
					if ($file == '.' || $file == '..')
						continue;
					if (!$zip->addFile(str_replace(DIRECTORY_SEPARATOR, '/', $path.$file), $file)) {
						$gui->putMsg(sprintf(_('Error writing file [%s]'), $file), Util::CSS_ERR);
						break;
					}
				}
				closedir($dir);
				$zip->close();
				$ct   = 'application/zip';
	       		break;
			}

			// load record
			if (!($doc = $db->Query($hid, DataStore::RGID, $gid)))
				break;

			// create open output file
			$dest = Util::getTmpFile('zip');

			if ($hid & DataStore::ATTACHMENT) {
			    $att = Attachment::getInstance();
    	        // we assume $gid is attachment record id
			    $val = $att->read($gid);
			    $ct  = $att->getVar('MIME');
			    $ext = Util::getFileExt($ct);
			    $gid = 'File';
			    file_put_contents($dest, $val);
			    break;
			}

			// build output document for data stores
			if ($hid & DataStore::DATASTORES) {
				$ds = Util::HID(Util::HID_CNAME, $hid);
				$ds = $ds::getInstance();
				$ds->loadXML('<syncgw/>');
				$ds->export($ds, $doc, self::FORMAT[$hid][1]);
				$data = $ds->getVar('Data');
				if (substr($data, 0, 8) == '<Folder>') {
					$ext = 'xml';
					$data = preg_replace('/></', '>'."\r\n".'<', $data);
				} else
					$ext = self::FORMAT[$hid][0];
				if (file_put_contents($dest, $data) === FALSE) {
					$gui->putMsg(sprintf(_('Error writing file [%s]'), $dest), Util::CSS_ERR);
   					unlink($dest);
 				break;
				}
				// set content type
				$ct = 'application/octet-stream';
				break;
			}

			break;

		default:
			break;
		}

		// start download
		if ($ct) {
			// log unsolicted output
			$log = Log::getInstance();
			$log->catchConsole(FALSE);
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: public');
			header('Content-Description: File Transfer');
			header('Content-Type: '.$ct);
			header('Content-Disposition: attachment; filename="'.$gid.'.'.$ext.'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '.filesize($dest));
			readfile($dest);
   			unlink($dest);
			exit();
		}

		// allow only during explorer call
		if (substr($gui->getVar('Action'), 0, 3) == 'Exp' && $gid && class_exists('ZipArchive') &&
		    ($hid & (DataStore::DATASTORES|DataStore::TRACE|DataStore::ATTACHMENT)))
			$gui->updVar('Button', $gui->getVar('Button').$gui->mkButton(_('Download'), _('Download selected record'), 'ExpDownload'));

		return guiHandler::CONT;
	}

}

?>