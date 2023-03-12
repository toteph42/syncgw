<?php
declare(strict_types=1);

/*
 * 	WebDAV handler class
 *
 *	@package	sync*gw
 *	@subpackage	SabreDAV support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 * For patches search "syncGW" in directory "syncgw/Sabre"
 */

namespace syncgw\dav;

use syncgw\lib\Debug; //3
use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\HTTP;
use syncgw\lib\Log;
use syncgw\lib\Session;
use syncgw\lib\XML;
use syncgw\lib\Server;

class davHandler {

	// module version number
	const VER = 12;

    /**
     * 	Singleton instance of object
     * 	@var davHandler
     */
    static private $_obj = NULL;

	/**
     * 	MIME version to use
     *  @var array
     */
    static public $mime = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): davHandler {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 19001-19010
			$log = Log::getInstance();
			$log->setMsg([
		            19001 => _('Calendar and task list synchronization in parallel is not supported by DAV protocoll - task synchronization disabled'),
					19002 => _('Cannot load %s for user (%s) - please check synchronization status'),
			]);
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

    	$xml->addVar('Name', _('WebDAV handler'));
		$xml->addVar('Ver', strval(self::VER));
		if ($status) {
			$xml->addVar('Opt', _('Maximum object size'));
			$cnf = Config::getInstance();
			$xml->addVar('Stat', sprintf(_('%d bytes'), $cnf->getVar(Config::MAXOBJSIZE)) );
		} else {
			$xml->addVar('Opt', '<a href="https://github.com/sabre-io/dav" target="_blank">SabreDAV</a> '._('framework for PHP'));
			$xml->addVar('Stat', 'v'.\Sabre\DAV\Version::VERSION);
		}

		$srv = Server::getInstance();
		$srv->getSupInfo($xml, $status, 'dav');
	}

	/**
	 * 	Process client request
	 */
	public function Process(): void {

		$http = HTTP::getInstance();
		$cnf = Config::getInstance();

		// check authorization
		if (!$http->getHTTPVar('PHP_AUTH_USER')) {
			$http->addHeader('WWW-Authenticate', 'Basic realm=davHandler');
			$http->send(401);
			return;
		}

		// set hacks we must support
		$a = $http->getHTTPVar('User-Agent');
		$h = 0;
		// CardDAV client
		if (stripos($a, 'CardDAV-Sync (Android)') !== FALSE)
			$h |= Config::HACK_CDAV;
		$cnf->updVar(Config::HACK, $h);

		// create / restart session
		$sess = Session::getInstance();
		if (!$sess->mkSession())
			return;

		// we don't wanna get SabreDAV warnings
		ErrorHandler::filter(E_NOTICE|E_DEPRECATED, 'Sabre');

		// ----------------------------------------------------------------------------------------------------------------------------------
		// Extracted from "SabreDav\examples\groupwareserver.php"

		$authBackend      = davUser::getInstance();
		$principalBackend = davPrincipal::getInstance();
		$tree             = [];
		$ena 			  = $cnf->getVar(Config::ENABLED);

		if ($ena & DataStore::CONTACT) {
    		$tree[] = new \Sabre\DAVACL\PrincipalCollection($principalBackend);
    		$tree[] = new \Sabre\CardDAV\AddressBookRoot($principalBackend, davContact::getInstance());
    		// default mime encoding
    		self::$mime = [ 'text/vcard', 4.0 ];
			// <CARD:address-data content-type="text/vcard" version="4.0"/>
			$body = $http->getHTTPVar(HTTP::RCV_BODY);
    		if (($pos = strpos($body, 'content-type')) !== FALSE) {
    			$a = explode('"', substr($body, $pos, 50));
    			self::$mime = [ $a[1], $a[3] ];
    		}
    		// VERSION:4.0
    		elseif (($pos = strpos($body, 'VERSION:')) !== FALSE) {
    			$v = substr($body, $pos + 8, 3);
    			self::$mime = [ $v == '2.1' ? 'text/x-vcard' : 'text/vcard', $v ];
    		}
		} else
			self::$mime = [ 'text/calendar', 2.0 ];

		// is task data store enabled?
		if ($ena & DataStore::TASK) {

            // sub domain enables to task list synchronization?
            // task list synchronization forced?
		    if (($t = $cnf->getVar(Config::FORCE_TASKDAV)) && ($t == 'FORCE' || stripos($http->getHTTPVar('SERVER_NAME'), $t))) {
    		    // disable calendar synchronization
                $ena &= ~DataStore::CALENDAR;
                Debug::Msg('Force task synchronization only'); //3
		    }

		    if ($ena & DataStore::CALENDAR && $ena & DataStore::TASK) {
   		        $log = Log::getInstance();
       			$log->Msg(Log::WARN, 19001);
       			$ena &= ~DataStore::TASK;
		    } else {
        		$tree[] = new \Sabre\CalDAV\Principal\Collection($principalBackend);
         		$tree[] = new \Sabre\CalDAV\CalendarRoot($principalBackend, davTask::getInstance());
		    }

		    // store update enabled handler ID for this session
            $cnf->updVar(Config::ENABLED, $ena);
		}

		// is calendar data store enabled?
		if ($ena & DataStore::CALENDAR) {
    		$tree[] = new \Sabre\CalDAV\Principal\Collection($principalBackend);
    		$tree[] = new \Sabre\CalDAV\CalendarRoot($principalBackend, davCalendar::getInstance());
		}

		// allocate server
		$wd = new \Sabre\DAV\Server($tree);
		// catch full exception errors?
		if ($cnf->getVar(Config::TRACE_EXC) == 'Y')
			$wd->debugExceptions = TRUE;

		// patch Sapi class => Sabre\HTTP\Sapi.php
		$wd->sapi = $this;
		$wd->httpRequest = self::getRequest();
		$wd->setBaseUri($http->getHTTPVar('SCRIPT_NAME'));

		// authentication plugin
        $wd->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));

        if ($ena & DataStore::CONTACT)
            $wd->addPlugin(new \Sabre\CardDAV\Plugin());
        if ($ena & (DataStore::CALENDAR|DataStore::TASK))
            $wd->addPlugin(new \Sabre\CalDAV\Plugin());

        // permission plugin
        $wd->addPlugin(new \Sabre\DAVACL\Plugin());
        // WebDAV sync plugin
        $wd->addPlugin(new \Sabre\DAV\Sync\Plugin());

        $wd->start();

		// ----------------------------------------------------------------------------------------------------------------------------------

        ErrorHandler::resetReporting();
	}

    /**
     * This static method will create a new Request object, based on the
     * current PHP request.
     *
     * @return - Request
     */
    static function getRequest() {

        $http = HTTP::getInstance();
        $req = new \Sabre\HTTP\Request($http->getHTTPVar('REQUEST_METHOD'), $uri = $http->getHTTPVar('REQUEST_URI'),
                                       $http->getHTTPVar(HTTP::RCV_HEAD), $http->getHTTPVar(HTTP::RCV_BODY));
        $req->setHttpVersion($http->getHTTPVar('SERVER_PROTOCOL') == 'HTTP/1.0' ? '1.0' : '1.1');
        $req->setRawServerData($http->getHTTPVar(HTTP::SERVER));
        $p = $http->getHTTPVar('HTTPS');
        $h = $http->getHTTPVar('HTTP_HOST');
        $req->setAbsoluteUrl((!empty($p) && $p !== 'off' ? 'https' : 'http').'://'.($h ? $h : 'localhost').$uri);
        $req->setPostData([]);

        return $req;
    }

    /**
     * Sends the HTTP response back to a HTTP client.
     *
     * This calls php's header() function and streams the body to php://output.
     *
     * @param  - ResponseInterface
     */
    static function sendResponse(\Sabre\HTTP\ResponseInterface $response) {

        $http = HTTP::getInstance();

        $rc = $response->getStatus();
        foreach ($response->getHeaders() as $key => $value) {
            foreach ($value as $unused => $v)
                $http->addHeader($key, $v);
        }
		$unused; // disable Eclipse warning

        $bdy = $response->getBody();
        if (is_resource($bdy)) {
            $http->addBody(stream_get_contents($bdy));
            fclose($bdy);
         } else
            $http->addBody($bdy);

        // flush data
        $http->send($rc);
     }

}

?>