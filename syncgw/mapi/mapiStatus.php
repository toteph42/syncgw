<?php
declare(strict_types=1);

/*
 * 	MAPI over HTTP status definitions
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

class mapiStatus {

	// module version number
	const VER = 1;

	// [MS-OXCMAPIHTTP] 2.2.3.3.3 X-ResponseCode Header fld
	const OK 			= '0';
	const ERR			= '1';
	const VERB			= '2';
	const PATH			= '3';
	const HEAD			= '4';
	const REQ			= '5';
	const SESS			= '6';
	const MISS			= '7';
	const ANONYM		= '8';
	const SIZE			= '9';
	const CONTEXT		= '10';
	const PRIV			= '11';
	const BODY			= '12';
	const COOKIE		= '13';
	const IGNORE		= '14';
	const SEQ			= '15';
	const ENDPOINT		= '16';
	const RESP			= '17';
	const DOWN			= '18';
	// [MS-OXCRPC] 3.1.4.2 EcDoRpcExt2 Method (Opnum 11)
	const LENGTH		= '1206';

	const STAT			= [ //2
		self::OK 		=> 'The request was properly formatted and accepted', //2
		self::ERR		=> 'The request produced an unknown failure', //2
		self::VERB		=> 'The request has an invalid verb', //2
		self::PATH		=> 'The request has an invalid path', //2
		self::HEAD		=> 'The request has an invalid header', //2
		self::REQ		=> 'The request has an invalid X-RequestType header', //2
		self::SESS		=> 'The request has an invalid session context cookie', //2
		self::MISS		=> 'The request has a missing required header', //2
		self::ANONYM	=> 'The request is anonymous, but anonymous requests are not accepted', //2
		self::SIZE		=> 'The request is too large', //2
		self::CONTEXT	=> 'The Session Context is not found', //2
		self::PRIV		=> 'The client has no privileges to the Session Context', //2
		self::BODY		=> 'The request body is invalid', //2
		self::COOKIE	=> 'The request is missing a required cookie', //2
		self::IGNORE	=> 'This value MUST be ignored by the client', //2
		self::SEQ		=> 'The request has violated the sequencing requirement of one request at a Time per Session Context', //2
		self::ENDPOINT	=> 'The endpoint is disabled', //2
		self::RESP		=> 'The response is invalid', //2
		self::DOWN		=> 'The endpoint is shutting down', //2
		self::LENGTH	=> 'The format of the request was found to be invalid', //2
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