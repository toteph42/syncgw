<?php
declare(strict_types=1);

/*
 * 	ActiveSync type definitions
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

class masFolderType {

	// module version number
	const VER 			= 7;

	// [MS-ASCMD] 2.2.3.186.3 Type (FolderSync)
	const F_GENERIC		= '1';
	const F_INBOX		= '2';
	const F_DRAFT		= '3';
	const F_DELETED		= '4';
	const F_SENT		= '5';
	const F_OUTBOX		= '6';
	const F_TASK		= '7';
	const F_CALENDAR	= '8';
	const F_CONTACT		= '9';
	const F_NOTE		= '10';
	const F_JOURNAL		= '11';
	const F_UMAIL		= '12';
	const F_UCALENDAR	= '13';
	const F_UCONTACT	= '14';
	const F_UTASK		= '15';
	const F_UJOURNAL	= '16';
	const F_UNOTE		= '17';
	const F_UNKNOWN		= '18';
	const F_CACHE		= '19';

	// type description
	const TYPE          = [ //2
		self::F_GENERIC			=>	'User-created folder (generic)', //2
		self::F_INBOX			=>	'Default "Inbox" folder', //2
		self::F_DRAFT			=>	'Default "Drafts" folder', //2
		self::F_DELETED			=>	'Default "Deleted" folder', //2
		self::F_SENT			=> 	'Default "Sent" folder', //2
		self::F_OUTBOX			=>	'Default "Outbox" folder', //2
		self::F_TASK			=>	'Default "Tasks" folder', //2
		self::F_CALENDAR		=> 	'Default "Calendar" folder', //2
		self::F_CONTACT			=>	'Default "Contacts" folder', //2
		self::F_NOTE			=>	'Default "Notes" folder', //2
		self::F_JOURNAL			=>	'Default "Journal" folder', //2
		self::F_UMAIL			=>	'User-created "Mail" folder', //2
		self::F_UCALENDAR		=>	'User-created "Calendar" folder', //2
		self::F_UCONTACT		=>	'User-created "Contacts" folder', //2
		self::F_UTASK			=>	'User-created "Tasks" folder', //2
		self::F_UJOURNAL		=>	'User-created "Journal" folder', //2
		self::F_UNOTE			=>	'User-created "Notes" folder', //2
		self::F_UNKNOWN			=>	'Unknown folder type', //2
		self::F_CACHE			=>	'Recipient Information cache', //2
	]; //2

	/**
	 * 	Get file type
	 *
	 * 	@param	- Type
	 * 	@return	- Description
	 */
	static public function type(string $typ): string { //2
		return isset(self::TYPE[$typ]) ? self::TYPE[$typ] : '+++ Typ "'.sprintf('%d',$typ).'" not found'; //2
	} //2

}

?>