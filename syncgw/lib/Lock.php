<?php
declare(strict_types=1);

/*
 * 	Lock functions class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class Lock {

	// module version number
	const VER = 8;

 	/**
     *  Lock file buffer
     *  @var array
     */
    private $_lock = [];

    /**
     * 	Singleton instance of object
     * 	@var Lock
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Lock {

		if (!self::$_obj) {
            self::$_obj = new self();

			$log = Log::getInstance();
	    	$log->setMsg([
	           	    11501 => _('Lock error on [%s] - "%s(%s): %s"'),
	    	]);

			// register shutdown function
			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);
		}

		return self::$_obj;
	}

 	/**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		if (!self::$_obj)
			return;

		foreach (self::$_obj->_lock as $unused => $lock) {
		    fclose($lock[0]);
   		    unlink($lock[1]);
        }
		$unused; // disable Eclipse warning

		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {
		$xml->addVar('Name', _('Locking handler'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 *  Create lock
	 *
	 *  @param  - Lock string
	 *  @param  - TRUE = Wait to get lock; FALSE = Do not wait
	 *  @return - TRUE = Ok; FALSE = Error
	 */
	public function lock(string $name, bool $wait = FALSE): bool {

	    // get file name
	    $cnf  = Config::getInstance();
	    $path = $cnf->getVar(Config::TMP_DIR).Util::normFileName($name.'.lock');

        do {
	        if (($fp = @fopen($path, 'c')) === FALSE) {
    			$err = error_get_last();
    			// special hack for windows
    			if (stripos($err['message'], 'Permission denied') === FALSE) {
	    			$log = Log::getInstance();
    				$log->Msg(Log::WARN, 11501, $path, $err['file'], $err['line'], $err['message']);
				    return FALSE;
    			}
    			Util::Sleep();
    	    }
        } while (!is_resource($fp));

       	if (@flock($fp, $wait ? LOCK_EX : LOCK_EX|LOCK_NB)) {
	    	// save lock data
    		$this->_lock[$name] = [ $fp, $path ];
	        return TRUE;
       	}

       	return FALSE;
	}

	/**
	 *  Unlock
	 *
	 *  @param  - Lock string
	 */
	public function unlock(string $name): void {

	    if (isset($this->_lock[$name])) {
	        // unlock file
	        flock($this->_lock[$name][0], LOCK_UN);
	        // close lock file
	        fclose($this->_lock[$name][0]);
	        // try to delete file
        	unlink($this->_lock[$name][1]);
            // delete entry
	        unset($this->_lock[$name]);
	    } else {
            $path = Util::getTmpFile('lock', $name);
	        // try to delete file
           	unlink($path);
	    }
	}

}

?>