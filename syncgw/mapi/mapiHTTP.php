<?php
declare(strict_types=1);

/*
 * 	Process HTTP input / output
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

use syncgw\lib\Debug; //3
use syncgw\lib\Config;
use syncgw\lib\HTTP;
use syncgw\lib\Util;
use syncgw\lib\XML;

class mapiHTTP extends HTTP {

	// module version number
	const VER = 2;

	// decode request body
	const REQ		= 1;
	// descode response body
	const RESP		= 2; 		//2
	// create response body
	const MKRESP	= 3;

    /**
     * 	Singleton instance of object
     * 	@var mapiHTTP
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiHTTP {

		if (!self::$_obj) {
            self::$_obj = new self();
            parent::getInstance();
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

		$xml->addVar('Opt', '<a href="https://learn.microsoft.Object/en-us/openspecs/exchange_server_protocols/ms-oxcmapihttp" target="_blank">[MS-OXCMAPIHTTP]</a> '.
				      'Messaging Application Programming Interface (MAPI) Extensions for HTTP v13.0');
		$xml->addVar('Stat', _('Implemented'));

		$xml->addVar('Opt', '<a href="https://tools.ietf.org/html/rfc2616" target="_blank">RFC2616</a> '.
				      'Hypertext Transfer Protocol -- HTTP/1.1');
		$xml->addVar('Stat', _('Implemented'));

		$xml->addVar('Opt', '<a href="https://tools.ietf.org/html/rfc4122" target="_blank">RFC4122</a> '.
				      'A Universally Unique IDentifier (UUID) URN Namespace');
		$xml->addVar('Stat', _('Implemented'));
	}

	/**
	 * 	Check HTTP input
	 *
	 * 	@return - HTTP status code
	 */
	public function checkIn(): int {

		// are we responsible?

		// NSPI (Name Service Provider Interface): Address Book Protocol
		// Mainly used by MAPI clients to perform username lookup

		// EMSMDB (Exchange Message Provide): Exchange transport
		// RFR: used to locate the NSPI server
		if (!isset(self::$_http[HTTP::SERVER]['REQUEST_METHOD']) ||
			self::$_http[HTTP::SERVER]['REQUEST_METHOD'] != 'POST' ||
			!isset(self::$_http[HTTP::SERVER]['REQUEST_URI']) ||
			stripos(self::$_http[HTTP::SERVER]['REQUEST_URI'], '/mapi/') === FALSE)
			return 200;

		// save handler
		$cnf = Config::getInstance();
		$cnf->updVar(Config::HD, 'MAPI');

		// convert binary data to XML
		self::$_http[self::RCV_BODY] = self::_convIn();

		return 200;
	}

	/**
	 * 	Check HTTP output
	 *
	 * 	@return - HTTP status code
	 */
	public function checkOut(): int {

		$cnf = Config::getInstance();

		// output processing
		if ($cnf->getVar(Config::HD) != 'MAPI')
			return 200;

		// do not use "chunked" transfer-encoding!
		if (is_object(self::$_http[HTTP::SND_BODY])) {
			$data =
			// [MS-OXCMAPIHTTP] 3.1.5.6 Handling a Chunked Response
			// [MS-OXCMAPIHTTP] 2.2.7 Response Meta-Tags
			// The server has queued the request to be processed
			"PROCESSING\r\n".
			"DONE\r\n".
			// [MS-OXCMAPIHTTP] 2.2.3.3.9 X-ElapsedTime Header fld
			// The X-ElapsedTime header specifies the amount of Time, in milliseconds, that the server took to
			// process the request. This header is returned by the server as an additional header in the final
			// response.
			"X-ElapsedTime: 0\r\n".
			// [MS-OXCMAPIHTTP] 2.2.3.3.10 X-StartTime Header fld
			// The X-StartTime header specifies the Time that the server started processing the request. This
			// header is returned by the server as an additional header in the final response. This header
			// follows the date/Time format, as specified in [RFC2616].
			"X-StartTime: ".gmdate(Util::RFC_TIME)."\r\n\r\n".
			self::_convOut(self::$_http[HTTP::SND_BODY]);
		} else
			$data = self::$_http[HTTP::SND_BODY];

		$n = $data ? strlen($data) : 0;

		if ($cnf->getVar(Config::DBG_LEVEL) != Config::DBG_OFF && $n) { //2
			$rec  = explode("\r\n", $data);  //2
			$data = strlen($rec[5]) ? self::_convOut($rec[5]) : new XML(); //2
			$data->getVar('MetaTags'); //2
			for ($i=0; $i < 4; $i++) //2
				$data->addVar('MAPIString', $rec[$i]); //2
		} //2

		if (Debug::$Conf['Script'] == 'LoadMapiDecode') //3
			self::_convOut($data); //3

		self::$_http[HTTP::SND_BODY] = $data;

		// send header
		if ($n) {
			// [MS-OXCMAPIHTTP] 3.2.5.2 Responding to All Request Type Requests
			self::addHeader('Content-Length',  strval($n));
			self::addHeader('Connection', 'keep-alive');
			self::addHeader('Cache-Control', 'private');
		}

		self::addHeader('Date', gmdate(Util::RFC_TIME));

		self::addBody($data);

		return 200;
	}

	/**
	 * 	Convert binary data to XML
	 *
	 * 	@return - XML document
	 */
	private function _convIn(): ?XML {

		$cmd = isset(self::$_http[self::RCV_HEAD]['X-Requesttype']) ?
					 self::$_http[self::RCV_HEAD]['X-Requesttype'] : 'Undef';
		Debug::Msg(self::$_http[self::RCV_BODY], 'Binary <'.$cmd.'> data received ('. //3
				   ($n = strlen(self::$_http[self::RCV_BODY])).' bytes 0x'.sprintf('%X', $n).')'. //3
				   ' First 1024 bytes', 0, 1024); //3

		if (!strlen(self::$_http[self::RCV_BODY]))
			return NULL;

		$mapi = mapiHandler::getInstance();
		$xml  = $mapi->Parse($cmd, self::REQ);

		if (!is_null($xml))
			$xml->setTop();

		return $xml;
	}

	/**
	 * 	Convert XML data to binary
	 *
	 * 	@param 	- XML document or binary data
	 * 	@return - Binary data or XML object
	 */
	private function _convOut($wrk) {

		$out = '';

		if (!isset(self::$_http[self::SND_HEAD]['X-Requesttype'])) //2
			return $out; //2

		$cmd = self::$_http[self::SND_HEAD]['X-Requesttype'];

		if (is_object($wrk)) { //2
			$wbxml = mapiWBXML::getInstance();
			$wrk->setTop();
			$out = $wbxml->Encode($wrk, $cmd);
		} else { //2
			// set buffer
			self::$_http[self::SND_BODY] = $wrk; //2
			$mapi = mapiHandler::getInstance(); //2
			$out = $mapi->Parse($cmd, self::RESP); //2
		} //2

		if (is_object($wrk)) { //3
			$wrk->setTop(); //3
			Debug::Msg($wrk, 'New <'.$cmd.'> created'); //3
			Debug::Msg($out, 'New binary <'.$cmd. //3
					   '> created ('.($n = strlen($out)).' bytes 0x'.sprintf('%X', $n).')'. //3
					   ' First 1024 bytes', 0, 1024); //3
		} else  { //3
			Debug::Msg($out, 'Decoded <'.$cmd.'> response'); //3
			Debug::Msg($wrk, 'Binary <'.$cmd. //3
					   '> send ('.($n = strlen($wrk)).' bytes 0x'.sprintf('%X', $n).')'. //3
					   ' First 1024 bytes', 0, 1024); //3
		} //3

		return $out;
	}

}

?>