<?php
declare(strict_types=1);

/*
 *  Administration interface handler interface definition
 *
 *	@package	sync*gw
 *	@subpackage	Data base
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces;

interface DBAdmin {

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance();

	/**
	 * 	Get installation parameter
	 */
	public function getParms(): void;

	/**
	 * 	Connect to handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	*/
	public function Connect(): bool;

	/**
	 * 	Disconnect from handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	 */
	public function DisConnect(): bool;

	/**
	 * 	Return list of supported data store handler
	 *
	 * 	@return - Bit map of supported data store handler
 	 */
	public function SupportedHandlers(): int;

}

?>