<?php
declare(strict_types=1);

/*
 * 	WBXML handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class WBXML {

	// module version number
	const VER 			= 11;

	// constant definition
	const SWITCH_PAGE 	= 0x00;
	const END			= 0x01;
	const ENTITY		= 0x02;
	const STR_I			= 0x03;
	const LITERAL		= 0x04;

	const EXT_I_0		= 0x40;
	const EXT_I_1		= 0x41;
	const EXT_I_2		= 0x42;
	const PI			= 0x43;
	const LITERAL_C		= 0x44;

	const EXT_T_0		= 0x80;
	const EXT_T_1		= 0x81;
	const EXT_T_2		= 0x82;
	const STR_T			= 0x83;
	const LITERAL_A		= 0x84;

	const EXT_0			= 0xC0;
	const EXT_1			= 0xC1;
	const EXT_2			= 0xC2;
	const OPAQUE		= 0xC3;
	const LITERAL_AC	= 0xC4;

	const CP 			= [
		// name						code page
		// [MS-ASWBXML]
		XML::CP[XML::AS_AIR]		=> 0,
		XML::CP[XML::AS_CONTACT] 	=> 1,
		XML::CP[XML::AS_MAIL]		=> 2,
		XML::CP[XML::AS_CALENDAR]	=> 4,
		XML::CP[XML::AS_MOVE]		=> 5,
		XML::CP[XML::AS_ESTIMATE]	=> 6,
		XML::CP[XML::AS_FOLDER]		=> 7,
		XML::CP[XML::AS_MRESPONSE]	=> 8,
		XML::CP[XML::AS_TASK]		=> 9,
		XML::CP[XML::AS_RESOLVE]	=> 10,
		XML::CP[XML::AS_CERT]		=> 11,
		XML::CP[XML::AS_CONTACT2]	=> 12,
		XML::CP[XML::AS_PING]		=> 13,
		XML::CP[XML::AS_PROVISION]	=> 14,
		XML::CP[XML::AS_SEARCH]		=> 15,
		XML::CP[XML::AS_GAL]		=> 16,
		XML::CP[XML::AS_BASE]		=> 17,
		XML::CP[XML::AS_SETTING]	=> 18,
		XML::CP[XML::AS_DocLib]		=> 19,
		XML::CP[XML::AS_ITEM]		=> 20,
		XML::CP[XML::AS_COMPOSE]	=> 21,
		XML::CP[XML::AS_MAIL2]		=> 22,
		XML::CP[XML::AS_NOTE]		=> 23,
		XML::CP[XML::AS_RIGTHM]		=> 24,
	    XML::CP[XML::AS_FIND]       => 25,
	];

 	/**
	 * 	Stack
	 * 	@var array
	 */
	private $_stack 	= [];

	/**
	 * 	Raw data buffer
	 * 	@var string
	 */
	private $_raw		= '';

	/**
	 * 	String position
	 * 	@var int
	 */
	private $_pos		= 0;

	/**
	 * 	Length of buffer
	 * 	@var int
	 */
	private $_len		= 0;

	/**
	 * 	String table
	 * 	@var array
	 */
	private $_strtab	= [];

	/**
	 * 	Attributes
	 * 	@var array
	 */
	private $_attr		= [];

	/**
	 * 	Encoding object pointer
	 * 	@var Encoding
	 */
	private $_enc;

	/**
	 * 	DTD object pointer
	 * 	@var DTD
	 */
	private $_dtd;

    /**
     * 	Singleton instance of object
     * 	@var WBXML
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): WBXML {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 10501-10600
			$log = Log::getInstance();
			$log->setMsg([
			        10501 => _('Invalid position %d in string table'),
					10502 => _('Unsupported function \'PI\''),
					10503 => _('Unknown \'Public Identifier\' %s'),
					10504 => _('Character set \'%s\' not supported'),
					10505 => _('Unsupported WBXML data version \'%s\' received'),
			]);

			self::$_obj->_enc = Encoding::getInstance();
			self::$_obj->_dtd = DTD::getInstance();
		}

		return self::$_obj;
	}

   /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

    	$xml->addVar('Name', _('WBXML handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status)
			return;

		$xml->addVar('Opt', '<a href="https://learn.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-aswbxml" target="_blank">[MS-ASWBXML]</a> '.
				      'Exchange ActiveSync: WAP Binary XML (WBXML) Algorithm v23.0');
		$xml->addVar('Stat', _('Implemented'));
		$xml->addVar('Opt', '<a href="http://www.openmobilealliance.org/tech/affiliates/wap/wap-192-wbxml-20010725-a.pdf" target="_blank">[WBXML]</a> '.
				      'Binary XML Content Format Specification v1.3');
		$xml->addVar('Stat', _('Implemented'));
	}

	/**
	 * 	Decode WBXML code
	 *
	 * 	see WAP-192-WBXML-20010725-a.pdf
	 *
	 * 	@param	- WBXML data to decode
	 * 	@param 	- Recursive flag
	 * 	@return	- Object or NULL
	 */
	public function Decode(string $data, bool $first = TRUE): ?XML {

		if (!($this->_len = strlen($this->_raw = $data)))
			return NULL;

		// create base object
		$xml = new XML();

		// reset position in buffer
		$this->_pos    = 0;
		// clear string table
		$this->_strtab = [];
		// attribute key
		$akey          = '';
		// clear attribute list
		$this->_attr   = [];
		// clear stack
		if ($first)
			$this->_stack = [];

		// version number (5.4)
		$iv  = $this->_getByte();
		$maj = ($iv & 0xf0) + 1;
		$min = ($iv & 0x0f);
		$ver = $maj.'.'.$min;
		// we do ONLY accept major version 1
		// this is required, because there is a PHP error, where RAW_POST_DATA is not properly build
		if ($maj > 1) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10505, $ver);
			return NULL;
		}
		Debug::Msg('-'.str_pad('', 10, ' -').' New WBXML document'); //3
		Debug::Msg('WBXML  version '.$ver. ' (0x'.sprintf('%02X', $iv).')'); //3

		// document public identifier (5.5)
		$pid = self::_getInt(2);
		if (!$pid) {
			// offset is in string table
			$pid = self::_getByte();
			// examine later
			$str_pid = TRUE;
			Debug::Msg('Public identifier will be examined later'); //3
		} else {
			// special patch for ActiveSync
			if ($pid == 1)
				$pid = 8192;
			if (!$this->_dtd->actDTD($pid))
				return FALSE;
			$str_pid = FALSE;
			Debug::Msg('Public identifier is '.$pid.' '.sprintf('0x%04X', $pid)); //3
		}

		// charset (5.6)
		$ics = self::_getInt(2);
		if (!($cs = $this->_enc->setEncoding(strval($ics)))) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10504, $ics);
		}
		$this->_attr['CHARSET'] = $cs;
		Debug::Msg('Character set is "'.$cs.'" '.sprintf('0x%04X', $ics)); //3

		// string table (5.7)
		$str = self::_getPString(FALSE);
		if (strlen($str)) {
			$a = explode("\0", $str);
			$pos = 0;
			// convert string table to array
			foreach ($a as $v) {
				$this->_strtab[$pos] = $v;
				$pos += strlen($v) + 1;
			}
		}
		if (count($this->_strtab)) { //3
			Debug::Msg('String table with length '.strlen($str).' '.sprintf('0x%04X', strlen($str)).' loaded'); //3
			Debug::Msg($str, 'Raw string table data (first 100 bytes)', 0, 100); //3
		} else //3
			Debug::Msg('String table is EMPTY'); //3

		// PID from string table?
		if ($str_pid) {
			$nam = self::_getStrTab(intval($pid));
			if (!$this->_dtd->actDTD($nam)) {
				if ($pid != 0x0600) {
					$log = Log::getInstance();
					$log->Msg(Log::WARN, 10503, $nam);
				}
				$xml = new XML();
				$xml->addVar('Unknown', base64_encode($data));
				return $xml;
			}

			$pid = $this->_dtd->getVar('PID', FALSE);
			Debug::Msg('Public identifier "'.$nam.'" from string table '.$pid.' '.sprintf('0x%04X', $pid)); //3
		}

		// load DTD URI
		$uri = $this->_dtd->getVar('URI', FALSE);
		// add xml-ns attribute
		$this->_attr['xml-ns'] = $uri;

		// set document type
		if (!count($this->_stack)) {
			Debug::Msg('Creating DOCTYPE element'); //3
			$xml->setDocType($uri, $this->_dtd->getVar('Name', FALSE), $this->_dtd->getVar('Link', FALSE));
		} else
			$xml = new XML();

		// process tokens (5.8)
		$as = '';

		// walk through buffer
		while ($this->_pos < $this->_len) {

			// get token to process
			$token = self::_getByte();
			$op = ''; //3
			switch ($token) {
			case self::STR_I:
				$op = 'STRI_I'; //3
			case self::EXT_I_0:
				if (!$op) $op = 'EXT_I_0'; //3
			case self::EXT_I_1:
				if (!$op) $op = 'EXT_I_1'; //3
			case self::EXT_I_2:
				if (!$op) $op = 'EXT_I_2'; //3
				$v = self::_getCString();
				if (strlen($v)) {
					$v = $this->_enc->import($v);
					$xml->setVal($v);
					Debug::Msg('Str    "'.$v.'" ('.$op.')'); //3
				}
				break;

			case self::STR_T:
				$op = 'STR_T'; //3
			case self::EXT_T_0:
				if (!$op) $op = 'EXT_T_0'; //3
			case self::EXT_T_1:
				if (!$op) $op = 'EXT_T_1'; //3
			case self::EXT_T_2:
				if (!$op) $op = 'EXT_T_2'; //3
				$pos = self::_getInt();
				$v = self::_getStrTab($pos);
				$v = $this->_enc->import($v);
				$xml->setVal($v);
				Debug::Msg('StrTab ('.strlen($v).') "'.str_replace("\n", '', $v).'" ('.$op.') at '.$pos); //3
                break;

			case self::LITERAL:
				if (!$op) $op = 'LITERAL'; //3
				$pos = self::_getInt();
				$v = self::_getStrTab($pos);
				$v = $this->_enc->import($v);
				$xml->setAttr([ $v => '' ]);
				Debug::Msg('Attr   "'.$v.'" ('.$op.') at '.sprintf('0x%04X', $pos)); //3
				break;

			case self::LITERAL_A:
				if (!$op) $op = 'LITERAL_A'; //3
				$pos = self::_getInt();
				$akey = self::_getStrTab($pos);
				$akey = $this->_enc->import($akey);
				Debug::Msg('Attr   "'.$akey.'" ('.$op.') at '.sprintf('0x%04X', $pos)); //3
				break;

			case self::LITERAL_C:
				if (!$op) $op = 'LITERAL_C'; //3
			    $pos = self::_getInt();
				$v = self::_getStrTab($pos);
				$v = $this->_enc->import($v);
				Debug::Msg('Attr   "'.$akey.'='.$v.'" ('.$op.') at '.sprintf('0x%04X', $pos)); //3
		        $xml->setAttr([ $akey => $v ]);
   				$akey = '';
				break;

			case self::LITERAL_AC:
				if (!$op) $op = 'LIT_AC'; //3
			    $p1   = self::_getInt();
				$akey = self::_getStrTab($p1);
			    $p2   = self::_getInt();
				$v    = self::_getStrTab($p2);
   				Debug::Msg('Attr   "'.$akey.'='.$v.'" ('.$op.') at '.sprintf('0x%04X', $p1).' and '.sprintf('0x%04X', $p2)); //3
		        $xml->setAttr([ $akey => $v ]);
   				$akey = '';
				break;

			case self::EXT_0:
				$op = 'EXT_0'; //3
			case self::EXT_1:
				if (!$op) $op = 'EXT_1'; //3
			case self::EXT_2:
				if (!$op) $op = 'EXT_2'; //3
				$v = self::_getByte();
				$xml->setVal($v = $this->_enc->import($v));
				Debug::Msg('Byte    "'.$v.'" ('.$op.')'); //3
				break;

			case self::ENTITY:
				$v = '&#'.self::_getHex().';';
				$v = $this->_enc->import($v);
				$xml->setVal($v);
				Debug::Msg('Hex.    "'.$v.'" (ENTITY)'); //3
				break;

			case self::PI:
				$log = Log::getInstance();
				$log->Msg(Log::WARN, 10502);
				break;

			case self::OPAQUE:
				$v = self::_getPString(FALSE);
				// opaque data inside a <data> element may be a nested document (for example devinf data).
				// find out by checking the first byte of the data: if it's less 10 (0x0a) we expect it to be the version number of a wbxml
				// document and thus start a new wbxml decoder instance on it.
				if (strlen($v) && ord(substr($v, 0, 1)) < 10) {
					$len = strlen($v);
		  			Debug::Msg('WBXML  Start - length '.$len.' '.sprintf('(0x%04X) - ending at 0x%04X Tag 0x%02X', //3
        			$len, $this->_pos, ord(substr($v, 0, 1)))); //3
				    Debug::Msg($v, '', 0, 20); //3
				    $v = substr($this->_raw, $this->_pos - $len, $len);
				    self::_save();
				    if ($seg = $this->Decode($v, FALSE)) {
				 	    if ($v = $seg->getVar('Unknown')) {
				 		    $xml->setVal($v);
				 		    $xml->setAttr([ 'TYPE' => 'BASE64' ]);
				 	    } else
				 		    $xml->append($seg);
				    }
				    self::_restore();
				    Debug::Msg('WBXML  End Len '.sprintf('(0x%04X) ended at 0x%04X', $len, $this->_pos)); //3
				} else {
					if (strlen($v)) {
						if (Util::isBinary($v)) {
							Debug::Msg('Data   Converting binary data to BASE64 (length '.strlen($v).')'); //3
							$v = base64_encode($v);
							$xml->setVal($v);
							$xml->setAttr([ 'TYPE' => 'BASE64'] );
						} else {
							$v = $this->_enc->import($v);
							$xml->setVal($v);
						}
						Debug::Msg('Opaque ('.strlen($v).') "'.$v.'"'); //3
					}
				}
				break;

			case self::END:
			    if ($as) {
			        $p = $this->_pos;
			        if (count($this->_attr)) {
				        foreach ($this->_attr as $k => $v)
				            $xml->setAttr([ $k => $v ]);
				        // not required - done by _restore(): $this->_attr = [];
                    }
					self::_restore();
					$this->_pos = $p;
			    }
				if ($xml->getType() != XML_DOCUMENT_NODE) {
					$xml->setParent();
					Debug::Msg('BackTo <'.$xml->getName().'>'); //3
				}
				break;

			case self::SWITCH_PAGE:
				$cp = self::_getByte();
				$x  = 'SetCP  '.$cp.' (from "'.$this->_dtd->getVar('URI', FALSE).'" to "'; //3
				$dn = '';
				switch ($pid) {
				// ActiveSync - please note PID 8192 is internal and officially a fake :-)
				// http://msdn.microsoft.com/en-us/library/ee219143%28v=exchg.80%29.aspx
				// 00 AirSync				8192
				// 01 Contacts 				8193
				// 02 Email					8194
				// 03 AirNotify 			no longer in use.
				// 04 Calendar				8196
				// 05 Move					8197
				// 06 GetItemEstimate		8198
				// 07 FolderHierarchy		8199
				// 08 MeetingResponse		8200
				// 09 Tasks					8201
				// 10 ResolveRecipients		8202
				// 11 ValidateCert			8203
				// 12 Contacts2				8204
				// 13 Ping					8205
				// 14 Provision				8206
				// 15 Search				8207
				// 16 GAL					8208
				// 17 AirSyncBase			8209
				// 18 Settings				8210
				// 19 DocumentLibrary		8211
				// 20 ItemOperations		8212
				// 21 ComposeMail			8213
				// 22 Email2				8214
				// 23 Notes					8215
				// 24 RightsManagement		8216
				// 25 Find                  8217
				case 8192:
					$dn = 8192 + $cp;
					$this->_dtd->actDTD($dn);
					break;

				default:
					if ($dn)
						$this->_dtd->actDTD($dn);
					break;
				}
				if ($dn) {
					$uri = $this->_dtd->getVar('URI', FALSE);
					// special ActiveSync hack
					if ($pid != 8192)
						$this->_attr['xml-ns'] = substr($uri, 0, -3);
					else
						$this->_attr['xml-ns'] = $uri;
					Debug::Msg($x.$uri.'")'); //3
				}
				break;

			default:
				// attribute state
				if ($as = $token & 0x80)
					self::_save();
				// tag state
				$cont = $token & 0x40;
				// get token
				$name = $this->_dtd->getTag(strval($token & 0x3f));
				if ($name === FALSE) {
					$http = HTTP::getInstance();
					$http->send(400);
					return NULL;
				}
				Debug::Msg('Token  <'.$name.'> '.sprintf('0x%02X', $token).' ('.sprintf('0x%02X', $token & 0x3f).') at '. //3
					 sprintf('0x%04X', $this->_pos-1).' Attribute='.($as ? 'TRUE' : 'FALSE').', Content='.($cont ? 'TRUE' : 'FALSE')); //3
				// add new element
				$xml->addVar($name);
				// attributes available
				foreach ($this->_attr as $k => $v)
					$xml->setAttr([ $k => $v ]);
				$this->_attr = [];
				// move pointer?
                if (!$cont)
					$xml->setParent();
				break;
			}
		}

		Debug::Msg('End of buffer'); //3
		Debug::Msg('-'.str_repeat(' -', 60)); //3

		// free buffer
		$this->_raw = '';

		return $xml;
	}

	/**
	 * 	Encode XML object from current position
	 *
	 * 	@param	- Input object
	 * 	@param	- TRUE=Use string table? (default); FALSE=Don't use (ActiveSync)
	 * 	@return	- WBXML data or NULL
	 */
	public function Encode(XML $xml, bool $tab = TRUE): ?string {

		// be sure only to encode XML ojects
		if (!is_object($xml))
			return NULL;

		Debug::Msg('Store strings as "'.($tab ? 'StrTab' : 'CString').'"'); //3

		$this->_raw = '';
		// we use _pos as as an list of nested elements
		$this->_pos = [];
		// we use _len as DTD stack
		$this->_len = [];
		// clean stack
		$this->_stack = [];

	    // empty document?
	    if ($xml->getType() == XML_DOCUMENT_NODE && !$xml->hasChild())
	        return NULL;

		while ($xml->getType() != XML_ELEMENT_NODE)
			$xml->setNext();

		// encode nested document
		self::_wbxmlEnc($xml, $tab);

		// set end of string flag
		Debug::Msg('End     (END)'); //3

		return $this->_raw;
	}

	/**
	 * 	Save object instance
	 */
	private function _save(): void {
		$this->_stack[] = [
				0 => $this->_raw,
				1 => $this->_pos,
				2 => $this->_len,
				3 => $this->_strtab,
				4 => $this->_dtd->getVar('PID', FALSE),
				5 => $this->_attr,
		];
	}

	/**
	 * 	Restore object instance
	 */
	private function _restore(): void {
		if (count($this->_stack)) {
			$a = array_pop($this->_stack);
			$this->_raw    = $a[0];
			$this->_pos    = $a[1];
			$this->_len    = $a[2];
			$this->_strtab = $a[3];
			$dn    		   = $a[4];
			$this->_attr   = $a[5];
			if ($dn)
				$this->_dtd->actDTD($dn);
		}
	}

	/**
	 * 	Get one byte
	 *
	 * 	@return	- Extracted byte from input buffer
	 */
	private function _getByte(): string {
		return strval($this->_pos < $this->_len ? ord($this->_raw[$this->_pos++]) : 0);
	}

	/**
	 * 	Get hex. value
	 *
	 * 	@return	- Extracted hex. integer from input buffer
	 */
	private function _getHex(): int {
		return dechex(self::_getInt());
	}

	/**
	 * 	Get integer (Network byte order is "big-endian")
	 *
	 * 	@param	- Max. length of integer
	 * 	@return	- Extracted integer from input buffer
	 */
	private function _getInt(int $max = 999): int {
		$val = 0;
		do {
			$t = ord($this->_raw[$this->_pos++]);
			$val <<= 7;
			$val += ($t & 0x7f);
			if (!--$max)
				break;
		} while ($t & 0x80 && $this->_pos < $this->_len);
		return $val;
	}

	/**
	 * 	Get PASCAL string
	 *
	 * 	@param	- TRUE = Decode string to UTF-8; FALSE = Do not decode
	 * 	@return	- Extracted string from input buffer
	 */
	private function _getPString(bool $cnv): string {
		$l = self::_getInt();
		if ($this->_pos + $l >= $this->_len)
			return '';
		$val = substr($this->_raw, $this->_pos, $l);
		if ($cnv)
			$val = $this->_enc->import($val);
		$this->_pos += $l;
		return $val;
	}

	/**
	 * 	Get C string
	 *
	 * 	@return	- Extracted string from input buffer
	 */
	private function _getCString(): string {
		$val = '';
		$c = $this->_raw[$this->_pos++];
		while (ord($c) && $this->_pos < $this->_len) {
			$val .= $c;
			$c    = $this->_raw[$this->_pos++];
		}
		$val = $this->_enc->import($val);
		return $val;
	}

	/**
	 * 	Get string from string table
	 *
	 *  @param	- Offset in table
	 *  @return	- Extracted string
	 */
	private function _getStrTab(int $pos): string {
		if (isset($this->_strtab[$pos])) {
			$val = $this->_enc->import($this->_strtab[$pos]);
			return $val;
		}
		$log = Log::getInstance();
		$log->Msg(Log::WARN, 10501, $pos);
		return '';
	}

	/**
	 * 	Save string into string table
	 *
	 *  @param	- String to save
	 *  @return	- Offset in string table
	 */
	private function _putStrTab(string $val): int {
		$val = $this->_enc->export($val);
		$pos = 0;
		foreach ($this->_strtab as $pos => $data) {
			if (!strcmp($val, $data))
				return $pos;
			$pos += strlen($data) + 1;
		}
		$this->_strtab[$pos] = $val;
		return $pos;
	}

	/**
	 * 	Convert and store data
	 *
	 * 	@param	- Node to save
	 * 	@param	- TRUE = Use string table (default); FALSE = Don't use (ActiveSync)
	 */
	private function _savestr(XML &$xml, bool $stab): void {
		if (!strlen($v = $xml->getVal()))
			return;

		// convert back CR/NL
		if (strpos($v,"\r\n") === FALSE)
		    $v = str_replace("\n", "\r\n", $v);

		// define data handling mode
		if ($stab) {
			$p = self::_putStrTab($v);
			$this->_raw .= chr(self::STR_T).self::_cnvInt($p).chr(self::END);
			Debug::Msg('StrTab  ('.strlen($v).') "'.str_replace("\n", '', $v).'" at '.$p); //3
		} else {
		    // sepcial hack for ActiveSync
		    // WBXML byte array token
            // byte array 		type MUST be encoded and transmitted as WBXML opaque data
		    if (($k = $xml->getName()) == 'Mime' || $k == 'Content') {
		        $this->_raw .= chr(self::OPAQUE).self::_cnvInt(strlen($v)).$v.chr(self::END);
	       		Debug::Msg('OPAQUE  ('.strlen($v).') "'.str_replace( [ "\n", "\r" ], [ '.', '.' ], $v).'"');	//3
		    } else {
    			$this->_raw .= self::_cnvCString($v).chr(self::END);
	       		Debug::Msg('STR_I   ('.strlen($v).') "'.str_replace( [ "\n", "\r" ], [ '.', '.' ], $v).'"');	//3
		    }
		}
	}

	/**
	 * 	Encode XML node
	 *
	 * 	see WAP-192-WBXML-20010725-a.pdf
	 *
	 * 	@param	- XML node object
	 * 	@param	- TRUE = Use string table (default); FALSE = Don't use
	 */
	private function _wbxmlEnc(XML &$node, bool $stab): void {

		// first call (required for setting appropriate encoding)?
		$first = !$this->_raw;

		// special CDATA handling
		if ($node->getType() == XML_CDATA_SECTION_NODE) {
			self::_savestr($node, $stab);
			return;
		}

		// skip document root
		if ($node->getType() == XML_DOCUMENT_NODE)
			$node->setNext();

		// save position in document
		$save = $node->savePos();

		// get attributes
		$this->_attr = $node->getAttr();

		// attribute "charset" indicates a new wbxml document for us
		if ($first || isset($this->_attr['CHARSET'])) {

    		Debug::Msg(str_repeat('-', 50).' Start encoding new WBXML document'); //3

		    // save character set
			if (isset($this->_attr['CHARSET'])) {
				$cs = $this->_attr['CHARSET'];
				unset($this->_attr['CHARSET']);
			} else
				$cs = 'UTF-8';

			// save encoding status
			self::_save();

			// clear output buffer
			$this->_raw    = '';
			$this->_strtab = [];

			// set base DTD
			if (isset($this->_attr['xml-ns'])) {
				$xmlns = $this->_attr['xml-ns'];
				// special hack for ActiveSync
				if (substr($xmlns, 0, 10) == 'activesync')
					// set to base name space
					$xmlns = 'activesync:AirSync';
				else
					unset($this->_attr['xml-ns']);
			} elseif ($node->getType() == XML_DOCUMENT_TYPE_NODE) {
				$xmlns = $node->getName();
			    // skip to next node in tree
			    $node->setNext();
				$this->_attr = $node->getAttr();
			}

			$this->_dtd->actDTD($xmlns);
			Debug::Msg('Setting base DTD "'.$xmlns.'" (code page "'.$this->_dtd->getVar('CodePage', FALSE).'")'); //3

			// set fix WBXML version = 1.2 (5.4)
			$hdr = 3;
			Debug::Msg('Setting WBXML version '.sprintf('%d', $hdr).' ('.sprintf('0x%02X', $hdr).')'); //3
			$hdr = chr($hdr);

			$hdr .= self::_cnvInt(1);
			Debug::Msg('Public identifier "UNKNOWN"'); //3

			// character set (5.6)
			$this->_enc->setEncoding($cs);
			$id = intval($this->_enc->getVar('Id', FALSE));
			$hdr .= self::_cnvInt($id);
			Debug::Msg('Character set "'.$cs.'" (0x'.sprintf('%02X', $id).')'); //3

			// string table (5.7)
			Debug::Msg('String table will be build later'); //3
			Debug::Msg($hdr, 'Header so far', 0, 10240); //3
		} else
			$hdr = '';

		// check code page switch
		if (isset($this->_attr['xml-ns'])) {
			$iscp = strval($this->_dtd->getVar('CodePage'));
			$isxmls = strval($this->_dtd->getVar('URI')); //3
			$xmlns = $this->_attr['xml-ns'];
   		  	unset($this->_attr['xml-ns']);
			// check for version
			$this->_dtd->actDTD($xmlns);
			// embed <Devinf> object
			if (stripos($xmlns, 'devinf') !== FALSE && $node->getName() == 'DevInf') {
				$p = $node->savePos();
	            self::_save();
				$wrk = $this->Encode($node, $stab);
	            self::_restore();
				$node->restorePos($p);
				$this->_raw .= $wrk;
				return;
			} elseif (($cp = $this->_dtd->getVar('CodePage')) != $iscp) {
				Debug::Msg('CodePag Changing to "'.$cp.'" (from "'.$isxmls.'" to "'.$xmlns.'")'); //3
				$this->_raw .= chr(self::SWITCH_PAGE).self::_cnvInt($cp);
			}
		}

		// process tokens (5.8)
		if (($token = $this->_dtd->getTag($node->getName())) === NULL) {
			Debug::Save('WBXML-Encode-'.$node->getName().'%d.xml', $node); //3
			$http = HTTP::getInstance();
			$http->send(400);
			return;
		}
		if ($cont = $node->hasChild(0))
		    $token |= 0x40;
	    $as = count($this->_attr) ? TRUE : FALSE;
		if ($as)
		    $token |= 0x80;
		$this->_raw .= chr(intval($token));
		Debug::Msg('Token   '.sprintf('0x%02X', $token).' <'.$node->getName().'> '. //3
			  	  'Attribute='.($as ? 'TRUE' : 'FALSE').', Content='.($cont ? 'TRUE' : 'FALSE')); //3
		$cont; // disable Eclipse warning

		// process attributes
		if (count($this->_attr)) {
			foreach ($this->_attr as $k => $v) {
			    // skip what we already processed
				if ($k == 'CHARSET')
					continue;
				$p1 = self::_putStrTab($k);
				$this->_raw .= chr(self::LITERAL_A).self::_cnvInt($p1);
				$p2 = self::_putStrTab($v);
				$this->_raw .= chr(self::LITERAL_C).self::_cnvInt($p2);
				unset($this->_attr[$k]);
				Debug::Msg('Attr    "'.$k.'" at '.$p1.' ('.sprintf('0x%X',$p1).') and value "'.$v.'" at '.$p2.	//3
				          ' ('.sprintf('0x%X', $p2).') in StringTab'); //3
			}
		}

		// save value
		if ($node->hasChild(XML_TEXT_NODE) !== FALSE || $node->hasChild(XML_CDATA_SECTION_NODE)) {
			self::_savestr($node, $stab);
			$this->_pos[] = FALSE;
		} else
			// add <END> tag
			if ($node->hasChild(XML_ELEMENT_NODE) !== FALSE)
				$this->_pos[] = $node->getName();
		else
			$this->_pos[] = FALSE;

		// process child nodes
		if ($node->getChild($node->getName(), FALSE)) {
			while($node->getItem() !== NULL) {
			    if (($t = $node->getType()) == XML_ELEMENT_NODE || $t == XML_CDATA_SECTION_NODE)
					self::_wbxmlEnc($node, $stab);
			}
			// go back to parent
			$node->setParent();
		}

		// did node contain any data?
		if ($k = array_pop($this->_pos)) {
			$this->_raw .= chr(self::END);
			Debug::Msg('End     </'.$k.'> (END)'); //3
		}

		// check for embedded WBXML
		if ($hdr) {
			Debug::Msg('-'.str_pad('', 10, ' -').' Embedded WBXML document found'); //3
			if (count($this->_strtab)) {
				$tab = implode("\0", $this->_strtab)."\0";
				$sl = strlen($tab);
			} else {
				$sl  = 0;
				$tab = '';
			}
			Debug::Msg('String table with length '.$sl.' '.sprintf('0x%04X', $sl).' build'); //3
			if ($sl) //3
				Debug::Msg($tab, '', 0, 100); //3
			$raw = $this->_raw;
			self::_restore();
			// check level
			if (count($this->_stack)) {
				// create header for nested document
				$raw = $hdr.self::_cnvInt($sl).$tab.$raw;
				// embedd document as opaque data
				$raw = chr(self::OPAQUE).self::_cnvInt(strlen($raw)).$raw.chr(self::END);
				Debug::Msg('-'.str_pad('', 10, ' -').' Embedded WBXML document with '.strlen($raw).' '. //3
						sprintf('0x%04X', strlen($raw)).' bytes created'); //3
				$this->_raw .= $raw;
			} else
				// build standard header
				$this->_raw = $hdr.self::_cnvInt($sl).$tab.$this->_raw.$raw;

			Debug::Msg(str_pad('', 10, ' -').' End of document'); //3

			// disable END tag
            $this->_pos[] = FALSE;
		}

		$node->restorePos($save);
	}

	/**
	 * 	Check bit status
	 *
	 * 	@param	- Bit number
	 * 	@param	- Integer value
	 * 	@return	- 1 = Bit is on; 0 = Bit is off
	 */
	private function _chkBit(int $bit, int $val): int {
		switch ($bit) {
		case 0:
			return $val & 0x7f;
		case 1:
			return ($val >> 7) & 0x7f;
		case 2:
			return ($val >> 14) & 0x7f;
		case 3:
			return ($val >> 21) & 0x7f;
		case 4:
			return ($val >> 28) & 0x7f;
		}
		return 0;
	}

	/**
	 * 	Convert to integer
	 *
	 * 	@param	- Number value
	 * 	@return	- Integer value
	 */
	private function _cnvInt($num): string {

		$val = '';
		$num = intval($num);
		if ($num > 268435455)
			$val .= chr(0x80 | self::_chkBit(4, $num));
		if ($num > 2097151)
			$val .= chr(0x80 | self::_chkBit(3, $num));
		if ($num > 16383)
			$val .= chr(0x80 | self::_chkBit(2, $num));
		if ($num > 127)
			$val .= chr(0x80 | self::_chkBit(1, $num));

		$val .= chr(self::_chkBit(0, $num));

		return $val;
	}

	/**
	 * 	Convert to C string
	 *
	 * 	@param	- String
	 * 	@return	- Converted string value
	 */
	private function _cnvCString(string $str): string {
		$val = $this->_enc->export($str);
		return chr(self::STR_I).$val.chr(0);
	}

	/**
	 * 	Convert to PASCAL string
	 *
	 * 	@param	- String
	 * 	@return	- Converted string value
	 */
	// private function _cnvPString(string $str): string {
	//  $val = $this->_enc->export($str);
	//  return self::_cnvInt(strlen($val)).$val;
	// }

}

?>