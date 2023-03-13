<?php
declare(strict_types=1);

/*
 * 	fld handler class
 *
 *	@package	sync*gw
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\Debug;use syncgw\lib\Util;
use syncgw\lib\XML;

class fldHandler {

	/**
	 *  Test import
	 *
	 *	@patam	- Pointer to field object
	 *  @param  - TRUE=Should work; FALSE=Should fail
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - External document
	 *  @param  - Internal document as string
	 *  @return - New internal record or FALSE=Skipped
	 *
	 */
	public function testImport($class, bool $mod, string $typ, float $ver, string $xpath, $ext, string $cmp) {									//3
		$int = new XML(); 																											   		$int->loadXML('<syncgw><Data/></syncgw>');	 																					//3
		$int->getVar('Data'); 																											//3
		Debug::Msg(''.str_repeat('-', 49).' Import should '.(!$mod ? 'NOT ' : '').'work'); 												//3
		if (strpos($typ, 'activesync') !== FALSE)																						//3
			$ext->getVar('ApplicationData');																							//3
		elseif (strpos($typ, 's4j') !== FALSE)																							//3
			$ext->getVar('syncgw');																										//3
		elseif (strpos($typ, 'omads') !== FALSE)																						//3
			$ext->getVar('Folder');																										//3
		Debug::Msg($ext, 'Input document'); 																							//3
		if ($class->import($typ, $ver, $xpath, $ext, '', $int)) { 																		//3
			if (!$mod) 																													//3
				msg('+++ Import unexpectly succeeded for "'.get_class($class).'"', Util::CSS_ERR); 										//3
			$int->getVar('Data'); 																										//3
			Debug::Msg($int, 'Internal document'); 																						//3
			if ($cmp) {																													//3
				ob_start();																												//3
 				print_r($int->saveXML(FALSE, TRUE));																					//3
 				$arr1 = ob_get_contents();																								//3
 				ob_end_clean();																											//3
		        $xml = new XML();																									   		        $xml->loadXML($cmp);																									//3
				$xml->getVar('Data'); 																									//3
 				ob_start();																												//3
				print_r($xml->saveXML(FALSE, TRUE));																					//3
   				$arr2 = ob_get_contents();																								//3
	   			ob_end_clean();																											//3
	   			$rc = Util::diffArray(explode("\n", $arr1), explode("\n", $arr2));													   				if ($rc[0] > 0) {																										//3
					msg('+++ Import #1 failed for "'.get_class($class).'"', Util::CSS_ERR);  											//3
					echo $rc[1];																										//3
					return FALSE;																										//3
				}																														//3
			}																															//3
			return $int; 																												//3
		} elseif ($mod)  																												//3
			msg('+++ Import #2 failed for "'.get_class($class).'"', Util::CSS_ERR);  													//3
		return FALSE;  																													//3
	} 	 																																//3

	/**
	 *  Test export
	 *
	 *	@patam	- Pointer to field object
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - Internal document
	 *  @param 	- External document
	 *  @return - TRUE=Ok; FALSE=Error
	 *
	 */
	public function testExport($class, string $typ, float $ver, string $xpath, XML &$int, $cmp): bool { 										//3
	    $ext= new XML();  																											   		$ext->loadXML('<syncgw><Data/></syncgw>');  																					//3
		$ext->getVar('Data');  																											//3
		Debug::Msg(''.str_repeat('-', 49).' Export should work');  																		//3
		if (is_array($rc = $class->export($typ, $ver, '', $int, $xpath, $ext))) {  														//3
			Debug::Msg($rc, 'Output document');  																						//3
			if ($cmp) {																													//3
				ob_start();																												//3
   				print_r($rc);																											//3
   				$arr1 = ob_get_contents();																								//3
	   			ob_end_clean();																											//3
		        if (strstr($cmp[0]['T'], '/')) {																						//3
		        	$t = explode('/', $cmp[0]['T']);																					//3
		        	$cmp[0]['T'] = array_pop($t);																						//3
				}																														//3
	   			ob_start();																												//3
		        print_r($cmp);																											//3
   				$arr2 = ob_get_contents();																								//3
	   			ob_end_clean();																											//3
				$rc = Util::diffArray(explode("\n", $arr1), explode("\n", $arr2));													   				if ($rc[0] > 0) {																										//3
					msg('+++ Export #1 failed for "'.get_class($class).'"', Util::CSS_ERR);  											//3
					echo $rc[1];																										//3
					return FALSE;																										//3
				}																														//3
			} 	 																														//3
			return TRUE;																												//3
		} elseif ($rc !== FALSE) {  																									//3
			$ext->getVar('Data');  																										//3
			Debug::Msg($ext, 'Output document');  																						//3
			if ($cmp) {																													//3
				ob_start();																												//3
  				print_r($ext->saveXML(FALSE, TRUE));																					//3
  				$arr1 = ob_get_contents();																								//3
	   			ob_end_clean();																											//3
		        $cmp->getVar('Data');																									//3
   				ob_start();																												//3
		        print_r($cmp->saveXML(FALSE, TRUE));																					//3
   				$arr2 = ob_get_contents();																								//3
	   			ob_end_clean();																											//3
				$rc = Util::diffArray(explode("\n", $arr1), explode("\n", $arr2));												  	   				if ($rc[0] > 0) {																										//3
					msg('+++ Export #2 failed for "'.get_class($class).'"', Util::CSS_ERR);  											//3
					echo $rc[1];																										//3
					return FALSE;																										//3
				}																														//3
			}																															//3
			return TRUE;																												//3
		} else  																														//3
			msg('+++ Export #3 failed for "'.get_class($class).'"', Util::CSS_ERR);  													//3
		return FALSE;  																													//3
	}  																																	//3

}

?>