<?php
declare(strict_types=1);

/*
 * 	Export trace file to HTML
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\DataStore;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiTraceExport {

	// module version
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var guiTraceExport
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiTraceExport {

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

		$xml->addVar('Opt', _('Export trace record plugin'));
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
		$ct  = NULL;

		switch ($action) {
		case 'ExpTraceExport':

			// load skeleton
			$skel = file_get_contents(Util::mkPath('gui/html/export.html'));
			$recs = [];
			foreach (explode("\n", file_get_contents($_SESSION[$gui->getVar('SessionID')][guiHandler::BACKUP])) as $rec)
				if (substr($rec, 0, 1) == '7')
					$recs[] = substr($rec, 1);

			if (!$recs) {
				$gui->putMsg(_('No trace data found'), Util::CSS_ERR);
				break;
			}

			$skel = str_replace('{TraceFile}', implode('', $recs), $skel);
			file_put_contents($html = Util::getTmpFile('html'), $skel);

			// create tmp file
			$dest = Util::getTmpFile('zip');
			$zip = new \ZipArchive();
			if (($rc = $zip->open($dest, \ZipArchive::CREATE|\ZipArchive::OVERWRITE)) !== TRUE) {
				$gui->putMsg(sprintf(_('Error opening file [%s] (%s)'), $dest, $rc), Util::CSS_ERR);
				break;
			}
			// swap all files
			foreach ([ $html, Util::mkPath('gui/html/favicon.ico'), Util::mkPath('gui/html/qbox.js'),
					   Util::mkPath('gui/html/style.css'), Util::mkPath('gui/html/syncgw.png') ] as $file)
				if (!$zip->addFile(str_replace(DIRECTORY_SEPARATOR, '/', $file), basename($file))) {
					$gui->putMsg(sprintf(_('Error writing file [%s]'), $file), Util::CSS_ERR);
					break;
				}
			$zip->close();
			$ct   = 'application/zip';
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
			header('Content-Disposition: attachment; filename="export.zip"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '.filesize($dest));
			readfile($dest);
   			unlink($dest);
   			unlink($html);
   			exit();
		}

		// allow only during explorer call
		$hid = intval($gui->getVar('ExpHID'));
		if (substr($gui->getVar('Action'), 0, 3) == 'Exp' && (($hid & DataStore::TRACE) || ($gui->getVar('ExpGRP') && ($hid & DataStore::DATASTORES))))
			$gui->updVar('Button', $gui->getVar('Button').$gui->mkButton(_('Export'), _('Export trace in message window'), 'ExpTraceExport'));

		return guiHandler::CONT;
	}

}

?>