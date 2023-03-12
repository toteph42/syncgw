<?php
declare(strict_types=1);

/*
 * 	Mail document handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\XML;
use syncgw\document\mime\mimAsMail;
use syncgw\activesync\masHandler;
use syncgw\document\field\fldAttribute;

class docMail extends docHandler {

	// module version number
	const VER = 6;

 	/**
     *  External and internal special folder GUIDS
     *  @var array
     */
    private $_boxes;

   /**
     * 	Singleton instance of object
     * 	@var docMail
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): docMail {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			self::$_obj->_mimeClass = [
	    				mimAsMail::getInstance(),
			];
			self::$_obj->_hid 		= DataStore::MAIL;
			self::$_obj->_docVer 	= self::VER;
			// no boxes loaded
			self::$_obj->_boxes 	= NULL;
			self::$_obj->_init();
	   	}

		return self::$_obj;
	}

    /**
	 * 	Get information about class
	 *
     *	@param 	- TRUE = Check status; FALSE = Provide supported features
	 * 	@param 	- Object to store information
	 */
	public function Info(bool $mod, XML $xml): void {

		// anything to check?
		if ($mod)
			return;

    	parent::Info($mod, $xml);

    	if ($mod)
    		return;

    	$xml->addVar('Opt', '<a href="https://tools.ietf.org/html/rfc6154" target="_blank">RFC6154</a> '.
				      'IMAP LIST Extension for Special-Use Mailboxes');
		$xml->addVar('Stat', _('Implemented'));
	}

	/**
	 * 	Get specific mail box typ
	 *
	 * 	@parm 	- BOX_xxx - Type of mail box to search for (0 = All)
	 * 	@return - [ Internal <GUID> => External <GID> ]
	 */
	public function getBoxID(int $typ): array {

		if (!$this->_boxes) {
			$this->_boxes = [];
			$db = DB::getInstance();
			foreach ($db->Query(DataStore::MAIL, DataStore::GRPS) as $gid => $unused) {

				// load group record
				$xml = $db->Query(DataStore::MAIL, DataStore::RGID, $gid);

				// get external record id
				$rid = $xml->getVar('extID');

				// save information
				$this->_boxes[$xml->getVar(fldAttribute::TAG)] = [ $gid, $rid ];
			}

		}
		$unused; // disable Eclipse warning

		return !$typ ? $this->_boxes : (isset($this->_boxes[$typ]) ? $this->_boxes[$typ] : [ $typ => 0 ]);
	}

	/**
	 * 	Export document to client
	 *
	 * 	@param	- External document
	 * 	@param	- Internal document
	 * 	@param	- Optional MIME type [ Name, Version ]
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function export(XML &$ext, XML &$int, array $mime = NULL): bool {

		$mas = masHandler::getInstance();
		$opts = $mas->getOption(strval(DataStore::MAIL));
		if ($mas->callParm('BinVer') > 2.5 || !$opts['MIMESupport'])
			return parent::export($ext, $int, $mime);

		$db  = DB::getInstance();
		$val = $db->cnv2MIME($int);
		$len = strlen($val);

		// contains the raw MIME data of an email message that is retrieved from the server
		if ($opts['MIMETruncation'] != -1)
			$val = substr($val, 0, $opts['MIMETruncation']);
		$ext->addVar('MIMEData', $val, FALSE, $ext->setCP(XML::AS_MAIL));

		// either the size, in characters, of the string returned in the MIMEData element
		$ext->addVar('MIMESize', $len);

		// either the size, in characters, of the string returned in the MIMEData element contains truncated data
		if ($len != strlen($val))
			$ext->addVar('MIMETruncated', '1');

		return TRUE;
	}

}

?>