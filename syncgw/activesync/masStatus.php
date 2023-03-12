<?php
declare(strict_types=1);

/*
 * 	ActiveSync status definitions
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

class masStatus {

	// module version number
	const VER = 2;

	// [MS-ASCMD] 2.2.2 Common Status Codes
	// Supported by: 14.0, 14.1, 16.0, 16.1
	const OK	  		= '1';
	const CONTENT		= '101'; // 12.0, or 12.1 is used an HTTP 400 response is returned
	const WBXML			= '102';
	const XML			= '103'; // 12.0, or 12.1 is used an HTTP 400 response is returned
	const DATETIME 		= '104';
	const COMBIDS		= '105';
	const IDS			= '106';
	const MIME 			= '107';
	const DeviceId			= '108';
	const DEVTYPE		= '109';
	const SERVER		= '110'; // 12.0, or 12.1 is used an HTTP 500 response is returned
	const RETRY			= '111'; // 12.0, or 12.1 is used an HTTP 503 response is returned
	const ACCESS 		= '112'; // 12.0, or 12.1 is used an HTTP 403 response is returned
	const QUOTA 		= '113'; // 12.0, or 12.1 is used an HTTP 507 response is returned
	const OFFLINE 		= '114';
	const SENDQUOTA		= '115';
	const RECIP			= '116';
	const NOREPLY 		= '117';
	const SEND 			= '118';
	const NORECIP		= '119';
	const SUBMIT		= '120';
	const REPLYERR		= '121';
	const ATTACHMENT	= '122';
	const MAILBOX 		= '123';
	const ANONYM 		= '124';
	const USER 			= '125'; // 12.0, or 12.1 is used an HTTP 403 response is returned
	const MAS 			= '126'; // 12.0, or 12.1 is used an HTTP 403 response is returned
	const NOSYNC		= '127';
	const MAILYSNC		= '128';
	const DEVSYNC 		= '129';
	const ACTION		= '130';
	const DISABLED 		= '131';
	const DATA			= '132'; // 12.0, or 12.1 is used, an HTTP 403 response for the Provision command,
								 // or an HTTP 500 response is returned instead of this status value
	const DEVLOCK		= '133';
	const DEVSTATE		= '134';
	const EXISTS 		= '135';
	const VERSION 		= '136';
	const COMMAND 		= '137'; // 12.0, or 12.1 is used an HTTP 501 response is returned
	const CMDVER 		= '138'; // 12.0, or 12.1 is used an HTTP 400 response is returned
	const PROVISION 	= '139';
	const WIPEREQUEST 	= '140'; // 12.0, or 12.1 is used an HTTP 403 response is returned
	const NOPROVISION 	= '141'; // 12.0, or 12.1 is used an HTTP 449 response is returned
	const NOTPROISION	= '142'; // 12.0, or 12.1 is used an HTTP 449 response is returned
	const POLREFRESH 	= '143'; // 12.0, or 12.1 is used an HTTP 449 response is returned
	const POLKEY		= '144'; // 12.0, or 12.1 is used an HTTP 449 response is returned
	const EXTMANAGED 	= '145';
	const MEETRECUR		= '146';
	const UNKNOWN 		= '147'; // 12.0, or 12.1 is used an HTTP 400 response is returned
	const NOSSL 		= '148';
	const REQUEST 		= '149';
	const NOTFOUND 		= '150';
	const MAILFOLDER 	= '151';
	const MAILNOFOLDER	= '152';
	const MOVE			= '153';
	const MAILMOVE		= '154';
	const CONVMOVE 		= '155';
	const DESTMOVE 		= '156';
	const RECIPMATCH 	= '160';
	const DISTLIST 		= '161';
	const TRANSIENT 	= '162';
	const AVAIL 		= '163';
	const BODYPART 		= '164';
	const DEVINF 		= '165';
	const ACCID 		= '166';
	const ACCSEND		= '167';
	const IRMDISABLED	= '168';
	const IRMTRANSIENT	= '169';
	const IRMERR		= '170';
	const TEMPLID		= '171';
	const IRMOP 		= '172';
	const NOPIC 		= '173';
	const PICSIZE  		= '174';
	const PICLIMIT 		= '175';
	const CONVSIZE 		= '176';
	const DEVLIMIT 		= '177';
	const SMARTFWD 		= '178';
	const SMARTFWDRD	= '179'; // 16.0, 16.1
	const DNORECIP		= '183'; // 16.0, 16.1
	const EXCEPTION		= '184'; // 16.0, 16.1

	const STAT			= [ //2
		self::OK			=>	'Success', //2
		self::CONTENT		=> 'The body of the HTTP request sent by theclient is invalid', //2
		self::WBXML			=> 'The request contains WBXML but it could not be decoded into XML', //2
		self::XML			=> 'The XML provided in the request does not follow the protocol requirements', //2
		self::DATETIME		=> 'The request contains a timestamp that could not be parsed into a valid date and time', //2
		self::COMBIDS		=> 'The request contains a combination of parameters that is invalid', //2
		self::IDS			=> 'The request contains one or more IDs that could not be parsed into valid values', //2
		self::MIME			=> 'The request contains MIME that could not be parsed', //2
		self::DeviceId			=> 'The device ID is either missing or has an invalid format', //2
		self::DEVTYPE		=> 'The device type is either missing or has an invalid format', //2
		self::SERVER		=> 'The server encountered an unknown error, the device SHOULD NOT retry later', //2
		self::RETRY			=> 'The server encountered an unknown error, the device SHOULD NOT retry later', //2
		self::ACCESS		=> 'The server does not have access to read/write to an object in the directory service', //2
		self::QUOTA			=> 'The mailbox has reached its size quota', //2
		self::OFFLINE		=> 'The mailbox server is offline', //2
		self::SENDQUOTA		=> 'The request would exceed the send quota', //2
		self::RECIP			=> 'One of the recipients could not be resolved to an email address', //2
		self::NOREPLY		=> 'The mailbox server will not allow a reply of this message', //2
		self::SEND			=> 'The message was already sent in a previous request or the request contains a message ID that was already used in a recent message', //2
		self::NORECIP		=> 'The message being sent contains no recipient', //2
		self::SUBMIT		=> 'The server failed to submit the message for delivery', //2
		self::REPLYERR		=> 'The server failed to create a reply message', //2
		self::ATTACHMENT	=> 'The attachment is too large to be processed by this request', //2
		self::MAILBOX		=> 'A mailbox could not be found for the user', //2
		self::ANONYM		=> 'The request was sent without credentials. Anonymous requests are not allowed', //2
		self::USER			=> 'The user was not found in the directory service', //2
		self::MAS			=> 'The user object in the directory service indicates that this user is not allowed to use ActiveSync', //2
		self::NOSYNC		=> 'The server is configured to prevent user\'s from syncing', //2
		self::MAILYSNC		=> 'The server is configured to prevent user\'s on legacy server\'s from syncing', //2
		self::DEVSYNC		=> 'The user is configured to allow only some devices to sync. This device is not the allowed device', //2
		self::ACTION		=> 'The user is not allowed to perform that request', //2
		self::DISABLED		=> 'The user\'s account is disabled', //2
		self::DATA			=> 'The server\'s data file that contains the state of the client was unexpectedly missing', //2
		self::DEVLOCK		=> 'The server\'s data file that contains the state of the client is locked', //2
		self::DEVSTATE		=> 'The server\'s data file that contains the state of the client appears to be corrupt', //2
		self::EXISTS		=> 'The server\'s data file that contains the state of the client already exists', //2
		self::VERSION		=> 'The version of the server\'s data file that contains the state of the client is invalid', //2
		self::COMMAND		=> 'The version of the server’s data file that contains the state of the client is invalid', //2
		self::PROVISION		=> 'The device uses a protocol version that cannot send all the policy settings the admin enabled', //2
		self::WIPEREQUEST	=> 'A remote wipe was requested', //2
		self::NOPROVISION	=> 'A policy is in place but the device is not provisionable', //2
		self::NOTPROISION	=> 'There is a policy in place; the device needs to provision', //2
		self::POLREFRESH	=> 'The policy is configured to be refreshed every few hours', //2
		self::POLKEY		=> 'The devices policy key is invalid', //2
		self::EXTMANAGED	=> 'The device claimed to be externally managed, but the server does not allow externally managed devices to sync', //2
		self::MEETRECUR		=> 'The request tried to forward an occurrence of a meeting that has no recurrence', //2
		self::UNKNOWN		=> 'The request tried to operate on a type of items unknown to the server', //2
		self::NOSSL			=> 'The request needs to be proxied to another server but that server does not have SSL enabled', //2
		self::REQUEST		=> 'The server had stored the previous request from that device. When the device sent an empty request, the server tried to re-execute that previous request but it was found to be impossible', //2
		self::NOTFOUND		=> 'The value of either the <ItemId> element or the <InstanceId> element specified in the <SmartReply> or the <SmartForward> command request could not be found in the mailbox', //2
		self::MAILFOLDER	=> 'The mailbox contains too many folders. By default, the mailbox cannot contain more than 1000 folders', //2
		self::MAILNOFOLDER	=> 'The mailbox contains no folders', //2
		self::MOVE			=> 'After moving items to the destination folder, some of those items could not be found', //2
		self::MAILMOVE		=> 'The mailbox server returned an unknown error while moving items', //2
		self::CONVMOVE		=> 'An <ItemOperations> command request to move a conversation is missing the <MoveAlways> element', //2
		self::DESTMOVE		=> 'The destination folder for the move is invalid', //2
		self::RECIPMATCH	=> 'The command has exceeded the maximum number of exactly matched recipients that it can request availability for', //2
		self::DISTLIST		=> 'The size of the distribution list is larger than the availability service is configured to process', //2
		self::TRANSIENT		=> 'Availability service request failed with a transient error', //2
		self::AVAIL			=> 'Availability service request failed with an error', //2
		self::BODYPART		=> 'The <BodyPartPreference> node (as specified in has an unsupported Type element value', //2
		self::DEVINF		=> 'The required DeviceInformation element is missing in the Provision request', //2
		self::ACCID			=> 'The <AccountId> value is not valid', //2
		self::ACCSEND 		=> 'The <AccountId> value is not valid', //2
		self::IRMDISABLED	=> 'The Information Rights Management feature is disabled', //2
		self::IRMTRANSIENT	=> 'Information Rights Management encountered an transient error', //2
		self::IRMERR		=> 'Information Rights Management encountered an transient error', //2
		self::TEMPLID		=> 'The Template ID value is not valid', //2
		self::IRMOP			=> 'Information Rights Management does not support the specified operation', //2
		self::NOPIC			=> 'The user does not have a contact photo', //2
		self::PICSIZE		=> 'The contact photo exceeds the size limit set by the <MaxSize> element', //2
		self::PICLIMIT		=> 'The number of contact photos returned exceeds the size limit set by the <MaxPictures> element', //2
		self::CONVSIZE		=> 'The conversation is too large to compute the body parts', //2
		self::DEVLIMIT		=> 'The user\'s account has too many device partnerships', //2
		self::SMARTFWD		=> 'The SmartForward command request included elements that are not allowed to be combined with either the <Forwardees> element or the <Body> element', //2
		self::SMARTFWDRD	=> 'The <Forwardees> element or the <Body> element in the <SmartForward> command request could not be parsed', //2
		self::DNORECIP		=> 'A draft email either has no recipients or has a recipient email address that is not in valid SMTP format', //2
		self::EXCEPTION		=> 'The server failed to successfully save all of the exceptions specified in a Sync command request to add a calendar series with exceptions', //2
	]; //2

	/**
	 * 	Get status message
	 *
	 * 	@param	- Status
	 * 	@return	- Description
	 */
	static public function status(string $stat): string {  //2

		return isset(self::MSG[$stat]) ? self::MSG[$stat] : '+++ Status "'.sprintf('%d',$stat).'" not found'; //2
	} //2

}

?>