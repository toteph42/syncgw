<?php
declare(strict_types=1);

/*
 * 	Process HTTP input / output
 *
 *	@package	sync*gw
 *	@subpackage	SabreDAV support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\dav;

use syncgw\lib\Config;
use syncgw\lib\HTTP;
use syncgw\lib\XML;

class davHTTP extends HTTP {

	// module version number
	const VER = 4;

    /**
     * 	Singleton instance of object
     * 	@var davHTTP
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): davHTTP {

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

		$xml->addVar('Opt', _('WebDAV HTTP handler'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Check HTTP input
	 *
	 * 	@return - HTTP status code
	 */
	public function checkIn(): int {

        $cnf = Config::getInstance();
        if ($cnf->getVar(Config::HD))
        	return 200;

		$cnf->updVar(Config::HD, 'DAV');

        // debugging turned on?
        if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) { //2

	        $tags = [ 'REQUEST_URI' => HTTP::SERVER, 'PATH_INFO' => HTTP::SERVER, //2
	        		  'PATH_TRANSLATED' => HTTP::SERVER, 'PHP_SELF' => HTTP::SERVER, //2
	        		  'Request' => HTTP::RCV_HEAD, 'User' => HTTP::RCV_HEAD ]; //2

	        $nuid = $cnf->getVar(Config::DBG_USR); //2

	        // check old user id
	        if (isset(self::$_http[HTTP::SERVER]['PHP_AUTH_USER'])) { //2

	        	$ouid = self::$_http[HTTP::SERVER]['PHP_AUTH_USER']; //2

	            // replace userid with debug user id
	            foreach ([ '/', DIRECTORY_SEPARATOR ] as $sep) { //2
		            foreach ([ 'principals', 'calendars', 'contacts'] as $key) { //2
				        foreach ($tags as $tag => $typ) { //2
				        	if (isset(self::$_http[$typ][$tag])) //2
			        			self::$_http[$typ][$tag] = str_replace($key.$sep.$ouid, $key.$sep.$nuid, self::$_http[$typ][$tag]); //2
					    } //2
	        	    } //2
	            } //2

	            // change body
	           	self::$_http[HTTP::RCV_BODY] = str_replace([ //2
	           				'/principals/'.$ouid.'/', //2
	           				'/calendars/'.$ouid.'/', //2
	           				'/contacts/'.$ouid.'/', //2
	           				'displayname>'.$ouid.'<', //2
	           	], [ //2
	           				'/principals/'.$nuid.'/', //2
	           				'/calendars/'.$nuid.'/', //2
	           				'/contacts/'.$nuid.'/', //2
	           				'displayname>'.$nuid.'<', //2
	           	],  self::$_http[HTTP::RCV_BODY]); //2
	        } //2

           	// special hack for e.g. PROPFIND /calendars/t1@mail.fd/Florian/
	        if (self::$_http[HTTP::SERVER]['REQUEST_METHOD'] == 'PROPFIND' &&//2
	        	strpos(self::$_http[HTTP::RCV_HEAD]['Request'], '@')) { //2
	        	$w = explode('/', self::$_http[HTTP::RCV_HEAD]['Request']); //2
	        	$ouid = $w[2]; //2
	        	$w[2] = $nuid; //2
		        self::$_http[HTTP::RCV_HEAD]['Request'] = implode('/', $w); //2
		        foreach ([ 'REQUEST_URI', 'PATH_INFO', 'PATH_TRANSLATED', 'PHP_SELF'] as $tag) //2
		        	if (isset(self::$_http[HTTP::SERVER][$tag])) //2
			        	self::$_http[HTTP::SERVER][$tag] = str_replace($ouid, $nuid, self::$_http[HTTP::SERVER][$tag]); //2
	        } //2

        } //2

		// do not change "xmlns" to "xml-ns" as Sabre expect this attibute!

		return 200;
	}

	/**
	 * 	Check HTTP output
	 *
	 * 	@return - HTTP status code
	 */
	public function checkOut(): int {

		$cnf = Config::getInstance();

		if ($cnf->getVar(Config::HD) != 'DAV')
			return 200;

		// delete optional character set attributes
		if (!self::$_http[HTTP::SND_BODY])
			self::$_http[HTTP::SND_BODY] = '';

		self::$_http[HTTP::SND_BODY] = preg_replace('/(\sCHARSET)(=[\'"].*[\'"])/iU', '', self::$_http[HTTP::SND_BODY]);

        // debugging turned on?
        if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) { //2

        	// check old user id
	        if (isset(self::$_http[HTTP::SERVER]['PHP_AUTH_USER'])) { //2

	        	$ouid = self::$_http[HTTP::SERVER]['PHP_AUTH_USER']; //2
		        $nuid = $cnf->getVar(Config::DBG_USR); //2

	        	// change body
		        self::$_http[HTTP::SND_BODY] = str_replace([ //2
		        			'/principals/'.$ouid.'/', //2
		           			'/calendars/'.$ouid.'/', //2
		           			'/contacts/'.$ouid.'/', //2
		           			'displayname>'.$ouid.'<', //2
		        ], [ //2
		        			'/principals/'.$nuid.'/', //2
		        			'/calendars/'.$nuid.'/', //2
		        			'/contacts/'.$nuid.'/', //2
		        			'displayname>'.$nuid.'<', //2
		        ],  self::$_http[HTTP::SND_BODY]); //2

	        } //2

        } //2

        self::$_http[self::SND_HEAD]['Content-Length'] = strlen(self::$_http[self::SND_BODY]);

		return 200;
	}

}

?>