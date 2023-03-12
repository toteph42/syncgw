<?php
declare(strict_types=1);

/*
 * 	Encoding handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class Encoding extends XML {

	// module version number
	const VER = 11;

 	/**
	 * 	External character set encoding name
	 * 	@var string
	 */
	private $_ext = '';

	/**
	 * 	Multibyte flag (TRUE=Available)
	 * 	@var bool
	 */
	private $_mb;

    /**
     * 	Singleton instance of object
     * 	@var Encoding
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Encoding {

		if (!self::$_obj) {

        	self::$_obj = new self();

			// set messages 10301-10400
			$log = Log::getInstance();
			$log->setMsg([
					10301 => _('Unknown character set \'%s\''),
			]);

			// load encoding

			if (!(self::$_obj->loadFile(Util::mkPath('source/charset.xml'))))
				return NULL;

			// check for multi byte encoding functions
			self::$_obj->_mb = function_exists('mb_convert_encoding');

			// set internal encoding
			mb_internal_encoding('UTF-8');
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

		$xml->addVar('Name', _('Encoding handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status) {

			$cnf = Config::getInstance();
			$xml->addVar('Opt', _('Language used'));
			list($v,,) = explode(';', $cnf->getVar(Config::LANG));
			$xml->addVar('Stat', $v);

			$xml->addVar('Opt', _('Internal encoding'));
			$xml->addVar('Stat', 'UTF-8');
		} else {
			parent::xpath('//Charset/.');
			while (parent::getItem() !== NULL) {
				$pos = parent::savePos();
				$cs = parent::getVar('Name', FALSE);
				$xml->addVar('Opt', sprintf(_('Character set "%s"'), $cs));
				$xml->addVar('Stat', _('Implemented'));

				parent::xpath('../Alias/.', FALSE);
				while ($n = parent::getItem()) {
					$xml->addVar('Opt', sprintf(_('Alias "%s" of character set "%s"'), $n, $cs));
					$xml->addVar('Stat', _('Implemented'));
				}
				parent::restorePos($pos);
			}
		}
	}

	/**
	 * 	Set language
	 *
	 * 	@param	- Name of language (or NULL for Config::LANG)
	 */
	public function setLang(?string $lang = NULL): void {

		$cnf = Config::getInstance();

		// get default language setting
		if (!$lang)
			list(,$lang) = explode(';', $cnf->getVar(Config::LANG));

		Debug::Msg('Set language "'.$lang.'"'); //3

		// local language
		setlocale(LC_ALL, $lang.'.UTF8', $lang.'UTF-8', $lang.'.utf8', $lang.'utf-8', $lang);

		// set text domain
		bindtextdomain($lang, Util::mkPath('locales'));

		// select domain
		textdomain($lang);

		// set encoding
		bind_textdomain_codeset($lang, 'UTF-8');
	}

	/**
	 * 	Set external character set encoding
	 *
	 * 	@param	- Character set ID or name
	 * 	@return	- Name of character set or NULL for UTF-8
	 */
	public function setEncoding(string $cs): ?string {

		// sepcial ActiveSync hack - we assume character set 0 = UTF-8
		if (!$cs)
			$cs = 'utf-8';
		else
			$cs = strtolower($cs);

		if (!parent::xpath('//Charset[Id="'.$cs.'"]/.') && !parent::xpath('//Charset[Cp="'.$cs.'"]/.') &&
			!parent::xpath('//Charset[Name="'.$cs.'"]/.') && !parent::xpath('//Charset[Alias="'.$cs.'"]/.')) {
			Debug::Msg('Set external character set encoding "'.$cs.'" to ""'); //3
			return $this->_ext = NULL;
		}

		parent::getItem();
		$this->_ext = parent::getVar('Name', FALSE);
		parent::setParent();
		Debug::Msg('Set external character set encoding "'.$cs.'" to "'.$this->_ext.'"'); //3

		if (Debug::$Conf['Script'] == 'Encoding') //3
			Debug::Msg($this, 'Character set selected'); //3

		return $this->_ext;
	}

	/**
	 * 	Get code page for character set
	 *
	 *  see: https://docs.microsoft.com/en-us/windows/win32/intl/code-page-identifiers (05/31/2018)
	 *       https://www.iana.org/assignments/character-sets/character-sets.xml (2020-01-04)
	 *
	 * 	@param  - Character set name
	 *  @return - MicroSoft code page
	 */
	public function getMSCP(string $cs): string {

		$cs = strtolower($cs);

		if (!parent::xpath('//Charset[Cp="'.$cs.'"]/.') &&
			!parent::xvalue('Name', $cs, '/Charset/') &&
			!parent::xvalue('Alias', $cs, '/Charset/')) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10301, $cs);
			return self::getMSCP('utf-8');
		}

		parent::getItem();
		$cp = parent::getVar('Cp', FALSE);

		Debug::Msg('Get code page "'.$cp.'" for character set "'.$cs.'"'); //3

		return $cp;
	}

	/**
	 * 	Get external encoding
	 *
	 * 	@return	- Active character set name or NULL
	 */
	public function getEncoding(): ?string {
		return $this->_ext;
	}

	/**
	 * 	Decode data from external to internal encoding
	 *
	 * 	@param	- String to decode
	 * 	@return	- Converted string
	 */
	public function import(string $str): string {

		if (!$this->_ext || !$this->_mb)
			return $str;

		return $this->_ext == 'UTF-8' ? $str : mb_convert_encoding($str, 'UTF-8', $this->_ext);
	}

	/**
	 * 	Encode data from internal to external encoding
	 *
	 * 	@param	- String to encode
	 * 	@return	- Converted string
	 */
	public function export(string $str): string {

		if (!$this->_ext || !$this->_mb)
			return $str;

		return $this->_ext == 'UTF-8' ? $str : mb_convert_encoding($str, $this->_ext, 'UTF-8');
	}

}

?>