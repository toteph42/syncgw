<?php
declare(strict_types=1);

/*
 *  MessageClass field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldMessageClass extends fldHandler {

	// module version number
	const VER = 3;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'MessageClass';

	const ASN_MAP 			= [
			'IPM.StickyNote',
	];

	const ASM_MAP 			= [
			'IPM.Note',							// Normal e-mail message.
			'IPM.Note.SMIME',					// The message is encrypted and can also be signed.
			'IPM.Note.SMIME.MultipartSigned',	// The message is clear signed.
			'IPM.Note.Receipt.SMIME',			// The message is a secure read receipt.
			'IPM.InfoPathForm',					// An InfoPath form, as specified by [MS-IPFFX].
			'IPM.Schedule.Meeting',				// Meeting request.
			'IPM.Notification.Meeting',			// Meeting notification.
			'IPM.Post',							// Post.
			'IPM.Octel.Voice',					// Octel voice message.
			'IPM.Voicenotes',					// Electronic voice notes.
			'IPM.Sharing',						// Shared message
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldMessageClass
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMessageClass {

		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			unset(parent::$Deleted[self::TAG]);
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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), self::TAG));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Import field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or external document
	 *  @param  - Internal path
	 * 	@param 	- Internal document
	 *  @return - TRUE = Ok; FALSE = Skipped
	 */
	public function import(string $typ, float $ver, string $xpath, $ext, string $ipath, XML &$int): bool {

		$rc    = FALSE;
		$map   = NULL;
		$ipath .= self::TAG;

		switch ($typ) {
		case 'application/activesync.note+xml':
			$map = self::ASN_MAP;

		case 'application/activesync.mail+xml':
			if (!$map)
				$map = self::ASM_MAP;

			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);
			while (($val = $ext->getItem()) !== NULL) {
				foreach ($map as $w) {
					if (strstr($val, $w) !== FALSE) {
						$int->addVar(self::TAG, $val);
						$rc = TRUE;
						break;
					}
				}
				if (!$rc) //3
					Debug::Msg('['.$xpath.'] - Invalid value "'.$val.'"'); //3
			}
			break;

		default:
			$rc = TRUE;
			break;
		}

		return $rc;
	}

	/**
	 * 	Export field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
 	 *	@param  - Internal path
	 * 	@param 	- Internal document
	 *  @param  - External path
	 *  @param  - External document
	 *  @return - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or FALSE=Not found
	 */
	public function export(string $typ, float $ver, string $ipath, XML &$int, string $xpath, ?XML $ext = NULL) {

		$rc   = FALSE;
		$cp   = NULL;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'application/activesync.note+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 14.0)
				break;
			$cp = XML::AS_NOTE;

		case 'application/activesync.mail+xml':
			if (!$cp)
				$cp = XML::AS_MAIL;

			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, $val, FALSE, $ext->setCP($cp));
				$rc	= TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>