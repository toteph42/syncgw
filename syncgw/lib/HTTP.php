<?php
declare(strict_types=1);

/*
 * 	Connection handler class
 *
 *	@package	sync*gw
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 *
 */

namespace syncgw\lib;

use syncgw\activesync\masHTTP;
use syncgw\dav\davHTTP;
use syncgw\gui\guiHTTP;

class HTTP {

	// module version number
	const VER = 14;

	const SERVER   = '0';			// received _SERVER data
	const RCV_HEAD = '1';			// received HTTP header
	const RCV_BODY = '2';			// received BODY data
	const SND_HEAD = '3';			// send HTTP header
	const SND_BODY = '4';			// send BODY data
	const HANDLER  = '5';			// sub handler

    // types
    const TYPES    = [ //3
            self::SERVER    => 'Server data', //3
            self::RCV_HEAD	=> 'Received header', //3
            self::RCV_BODY	=> 'Received body', //3
            self::SND_HEAD	=> 'Send header', //3
            self::SND_BODY	=> 'Send body', //3
    ]; //3

	// supported output compression encoding
	const ENCODING = [
            'x-gzip'     => 'gz',
            'gzip'       => 'gz',
            'deflate'    => 'deflate',
    ];

	// status messages
	const MSG      = [
			200	        => 'Ok',
			204 		=> 'No Content',
		    207         => 'Multi-Status',

			400         => 'Bad Request',
			401         => 'Unauthorized',
			403         => 'Forbidden',
		    404         => 'Not Found',
			449         => 'Retry after sending a PROVISION command',

			456			=> 'Blocked',
	        500         => 'Internal Server Error',
			501         => 'Not Implemented',
			503         => 'Service unavailable',
			507 		=> 'Insufficient Disk Space',
	];

	/**
     *  HTTP data
     *  @var array
     */
	static protected $_http;

	/**
	 * 	Reader
	 * 	@var array
	 */
	private $_reader = [];

    /**
     * 	Singleton instance of object
     * 	@var HTTP
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): HTTP {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 10201-10300
			$log = Log::getInstance();
			$log->setMsg([
					10201 => _('Fatal PHP error: %s'),
			]);

			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);

			// we will not save client connection data here, because we first
			// must give GUI_Handler a chance to disable trace
			self::$_http = [
				self::SERVER 	=> [],
				self::RCV_BODY	=> '',
				self::RCV_HEAD	=> [],
				self::SND_BODY	=> '',
				self::SND_HEAD	=> [ 'Response' => '', ],
				self::HANDLER	=> [
					guiHTTP::getInstance(),
					masHTTP::getInstance(),
					davHTTP::getInstance(),
				],
			];

			$class = 'syncgw\mapi\mapiHTTP'; //3
			self::$_http[self::HANDLER][] = $class::getInstance(); //3
		}

		return self::$_obj;
	}

    /**
	 * 	Shutdown function
	 */
	public function delInstance(): void {
		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('HTTP handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status)
			return;

		$xml->addVar('Opt', '<a href="http://tools.ietf.org/html/rfc2616" target="_blank">RFC2616</a> '.
					  _('Hypertext Trasfer Protocol -- HTTP/1.1 handler'));
		$xml->addVar('Stat', _('Implemented'));
		$xml->addVar('Opt', '<a href="http://tools.ietf.org/html/rfc2817" target="_blank">RFC2817</a> '.
					  _('HTTP over TLS handler'));
		$xml->addVar('Stat', _('Implemented'));
	}

	/**
	 * 	Add reader
	 *
	 * 	@param	- Class object or NULL
	 *  @Param  - Function name
	 */
	public function catchHTTP(string $func, $class = NULL): void {
		$c = $class ? get_class($class) : '';
		$k = $c.':'.$func;
		Debug::Msg('Add output reader "'.$k.'"'); //3
		$this->_reader[$k] = [ $c, $func ];
	}

	/**
	 * 	Get variable
	 *
	 * 	@param	- Name of variable
	 * 	@return	- Variable content; NULL = Variable not found; [] for all variables of type HTTP::*
	 */
	public function getHTTPVar(string $name) {

		$rc = NULL;

		// check for special variables
		switch ($name) {
		case self::SERVER:
		case self::RCV_BODY:
		case self::RCV_HEAD:
		case self::SND_BODY:
		case self::SND_HEAD:
			Debug::Msg('['.self::TYPES[$name].']'); //3
		    $rc = self::$_http[$name];
		    break;

		default:
			if (isset(self::$_http[self::SERVER][$name])) {
				$rc = self::$_http[self::SERVER][$name];
				$n  = self::SERVER; //3
			} elseif (isset(self::$_http[self::RCV_HEAD][$name])) {
				$rc = self::$_http[self::RCV_HEAD][$name];
				$n  = self::RCV_HEAD; //3
			} elseif (isset(self::$_http[self::SND_HEAD][$name])) {
				$rc = self::$_http[self::SND_HEAD][$name];
				$n  = self::SND_HEAD; //3
			} else {
				$rc = '';
				$n  = -1; //3
			}
			if ($rc) //3
    			Debug::Msg('['.self::TYPES[$n].']['.$name.'] "'.$rc.'"'); //3
		    else //3
	       		Debug::Msg('['.$name.'] "#Not found#"'); //3
			break;
		}

		return $rc;
	}

	/**
	 * 	Update variable
	 *
	 * 	@param	- HTTP::*
	 * 	@param 	- Name of variable
	 * 	@param	- Variable content or []
	 */
	public function updHTTPVar(string $typ, ?string $name, $val): void {

		switch ($typ) {
		case self::RCV_BODY:
		case self::RCV_HEAD:
		case self::SND_BODY:
		case self::SND_HEAD:
        case self::SERVER:
			Debug::Msg('['.self::TYPES[$typ].']'); //3
			if (is_null($name))
				self::$_http[$typ] = $val;
			else
				self::$_http[$typ][$name] = $val;

		default:
			break;
		}
	}

	/**
	 * 	Add header variable
	 *
	 * 	@param	- Name of variable
	 * 	@param	- Variable content
	 */
	public function addHeader(string $name, string $val): void {
        Debug::Msg('['.self::TYPES[self::SND_HEAD].']['.$name.'] = "'.$val.'"'); //3
		self::$_http[self::SND_HEAD][$name] = $val;
	}

	/**
	 * 	Add body
	 *
	 * 	@param 	- Body to send
	 */
	public function addBody($val): void {
        Debug::Msg('['.self::TYPES[self::SND_BODY].']'); //3
		self::$_http[self::SND_BODY] = $val;
	}

	/**
	 * 	Receive and identify HTTP data
	 *
	 *	@param	- $_SERVER array
	 *	@param 	- Body data
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	public function receive(array $server, string $body): bool {

		// load received body data
		self::$_http[self::RCV_BODY] = $body;
		// load server data
		self::$_http[self::SERVER] = $server;

		// call handler
		if (self::checkIn() !== 200)
			return FALSE;

   		return TRUE;
	}

	/**
	 * 	Send HTTP data
	 *
	 * 	@param	- HTTP Status code
	 * 	@param 	- Reason-Phrase
	 */
	public function send(int $rc, string $reason = ''): void {

 		// create HTTP status code
		// set default HTTP output
		if (!isset(self::$_http[self::SERVER]['SERVER_PROTOCOL']))
			self::$_http[self::SERVER]['SERVER_PROTOCOL'] = 'HTTP/1.1';

		$msg = self::$_http[self::SERVER]['SERVER_PROTOCOL'].' '.$rc.' ';

		if ($reason)
			$msg .= $reason;
		else
			$msg .= isset(self::MSG[$rc]) ? self::MSG[$rc] : 'Unknown';

		self::$_http[self::SND_HEAD]['Response'] = $msg;

		// call handler
		if ($rc != 400)
			if (self::checkOut() !== 200)
				return;

		$enc = NULL;

		// output length (uncompressed)
		if (isset(self::$_http[self::SND_HEAD]['Content-Length'])) {

			// check encoding request
			// this is especially require for Apple (wont work without)
			if (self::$_http[self::SND_HEAD]['Content-Length']) {
				if(isset(self::$_http[self::RCV_HEAD]['Accept-Encoding'])) {
				    foreach (self::ENCODING as $r => $t)
					    if (stripos(self::$_http[self::RCV_HEAD]['Accept-Encoding'], $r) !== FALSE) {
							// send encoding header
						    self::$_http[self::SND_HEAD]['Content-Encoding'] = $r;
					    	$enc 											 = $t;
				            break;
					    }
				}
				self::$_http[self::SND_HEAD]['Cache-Control'] = 'private';
			}
		}

		// provide output to reader
		foreach ($this->_reader as $parm) {
			if (isset($parm[0])) {
		        $parm[0] = $parm[0]::getInstance();
		        if (!$parm[0]->{$parm[1]}(self::$_http[self::SND_HEAD], self::$_http[self::SND_BODY]))
		            return;
		    } else
		    	if (!$parm[1](self::$_http[self::SND_HEAD], self::$_http[self::SND_BODY]))
		        	return;
		}

	    // are we testing?
		if (Debug::$Conf['Script']) //3
			return; //3

   		// stop logging of console output
	   	$log = Log::getInstance();
		$log->catchConsole(FALSE);

		// convert body?
		if ($enc && self::$_http[self::SND_BODY]) {
            // gzip encode output with an optimal level of 4
            self::$_http[self::SND_BODY] = gzencode(self::$_http[self::SND_BODY], 4, $enc == 'gz' ? FORCE_GZIP : FORCE_DEFLATE);
			// set new compressed output length
            self::$_http[self::SND_HEAD]['Content-Length'] = strlen(self::$_http[self::SND_BODY]);
		}

		// flush headers
		header(self::$_http[self::SND_HEAD]['Response']);
   		foreach (self::$_http[self::SND_HEAD] as $k => $v) {
   			if ($k != 'Response')
   				header($k.': '.$v);
   		}

   		// flush body
   		if (!is_null(self::$_http[self::SND_BODY]) && strlen(self::$_http[self::SND_BODY]))
   			echo self::$_http[self::SND_BODY];
	}

	/**
	 * 	Check HTTP input
	 *
	 * 	@return - HTTP status code
	 */
	public function checkIn(): int {

		Debug::Msg('Modify received header'); //3

		$cnf = Config::getInstance(); //2

		// convert server data to input header
		self::$_http[self::RCV_HEAD] = [];
		foreach (self::$_http[self::SERVER] as $k => $v) {

			// do not swap cookie variables
			if (stripos($k, 'HTTP_COOKIE') !== FALSE)
				continue;

			// be sure to decode URL characters
			$v = is_string($v) && $k != 'PHP_AUTH_PW' && $k != 'HTTP_CONTENT_TYPE' ? urldecode($v) : $v;

			// check for input header
			if (substr($k, 0, 5) == 'HTTP_') {
			    $kc = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($k, 5)))));

				// make some special additional conversions
				foreach ([ 'DAV', 'MS-Author-Via', 'WWW', 'MS-ASProtocolVersion', 'X-MS-PolicyKey', 'MS-ASAcceptMultiPart' ] as $c) {
					if (($p = strpos(strtolower($kc), strtolower($c))) !== FALSE)
						$kc = substr($kc, 0, $p).$c.substr($kc, $p + strlen($c));
				}
				self::$_http[self::RCV_HEAD][$kc] = $v;
				continue;
			}

			// set http auth headers for apache+php-cgi work around
			// please note: you need to add .htaccess patch to get this working!
			// set http auth headers for apache+php-cgi work around if variable gets renamed by apache
			$m = [];
			if (($k == 'HTTP_AUTHORIZATION' || $k == 'REDIRECT_HTTP_AUTHORIZATION') && preg_match('/Basic\s+(.*)$/i', $v, $m)) {
			    @list($u, $p) = explode(':', base64_decode($m[1], TRUE));
                // special hack for ActiveSync to remove domain name
   				if (($t = strrpos(strip_tags($u), '\\')))
       				self::$_http[self::RCV_HEAD]['User'] = substr(strip_tags($u), $t + 1);
   				self::$_http[self::RCV_HEAD]['Password'] = strip_tags($p);
   				continue;
			}

			// normal authorisation
			if ($k == 'PHP_AUTH_USER') {

				self::$_http[self::RCV_HEAD]['User'] = $v;

                // special hack for ActiveSync to remove domain name
   				if (($p = strrpos(self::$_http[self::RCV_HEAD]['User'], '\\')))
       				self::$_http[self::RCV_HEAD]['User'] = substr(self::$_http[self::RCV_HEAD]['User'], $p + 1);
       			continue;
			}

			if ($k == 'PHP_AUTH_PW') {
				self::$_http[self::RCV_HEAD]['Password'] = $v;
				continue;
			}

			// get CONTENT_TYPE (check in case of problems at customer)
			// $strData=file_get_contents('php://input'); $strMime = finfo_buffer(finfo_open(), $strData, FILEINFO_MIME_TYPE);
			// A potential solution would be to edit your .htaccess file and add
			// RewriteEngine on
			// RewriteRule .* - [E=CONTENT_TYPE:%{HTTP:Content-Type},L]
			// RewriteRule .* - [E=CONTENT_LENGTH:%{HTTP:Content-Length},L]
			if ($k == 'CONTENT_TYPE') {
				self::$_http[self::RCV_HEAD]['Content-Type'] = $v;
				continue;
			}

			if ($k == 'CONTENT_LENGTH') {
				self::$_http[self::RCV_HEAD]['Content-Length'] = $v;
				continue;
			}

			// swap Host for WebDAV
			if ($k == 'HTTP_HOST') {
			    self::$_http[self::RCV_HEAD]['Host'] = $v;
			    continue;
			}
		}

        // debugging turned on?
		if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) { //2
			if (isset(self::$_http[self::RCV_HEAD]['User']))  //2
				self::$_http[self::RCV_HEAD]['User'] = $cnf->getVar(Config::DBG_USR); //2
	   		if (isset(self::$_http[self::RCV_HEAD]['Password'])) //2
				self::$_http[self::RCV_HEAD]['Password'] = $cnf->getVar(Config::DBG_UPW); //2
		} //2

		// set request information to input header
		if ($req = trim((isset(self::$_http[self::SERVER]['REQUEST_METHOD']) ?
		                self::$_http[self::SERVER]['REQUEST_METHOD'] : '').' '.
			 			(isset(self::$_http[self::SERVER]['PATH_INFO']) ?
			 			self::$_http[self::SERVER]['PATH_INFO'] :
                   		(isset(self::$_http[self::SERVER]['REQUEST_URI']) ?
			 			self::$_http[self::SERVER]['REQUEST_URI'] : '')).' '.
                   		(isset(self::$_http[self::SERVER]['SERVER_PROTOCOL']) ?
                   		self::$_http[self::SERVER]['SERVER_PROTOCOL'] : '')))
			self::$_http[self::RCV_HEAD]['Request'] = $req;

	    // swap Authorization header for SabreDAV
   		if (isset(self::$_http[self::RCV_HEAD]['User']) && isset(self::$_http[self::RCV_HEAD]['Password']))
   		    self::$_http[self::RCV_HEAD]['Authorization'] = 'Basic ' .
   		                                         base64_encode(self::$_http[self::RCV_HEAD]['User'].':'.
   		                                         self::$_http[self::RCV_HEAD]['Password']);

		// special hook to catch PHP fatal errors (at least in XAMPP)
		if (!is_object(self::$_http[self::RCV_BODY]) && stripos(self::$_http[self::RCV_BODY], "\nFatal error") !== FALSE) {
			$msg = substr(self::$_http[self::RCV_BODY], 14);
			ErrorHandler::Raise(10201, substr($msg, 0, strpos($msg, "\n")));
			return 503;
		}

		// call handler
		foreach (self::$_http[self::HANDLER] as $class) {
			if (($rc = $class->checkIn()) != 200) {
				self::send($rc);
				return $rc;
			}
		}

		return 200;
	}

	/**
	 * 	Process HTTP output
	 *
	 * 	@return - HTTP status code
	 */
	public function checkOut(): int {

		foreach (self::$_http[self::HANDLER] as $class) {
			if (($rc = $class->checkOut()) != 200)
				return $rc;
		}

		return 200;
	}

}

?>