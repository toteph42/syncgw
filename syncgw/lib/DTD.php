<?php
declare(strict_types=1);

/*
 * 	DTD handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class DTD extends XML {

	// module version number
	const VER = 4;

	/**
	 * 	DTD position or FALSE
	 * 	@var bool|array
	 */
	private $_dtd;

	/**
	 *  Foreced base DTD
	 *  @var string
	 */
	private $_base = '';

    /**
     * 	Singleton instance of object
     * 	@var DTD
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): DTD {

		if (!self::$_obj) {
           	self::$_obj = new self();

			// set messages 10601-10700
			$log = Log::getInstance();
			$log->setMsg([
					10601 => _('Corrupted DTD "%s" - entry \'%s\' not found'),
					10602 => _('No DTD activated'),
					10603 => _('No DTD found'),
					10604 => _('DTD \'%s\' not found'),
			]);

			// load all available DTD
			$a = [];
			if (!($d = @opendir($p = Util::mkPath('source')))) {
				Error:Kill(10604, $p);
				return self::$_obj;
			}
			while (($f = @readdir($d)) !== FALSE) {
				if ($f == '.' || $f == '..' || substr($f, 0, 3) != 'cp_')
					continue;
				$a[] = Util::mkPath('source/'.$f);
			}
			@closedir($d);
			if (!count($a)) {
				ErrorHandler::Raise(10603);
	            return self::$_obj;
			}

 			// set root
			self::$_obj->loadXML('<CodePage/>');
			self::$_obj->getVar('CodePage');

			// load documents
			$wrk = new XML();
			foreach ($a as $file) {
				if (!$wrk->loadFile($file)) {
					self::$_obj = NULL;
					return self::$_obj;
				}
				$wrk->getVar('CodePage');
				self::$_obj->append($wrk);
			}
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

		$xml->addVar('Name', _('DTD handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status)
			return;

		if (parent::xpath('//Header/.')) {
			while (parent::getItem() !== NULL) {
				$ip = parent::savePos();
				$tag   = parent::getVar('Tag', FALSE);
				parent::restorePos($ip);
				$link  = parent::getVar('Link', FALSE);
				parent::restorePos($ip);
				$title = parent::getVar('Title', FALSE);
				parent::restorePos($ip);
				$ver   = parent::getVar('Ver', FALSE);
				$xml->addVar('Opt', '<a href="'.$link.'" target="_blank">'.$tag.'</a> '.
							  $title.' v'.$ver);
				$xml->addVar('Stat', _('Implemented'));
			}
		}
	}

	/**
	 * 	Activate DTD
	 *
	 * 	@param	- "PID" or "Name" or "URI"
	 * 	@return	- TRUE=Ok, FALSE=Not found
	 */
	public function actDTD($dtd): bool {

		// patch for ActiveSync
		if ($dtd == 1 && $this->_base)
			$dtd = $this->_base;
		Debug::Msg('Activate DTD "'.$dtd.'"');	//3

		if (!$this->xpath('//PID[text()="'.$dtd.'"]/.. | //Name[text()="'.$dtd.'"]/..') && !$this->xvalue('URI', strval($dtd))) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10604, $dtd);
			$this->_dtd = FALSE;
			return FALSE;
		}
		parent::getItem();
		$this->_dtd = parent::savePos();

		return TRUE;
	}

	/**
	 * 	Get variable
	 *
	 * 	@param	- Optional name of variable; NULL for all available
	 * 	@param 	- TRUE = Search whole document; FALSE = Search from current position
	 * 	@return	- Variable content; NULL = Variable not found
	 */
	public function getVar(?string $name = NULL, bool $top = FALSE): ?string {

		if (!$this->_dtd) {
			Debug::Warn('Get variable "'.$name.'" - no DTD set'); //3
			return NULL;
		}

		parent::restorePos($this->_dtd);
		$rc = parent::getVar($name, $top);
		Debug::Msg('Get variable "'.$name.'" = "'.$rc.'"'); //3

		return $rc;
	}

	/**
	 * 	Get tag definition
	 *
	 * 	@param	- WBXML code or string
	 * 	@return - WBXML code: Name of Tag; String: WBXML code; NULL = Error
	 */
	public function getTag(string $tag): ?string {

		Debug::Msg('Get tag definition "'.$tag.'"'); //3

		if (!$this->_dtd) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10602);
			return NULL;
		}

		// swicth to DTD table
		parent::restorePos($this->_dtd);
		if (is_numeric($tag)) {
			$v = sprintf('0x%02x', $tag);
			if (parent::xpath('../Defs/*[text()="'.$v.'"]/.', FALSE)) {
				if (parent::getItem())
					return parent::getName();
			}

			// create new entry for unknown tag
			parent::restorePos($this->_dtd);
			parent::xpath('../Defs/.', FALSE);
			parent::getItem();
			parent::addVar('Unknown-'.$v, $v);
			return self::getTag($tag);
		}

		if (self::xpath('../Defs/'.$tag.'/.', FALSE))
			return strval(hexdec(parent::getItem()));

		$log = Log::getInstance();
		$log->Msg(Log::ERR, 10601, parent::getVar('URI', FALSE), $tag);

		return NULL;
	}

}

?>