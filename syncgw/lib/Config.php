<?php
declare(strict_types=1);

/*
 *  Configuration handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class Config {

	const VER 				= 27;							// module version number

	const CONFIG            = 'config.ini.php';				// configuration file name

	// configuration paremeters accessible by GUI_Config.php and Admin.php
	const ADMPW				= 'AdminPassword';				// administrator passwort
	const LANG				= 'Language';					// language setting
	const CRONJOB			= 'CronJob';					// use cron job for expiration handling
	const LOG_FILE			= 'LogFile';					// log file name prefix
	const LOG_LVL			= 'LogLevel';					// log level
	const LOG_EXP			= 'LogFileExp';					// log file expiration
	const TRACE_MOD			= 'TraceMod';					// trace modus
	const TRACE_DIR			= 'TraceDir';					// trace directory
	const TRACE_EXP 		= 'TraceExpiration';			// trace file expiration
	const TRACE_EXC			= 'TraceException';				// trace SabreDAV exceptions
	const SESSION_TIMEOUT	= 'SessionTimeout';				// session timeout
	const SESSION_EXP		= 'SessionExp';					// session record expiration
	const PHPERROR			= 'PHPError';					// capture PHP fatal errors
	const ENABLED			= 'Datastores';					// enabled data stores
	const UPGRADE			= 'Upgrade';					// update level
	const DATABASE			= 'Database';					// data base connection

	const MAXOBJSIZE        = 'MaxObjectSize';				// max. object size for DAV (e.g. attachments)

	const HEARTBEAT			= 'HeartBeat';					// ActiveSync: Max. heatbeat in seconds
	const PING_SLEEP        = 'PingSleep';					// ActiveSync: Max. sleep time during <Ping> processing

	// "file" handler parameter
	const FILE_DIR			= 'FileDirectory';				// "file" handler parameter

	// "mysql" handler parameter
	const DB_HOST			= 'MySQLHost';					// host name
	const DB_PORT			= 'MySQLPort';					// post number
	const DB_USR			= 'MySQLUser';					// user name
	const DB_UPW			= 'MySQLPassword';				// password
	const DB_NAME			= 'MySQLDatabase';				// data base
	const DB_PREF			= 'MySQLPrefix';				// syncgw table name prefix
	const DB_RSIZE          = 'MySQLSize';					// max. data base record size
	const DB_RETRY 			= 'MySQLRetry';					// "[2006] MySQL server has gone away" repeation max

	// "RoundCube" handler parameter
	const RC_DIR			= 'RCDirectory';				// directory where code is located

	// "mail" handler parameter
	const CON_TIMEOUT 		= 'ConnectionTimeout';			// connection test timeout
	const MAILER_ERR 		= 'MailerError';				// throw external exceptions in PHPMAILER

	const IMAP_HOST         = 'ImapHost';					// the IMAP server to get connected
    	const IMAP_PORT     = 'ImapPort';					// TCP port to connect to
    	const IMAP_ENC      = 'ImapEncryption';				// encryption to use
    	const IMAP_CERT     = 'ImapValidateCert';			// validate certificate

	const SMTP_HOST         = 'SMTPHost';					// the SMP server to get connected
    	const SMTP_PORT     = 'SMTPPort';					// TCP port to connect to
    	const SMTP_AUTH     = 'SMTPAuth';					// encryption to use
    	const SMTP_ENC      = 'SMTPEncryption';				// encryption to use
		const SMTP_DEBUG	= 'SMTPDebug';					// SMTP class debug output mode

    // internal configuration paremeters
	const FULLVERSION		= 'FullVersion';				// syncgw full version
	const VERSION			= 'Version';					// syncgw version
	const TYPE				= 'Type';						// syncgw type
    const EXECUTION			= 'MaxExecutionTime';			// max. PHP execution time
	const SOCKET_TIMEOUT	= 'SocketTimeout';				// Default timeout for socket based streams.
    const TMP_DIR			= 'TmpDir';						// temporary directory
	const TRACE 			= 'Trace';						// trace status
		const TRACE_OFF		= 0x01;							// trace is off
		const TRACE_ON		= 0x02;							// trace is on
		const TRACE_FORCE	= 0x04;							// forced trace is running
	const TIME_ZONE			= 'Timezone';					// default system time zone

	const BASEURI			= 'BaseUri';					// base URI

	const HACK				= 'Hack';						// hack bit field
		const HACK_SIZE     = 0x0001;						// do NOT limit attachment size
		const HACK_NOKIA	= 0x0002;						// general Nokia
		const HACK_CDAV		= 0x0004;						// special hack for CardDAV-Sync (Android) (Only for 0.3.8.2 required)
		const HACK_WINMAIL	= 0x0008;						// windows mail program

	const DBG_LEVEL			= 'DebugLevel'; 				// debug level											//2
		const DBG_OFF		= '0';							// off													//2
		const DBG_VIEW		= '1';							// view trace record									//2
		const DBG_TRACE		= '2';							// process trace data									//2
	const DBG_USR			= 'DebugUser'; 					// debug user (Professional Edition only) 				//2
	const DBG_UPW			= 'DebugPassword'; 				// debug user password (Professional Edition only) 		//2
	const DBG_DIR           = 'DebugDirectory'; 			// debug directory 										//3
	const DBG_CLASS			= 'DebugClass'; 				// which classes to debug 								//2

	// handler processing class
	const HD				= 'Handler';					// MAS=ActiveSync, DAV=WebDAV,
															// GUI=Browser Interface. MAPI=MAPI over HTTP
	const HTTP_CHUNK        = 'SendSize';					// max. of bytes (1 MB) send in one chunk
	const FORCE_TASKDAV     = 'TaskDAV';					// force WebDAV task list synchronization

	// value types
    const VAL_TYP           = 0;							// type of value
	const VAL_DEF			= 1;							// default value
	const VAL_POSS			= 2;							// possible value
	const VAL_NAME			= 3;							// constant name
	const VAL_ORG			= 4;							// orginal value
	const VAL_CURR			= 5;							// current value
	const VAL_SAVE          = 6;							// save to .ini file

 	/**
	 * 	Configuration definition array<fieldset>
	 *	@var array
	 */
	private $_conf;

    /**
     * 	Singleton instance of object
     * 	@var Config
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Config {

		if (!self::$_obj) {
            self::$_obj = new self();

			// set default configuration definitions
			// VAL_TYP:  0 - String;  1 - Integer
		 	// VAL_DEF:  Default value
			// VAL_POSS: [ Possible values ]
		 	// VAL_NAME: Constant name
		    // VAL_ORG:  Original loaded value
		    // VAL_CURR: Current value
		    // VAL_SAVE: 1 - Save to .INI file
			self::$_obj->_conf = [

				self::ADMPW				=> [ 0, NULL, [], NULL, [], [], 1                                               ],
				self::LANG				=> [ 0, 'English;en_US', [], 'LANG', [], [], 1                                  ],
				self::CRONJOB			=> [ 0, 'N', [ 'Y', 'N' ], 'CRONJOB', [], [], 1	                                ],
				self::TRACE				=> [ 1, self::TRACE_OFF, [ self::TRACE_OFF, self::TRACE_ON, self::TRACE_FORCE,
																   self::TRACE_ON|self::TRACE_FORCE ], NULL, [], [], 0  ],
				self::TRACE_MOD			=> [ 0, 'Off', [], NULL, [], [], 1                                              ],
				self::TRACE_DIR			=> [ 0, NULL, [], NULL, [], [], 1		                                        ],
				self::TRACE_EXP			=> [ 0, 24, [], 'TRACE_EXP', [], [], 1     		                                ],
				self::TRACE_EXC			=> [ 0, 'N', [ 'Y', 'N' ], 'TRACE_EXC', [], [], 1     		                    ],
				self::TIME_ZONE			=> [ 0, date_default_timezone_get(), [], 'TIME_ZONE', [], [], 0 		        ],
				self::SESSION_TIMEOUT	=> [ 1, 10, [], 'SESSION_TIMEOUT', [], [], 1                                    ],
				self::SESSION_EXP		=> [ 0, 24, [], 'SESSION_EXP', [], [], 1                   			            ],
				self::LOG_FILE          => [ 0, 'Off', [], NULL, [], [], 1                                              ],
				self::LOG_LVL			=> [ 1, Log::ERR, [], 'LOG_LVL', [], [], 1       								],
				self::LOG_EXP 			=> [ 1, 7, [], 'LOG_EXP', [], [], 1                                             ],
				self::PHPERROR			=> [ 0, 'Y', [ 'Y', 'N' ], 'PHPERROR', [], [], 1                                ],
				self::DATABASE			=> [ 0, NULL, [], 'DATABASE', [], [], 1                                         ],
			    self::MAXOBJSIZE        => [ 1, 1024000, [], 'MAXOBJSIZE', [], [], 1                                    ],
			    self::ENABLED			=> [ 1, DataStore::DATASTORES, [], 'ENABLED', [], [], 1                         ],

				self::FULLVERSION		=> [ 0, 0, [], 'FULLVERSION', [], [], 0                                         ],
			    self::VERSION			=> [ 0, 0, [], 'VERSION', [], [], 0                                             ],
				self::TYPE				=> [ 0, 0, [], 'TYPE', [], [], 0                                                ],
				self::UPGRADE			=> [ 0, '0.00.00', [], 'UPGRADE', [], [], 1                                     ],

				self::TMP_DIR           => [ 0, NULL, [], NULL, [], [], 1                                               ],
				self::EXECUTION			=> [ 1, 910, [], 'EXECUTION', [], [], 1											],
				self::SOCKET_TIMEOUT	=> [ 1, 60, [], 'SOCKET_TIMEOUT', [], [], 1										],
				self::BASEURI 			=> [ 0, NULL, [], 'BASEURI', [], [], 1                                          ],
				self::HACK				=> [ 1, 0, [], 'HACK', [], [], 0                                  ],

				self::DBG_LEVEL			=> [ 1, self::DBG_OFF, [ self::DBG_OFF, self::DBG_VIEW, 						   //2
											 self::DBG_TRACE ], NULL, [], [], 0  										], //2
				self::DBG_USR			=> [ 0, NULL, [], NULL, [], [], 1                                               ], //2
				self::DBG_UPW			=> [ 0, NULL, [], NULL, [], [], 1                                               ], //2
				self::DBG_DIR			=> [ 0, NULL, [], NULL, [], [], 1                                               ], //3
				self::DBG_CLASS			=> [ 0, NULL, [], NULL, [], [], 1  				   								], //2

				// force WebDAV task list synchronization
				self::FORCE_TASKDAV     => [ 0, NULL, [], 'FORCE_TASKDAV', [], [], 1                                    ],

			    // ActiveSync
				self::PING_SLEEP        => [ 1, 60, [] , 'PING_SLEEP', [], [], 1                                        ],
				self::HEARTBEAT			=> [ 1, 900, [], 'HEARTBEAT', [], [], 1                                         ],

			    // HTTP chunk size
			    self::HTTP_CHUNK        => [ 1, 1000000, [], 'HTTP_CHUNK', [], [], 1                                    ],

				// connection test timeout
				self::CON_TIMEOUT		=> [ 1, 5, [], NULL, [], [], 0                                                  ],

				// "file" handler parameter
				self::FILE_DIR			=> [ 0, NULL, [], NULL, [], [], 1                                               ],

				// "mysql" handler parameter
				self::DB_HOST			=> [ 0, 'localhost', [], NULL, [], [], 1                                        ],
				self::DB_PORT			=> [ 1, 3306, [], NULL, [], [], 1                                               ],
				self::DB_USR			=> [ 0, NULL, [], NULL, [], [], 1                                               ],
				self::DB_UPW			=> [ 0, NULL, [], NULL, [], [], 1                                               ],
				self::DB_NAME			=> [ 0, NULL, [], NULL, [], [], 1                                               ],
				self::DB_PREF			=> [ 0, 'syncgw', [], '', [], [], 1                                             ],
				self::DB_RETRY			=> [ 1, 10, [], NULL, [], [], 1                                                 ],

			    // database record size (10 MB)
			    self::DB_RSIZE         	=> [ 1, 10485760, [], 'DB_RSIZE', [], [], 1                                     ],

				// "RoundCube" handler parameter
				self::RC_DIR			=> [ 0, '.', [], NULL, [], [], 1                                                ],

				// "PHPMAILER" handler parameter
				self::MAILER_ERR		=> [ 1, 0, [ 0, 1 ], '', [], [], 1                               				],

				// IMAP configuration parameter
			    self::IMAP_HOST         => [ 0, 'localhost', [], 'IMAP_HOST', [], [], 1                                 ],
			    self::IMAP_PORT		    => [ 1, 143, [ 143, 993 ], 'IMAP_PORT', [], [], 1		                        ],
				self::IMAP_ENC			=> [ 0, NULL, [ NULL, 'TLS', 'SSL' ], 'IMAP_ENC', [], [], 1                     ],
				self::IMAP_CERT			=> [ 0, 'Y', [ 'Y', 'N' ], 'IMAP_CERT', [], [], 1                               ],

			    // SMTP configuration parameter
			    self::SMTP_HOST         => [ 0, 'localhost', [], 'SMTP_HOST', [], [], 1                                 ],
			    self::SMTP_PORT		    => [ 1, 25, [ 25, 587, 465, 2525 ], 'SMTP_PORT', [], [], 1                      ],
				self::SMTP_AUTH			=> [ 0, 'N', [ 'Y', 'N' ], 'SMTP_AUTH', [], [], 1		                        ],
				self::SMTP_ENC			=> [ 0, NULL, [ NULL, 'TLS', 'SSL' ], 'SMTP_ENC', [], [], 1                     ],
				self::SMTP_DEBUG		=> [ 1, 0, [ 0, 1, 2, 3, 4 ], '', [], [], 1                               		],

				// internal flags
				self::HD				=> [ 0, NULL, [ NULL, 'MAS', 'MAS', 'DAV', 'GUI', 'MAPI', ], NULL, [], [], 0	],
			];

			// set temp. directory
			if (!strlen(self::$_obj->_conf[self::TMP_DIR][self::VAL_DEF] = ini_get('upload_tmp_dir')))
				self::$_obj->_conf[self::TMP_DIR][self::VAL_DEF] = sys_get_temp_dir();
			self::$_obj->_conf[self::TMP_DIR][self::VAL_DEF] .= DIRECTORY_SEPARATOR;

			// set default values
			foreach (self::$_obj->_conf as $k => $v)
				self::$_obj->_conf[$k][self::VAL_ORG] = self::$_obj->_conf[$k][self::VAL_CURR] = $v[self::VAL_DEF];

			// set messages 10701-10800
			$log = Log::getInstance();
			$log->setMsg( [
				10701 => _('Error writing to directory [%s]. Please change user permission settings'),
				10702 => _('Error writing file [%s]'),
				10703 => _('Invalid configuration parameter \'%s\''),
				10704 => 10703,
				10705 => 10703,
				10706 => 10703,
				10707 => 10703,
				10708 => _('Invalid value \'%s\' for configuration parameter \'%s\''),
				10709 => _('Error setting PHP.INI setting \'%s\' from \'%s\' to \'%s\''),
			]);

			// "normalize" time zone
			date_default_timezone_set('UTC');

			// filter function messages
			ErrorHandler::filter(E_WARNING, '', 'unlink');

			// load configuration
			self::$_obj->loadConf();
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

		$xml->addVar('Name', _('Configuration handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status)
			return;

		$xml->addVar('Opt', _('INI file support'));
		$xml->addVar('Stat', _('Implemented'));
	}

	/**
	 * 	Load configuration
	 *
	 * 	@param	- Optional configuration array()
	 */
	public function loadConf(array $conf = NULL): void {

		$log = Log::getInstance();

		if (!$conf) {
            // load .INI file
		    $fnam = Util::mkPath(self::CONFIG);
			if (file_exists($fnam)) {
                $c = @parse_ini_file($fnam);
                // load type and version
                if (Debug::$Conf['Script']) //3
                	$this->_conf[self::FULLVERSION][self::VAL_ORG] = $this->_conf[self::FULLVERSION][self::VAL_CURR] = 'Professional Edition x.xx.xx'; //3
                else //3
					if (($this->_conf[self::FULLVERSION][self::VAL_ORG] = $this->_conf[self::FULLVERSION][self::VAL_CURR] =
							@file_get_contents(Util::mkPath().'syncgw.pkg')) === FALSE)
						$log->Msg(Log::WARN, 10703, self::FULLVERSION);
				if (!($p = strrpos($this->_conf[self::FULLVERSION][self::VAL_ORG], ' ')))
					$log->Msg(Log::WARN, 10704, self::VERSION);
				$this->_conf[self::VERSION][self::VAL_ORG] = $this->_conf[self::VERSION][self::VAL_CURR] =
									substr($this->_conf[self::FULLVERSION][self::VAL_ORG], $p + 1);
				if (!($this->_conf[self::TYPE][self::VAL_ORG] = $this->_conf[self::TYPE][self::VAL_CURR] =
									substr($this->_conf[self::FULLVERSION][self::VAL_ORG], 0, $p)))
					$log->Msg(Log::WARN, 10705, self::TYPE);
			} else
			    $c = NULL;
		} else
			$c = $conf;

		if (is_array($c)) {
			// swap data
		    foreach ($c as $k => $v) {
		        if (!isset($this->_conf[$k]))
		            continue;
                if (substr($k, 0, 4) != 'Usr_' && $this->_conf[$k][self::VAL_TYP])
		            $v = intval($v);
			    $this->_conf[$k][self::VAL_ORG] = $this->_conf[$k][self::VAL_CURR] = $v;
		    }
		}

		// set max. execution timer to 5 minutes
		@set_time_limit($this->_conf[self::EXECUTION][self::VAL_ORG]);

		// set socket timeout
		@ini_set('default_socket_timeout', strval($this->_conf[self::SOCKET_TIMEOUT][self::VAL_ORG]));

		// configure error logging
		ErrorHandler::resetReporting();

		// rewrite config file?
		if ($conf && $this->_conf[self::DATABASE][self::VAL_ORG]) {
			self::updVar(self::DATABASE, $this->_conf[self::DATABASE][self::VAL_ORG]);
			self::saveINI();
		}

		self::updVar(self::DBG_USR, 	Debug::$Conf['DebugUID']); //3
		self::updVar(self::DBG_UPW, 	Debug::$Conf['DebugUPW']); //3
		self::updVar(self::IMAP_HOST, 	Debug::$Conf['Imap-Host']); //3
		self::updVar(self::IMAP_PORT, 	Debug::$Conf['Imap-Port']); //3
		self::updVar(self::IMAP_ENC, 	Debug::$Conf['Imap-Enc']); //3
		self::updVar(self::IMAP_CERT, 	Debug::$Conf['Imap-Cert']); //3
		self::updVar(self::SMTP_HOST, 	Debug::$Conf['SMTP-Host']); //3
		self::updVar(self::SMTP_PORT, 	Debug::$Conf['SMTP-Port']); //3
		self::updVar(self::SMTP_ENC, 	Debug::$Conf['SMTP-Enc']); //3
		self::updVar(self::SMTP_AUTH, 	Debug::$Conf['SMTP-Auth']); //3
	}

	/**
	 * 	Get configuration parameter
	 *
	 * 	@param 	- Parameter name or NULL for complete active configuration
	 * 	@param 	- TRUE=Get original value; FALSE=Get current (default)
	 * 	@return - Configuration value or NULL
	 */
	public function getVar(?string $id, bool $org = FALSE) {

		// get definitions?
		if (!$id) {
			$a = [];
			foreach ($this->_conf as $k => $v) {
				if ($v[self::VAL_NAME])
					$a['Config::'.$v[self::VAL_NAME]] = $this->_conf[$k][self::VAL_CURR];
			}
			Debug::Msg($a, 'Get configuration definition array()'); //3
			return $a;
		}

		if (!isset($this->_conf[$id][$org ? self::VAL_ORG : self::VAL_CURR])) {
			// no defaults for user defined variables
			if (substr($id, 0, 4) == 'Usr_')
				return NULL;
			if (!isset($this->_conf[$id])) {
				$log = Log::getInstance();
				$log->Msg(Log::WARN, 10706, $id);
				return NULL;
			}
			Debug::Msg('['.$id.'] = "'.$this->_conf[$id][self::VAL_DEF].'"'); //3
			return $this->_conf[$id][self::VAL_DEF];
		}

		Debug::Msg('['.$id.'] = "'.$this->_conf[$id][$org ? self::VAL_ORG : self::VAL_CURR].'"'); //3

		return $this->_conf[$id][$org ? self::VAL_ORG : self::VAL_CURR];
	}

	/**
	 * 	Update configuration variable
	 *
	 * 	@param 	- Parameter name
	 * 	@param 	- New value
	 * 	@return - Current value
	 */
	public function updVar(string $id, $val) {

		Debug::Msg('['.$id.'] = "'.$val.'"'); //3

		$old = strval(isset($this->_conf[$id]) ? $this->_conf[$id][self::VAL_CURR] : '');

		// allow user defined variables
		if (substr($id, 0, 4) == 'Usr_') {
			$this->_conf[$id][self::VAL_CURR] = $val;
			$this->_conf[$id][self::VAL_SAVE] = 1;
			return $old;
		}

		if (!isset($this->_conf[$id])) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10707, $id);
			return $old;
		}

		// check parameter
		if (count($this->_conf[$id][self::VAL_POSS])) {
			$f = FALSE;
			foreach ($this->_conf[$id][self::VAL_POSS] as $v) {
				if ($val == $v) {
					$this->_conf[$id][self::VAL_CURR] = $this->_conf[$id][self::VAL_TYP] ? intval($val) : $val;
					$f = TRUE;
					break;
				}
			}
			if (!$f) {
				$log = Log::getInstance();
				$log->Msg(Log::WARN, 10708, $val, $id);
				return $old;
			}
		} else
			$this->_conf[$id][self::VAL_CURR] = $this->_conf[$id][self::VAL_TYP] ? intval($val) : $val;

		if ($id == self::EXECUTION)
			set_time_limit($this->_conf[$id][self::VAL_CURR]);

		return $old;
	}

	/**
	 * 	Save configuration table
	 *
	 * 	@return - TRUE=Ok or FALSE=Error
	 */
	public function saveINI(): bool {

		// create new .ini file
		$wrk  = ';<?php die(); ?>'."\n";
		$fnam = Util::mkPath(self::CONFIG);

   		foreach ($this->_conf as $k => $v) {
   			if (!$v[self::VAL_SAVE] || !isset($v[self::VAL_CURR]))
       			continue;
   			$wrk .= $k.' = "'.$v[self::VAL_CURR].'"'."\n";
		}

		if (@file_exists($fnam) && !@is_writeable($fnam)) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10701, Util::mkPath());
			return FALSE;
		}

		if (!@file_put_contents($fnam, $wrk)) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 10702, $fnam);
			return FALSE;
		}

		return TRUE;
	}

}

?>