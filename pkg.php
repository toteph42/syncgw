<?php
declare(strict_types=1);

/**
 *  syncgw package list
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

$package = [

	// editions
	'ED01'		=> [
  	'Name'		=> 'Standard Edition',
  	'Files'		=> [

  				# core
  				[ '/syncgw/'	                          						, 1 ],
  				[ '/syncgw/config.ini.php'       			                   	, 0 ],
  				[ '/syncgw/lib/Debug.php'		                     			, 0 ],
  				[ '/.settings'													, 0 ],
  				[ '/.externalToolBuilders'										, 0 ],
  				[ '/downloads'													, 0 ],
  				[ '/packages/'													, 0 ],
  				[ '/test'														, 0 ],
  				[ '/.buildpath'													, 0 ],
  				[ '/.project'													, 0 ],
  				[ '/.htaccess'													, 0 ],
  				[ '/pkg.php'													, 0 ],
				[ '/.git'														, 0 ],
  				[ '/.gitignore'													, 0 ],
  				[ '/syncgw/.gitignore'											, 0 ],
	  			[ '/README.md'													, 0 ],
  				[ '/sync.php'						                            , 1 ],

  				# No CSS ./. JS source files
			 	[ '.src.'								                        , 0 ],

  				# developer GUI extensions
			 	[ '/syncgw/gui/guiSwitch.php'		    						, 0 ],
			 	[ '/syncgw/gui/guiTrunc.php'		    						, 0 ],
			 	[ '/syncgw/gui/guiSoftware.php'	        						, 0 ],
			 	[ '/syncgw/gui/guiForceTrace.php'			    				, 0 ],
			 	[ '/syncgw/gui/guiTraceExport.php'			    				, 0 ],

  				# professional edition GUI extension
    			[ '/syncgw/gui/guiStats.php'		      						, 0 ],
  				[ '/syncgw/gui/guiEdit.php'    							        , 0 ],
			 	[ '/syncgw/gui/guiSync.php'		        					    , 0 ],
			 	[ '/syncgw/gui/guiUpload.php'    							    , 0 ],
			 	[ '/syncgw/gui/guiRename.php'	    						    , 0 ],

  				# document handler
  				[ '/syncgw/document/'						    				, 0 ],
  				[ '/syncgw/document/docHandler.php'								, 1 ],
  				[ '/syncgw/document/field/'										, 1 ],

  				# interface support
  				[ '/syncgw/interfaces/'	       				    				, 0 ],
			 	[ '/syncgw/interfaces/DBAdmin.php'		        				, 1 ],
			 	[ '/syncgw/interfaces/DBintHandler.php'		        			, 1 ],
			 	[ '/syncgw/interfaces/DBextHandler.php'		        			, 1 ],

  				# source files
  	  			[ '/syncgw/source/'					   		    				, 0 ],
  	  			[ '/syncgw/source/charset.xml'		   		    				, 1 ],
  	  			[ '/syncgw/source/dev_DAV.xml'		   		    				, 1 ],
  	  			[ '/syncgw/source/dev_MAS.xml'		   		    				, 1 ],
  				[ '/syncgw/source/mime_types.xml'	   		    				, 1 ],
  				[ '/syncgw/source/syncgw.jpg'		   		    				, 1 ],

  				# DAV support start ==================================================
  				[ '/syncgw/dav'		        									, 1 ],
 				[ '/syncgw/ext/Sabre'	       				    				, 1 ],


  				# MIME support
  				[ '/syncgw/document/mime'	    			    				, 0 ],
  				[ '/syncgw/document/mime/mimHandler.php'    				    , 1 ],
  				[ '/syncgw/document/mime/mimRFC2425.php'	    			    , 1 ],
  				[ '/syncgw/document/mime/mimRFC5234.php'		        		, 1 ],
                [ '/syncgw/document/mime/mimRFC6868.php'    				    , 1 ],
    			[ '/syncgw/document/mime/mimPlain.php'	    					, 1 ],
  				[ '/syncgw/document/mime/mimvCard.php'	    					, 1 ],
    			[ '/syncgw/document/mime/mimvCal.php'	    					, 1 ],
    			[ '/syncgw/document/mime/mimvNote.php'	    					, 1 ],
    			[ '/syncgw/document/mime/mimvTask.php'	    					, 1 ],

 				# source files
  	  			# [ '/syncgw/source/'					   		    			, 0 ],
  	  			[ '/syncgw/source/TooBig.png'		   		    				, 1 ],
  				# DAV support end ====================================================

  				# ActiveSync support start ===========================================
 				[ '/syncgw/activesync/'										    , 1 ],

 	            # document handler
  				# [ '/syncgw/document/'						    				, 0 ],
  				[ '/syncgw/document/DocLib.php'									, 1 ],

  				# MIME support
  				[ '/syncgw/document/mime'	    			    				, 0 ],
  				[ '/syncgw/document/mime/mimAs.php'		   						, 1 ],
                [ '/syncgw/document/mime/mimAsDocLib.php'	   					, 1 ],
    			[ '/syncgw/document/mime/mimAsContact.php'		    			, 1 ],
				[ '/syncgw/document/mime/mimAsGAL.php'						   	, 1 ],
  				[ '/syncgw/document/mime/mimAsCalendar.php'				   	    , 1 ],
				[ '/syncgw/document/mime/mimAsTask.php'						   	, 1 ],
				[ '/syncgw/document/mime/mimAsNote.php'						   	, 1 ],
				[ '/syncgw/document/mime/mimAsMail.php'							, 1 ],

   				# code page files
  	  			# [ '/syncgw/source/'					   		    			, 0 ],
  	  			[ '/syncgw/source/cp_airsync.xml'					   		    , 1 ],
  	  			[ '/syncgw/source/cp_airsyncbase.xml'				   		    , 1 ],
  	  			[ '/syncgw/source/cp_contacts.xml'					   		    , 1 ],
				[ '/syncgw/source/cp_contacts2.xml'							    , 1 ],
  	  			[ '/syncgw/source/cp_documentlibrary.xml'			   		    , 1 ],
 	    		[ '/syncgw/source/cp_calendar.xml'					   		    , 1 ],
				[ '/syncgw/source/cp_mail.xml'							   	    , 1 ],
				[ '/syncgw/source/cp_email.xml'		    						, 1 ],
				[ '/syncgw/source/cp_email2.xml'		    					, 1 ],
				[ '/syncgw/source/cp_find.xml'			    					, 1 ],
				[ '/syncgw/source/cp_folderhierarchy.xml'		    			, 1 ],
  				[ '/syncgw/source/cp_gal.xml'							   		, 1 ],
  				[ '/syncgw/source/cp_getitemestimate.xml'				   		, 1 ],
  				[ '/syncgw/source/cp_itemoperations.xml'				   		, 1 ],
  				[ '/syncgw/source/cp_meetingresponse.xml'				   		, 1 ],
  				[ '/syncgw/source/cp_move.xml'							   		, 1 ],
  				[ '/syncgw/source/cp_gal.xml'							   		, 1 ],
  				[ '/syncgw/source/cp_notes.xml'							   	    , 1 ],
 	  			[ '/syncgw/source/cp_ping.xml'							   	    , 1 ],
 	  			[ '/syncgw/source/cp_provision.xml'						   	    , 1 ],
 	  			[ '/syncgw/source/cp_resolvereciepients.xml'			   	    , 1 ],
 	  			[ '/syncgw/source/cp_rightsmanagement.xml'				   	    , 1 ],
  	  			[ '/syncgw/source/cp_search.xml'						   	    , 1 ],
  	  			[ '/syncgw/source/cp_settings.xml'						   	    , 1 ],
  				[ '/syncgw/source/cp_task.xml'							   	    , 1 ],
				[ '/syncgw/source/cp_validatecert.xml'					   	    , 1 ],

  				# source files
 				[ '/syncgw/source/activesync.xml'					   		    , 1 ],
  	  			[ '/syncgw/source/masPolicy.xml'					   		    , 1 ],
  	  			[ '/syncgw/source/masRights.xml'					   		    , 1 ],
  				# ActiveSync support end =============================================

				# MAPI support start =================================================
 				[ '/syncgw/mapi/'										    	, 0 ],
				# MAPI support end ===================================================

				# Mail support start =================================================
  				[ '/syncgw/document/docMail.php'	       				    	, 0 ],
  				[ '/syncgw/ext/PHPMailer/'	       				    			, 0 ],
				# Mail support end ===================================================

  				],
	],

    'ED02'		=> [
    'Name'		=> 'Professional Edition',
    'Files'		=> [

  				# core
  				[ '/syncgw/'	                          						, 1 ],
  				[ '/syncgw/config.ini.php'       			                   	, 0 ],
  				[ '/syncgw/lib/Debug.php'		                     			, 0 ],
  				[ '/.settings'													, 0 ],
  				[ '/.externalToolBuilders'										, 0 ],
  				[ '/downloads'													, 0 ],
  				[ '/packages/'													, 0 ],
  				[ '/test'														, 0 ],
  				[ '/.buildpath'													, 0 ],
  				[ '/.project'													, 0 ],
  				[ '/.htaccess'													, 0 ],
  				[ '/pkg.php'													, 0 ],
				[ '/.git'														, 0 ],
  				[ '/.gitignore'													, 0 ],
  				[ '/syncgw/.gitignore'											, 0 ],
    			[ '/README.md'													, 0 ],
    			[ '/sync.php'						                            , 1 ],

  				# No CSS ./. JS source files
			 	[ '.src.'								                        , 0 ],

  				# developer GUI extensions
			 	[ '/syncgw/gui/guiSwitch.php'		    						, 0 ],
			 	[ '/syncgw/gui/guiTrunc.php'		    						, 0 ],
			 	[ '/syncgw/gui/guiSoftware.php'	        						, 0 ],
			 	[ '/syncgw/gui/guiForceTrace.php'			    				, 0 ],
			 	[ '/syncgw/gui/guiTraceExport.php'			    				, 0 ],

/*
#### Start: Additional GUI plugins for Professional Edition ####
    			# professional edition GUI extension
    			[ '/syncgw/gui/guiStats.php'		      						, 0 ],
  				[ '/syncgw/gui/guiEdit.php'    							        , 0 ],
			 	[ '/syncgw/gui/guiSync.php'		        					    , 0 ],
			 	[ '/syncgw/gui/guiUpload.php'    							    , 0 ],
			 	[ '/syncgw/gui/guiRename.php'	    						    , 0 ],
*/
    			# document handler
  				[ '/syncgw/document/'						    				, 0 ],
  				[ '/syncgw/document/docHandler.php'								, 1 ],
  				[ '/syncgw/document/field/'										, 1 ],

  				# interface support
  				[ '/syncgw/interfaces/'	       				    				, 0 ],
			 	[ '/syncgw/interfaces/DBAdmin.php'		        				, 1 ],
			 	[ '/syncgw/interfaces/DBintHandler.php'		        			, 1 ],
			 	[ '/syncgw/interfaces/DBextHandler.php'		        			, 1 ],

  				# source files
  	  			[ '/syncgw/source/'					   		    				, 0 ],
  	  			[ '/syncgw/source/charset.xml'		   		    				, 1 ],
  	  			[ '/syncgw/source/dev_DAV.xml'		   		    				, 1 ],
  	  			[ '/syncgw/source/dev_MAS.xml'		   		    				, 1 ],
  				[ '/syncgw/source/mime_types.xml'	   		    				, 1 ],
  				[ '/syncgw/source/syncgw.jpg'		   		    				, 1 ],

    			# DAV support start ==================================================
  				[ '/syncgw/dav'		        									, 1 ],
 				[ '/syncgw/ext/Sabre'	       				    				, 1 ],


  				# MIME support
  				[ '/syncgw/document/mime'	    			    				, 0 ],
  				[ '/syncgw/document/mime/mimHandler.php'    				    , 1 ],
  				[ '/syncgw/document/mime/mimRFC2425.php'	    			    , 1 ],
  				[ '/syncgw/document/mime/mimRFC5234.php'		        		, 1 ],
                [ '/syncgw/document/mime/mimRFC6868.php'    				    , 1 ],
    			[ '/syncgw/document/mime/mimPlain.php'	    					, 1 ],
  				[ '/syncgw/document/mime/mimvCard.php'	    					, 1 ],
    			[ '/syncgw/document/mime/mimvCal.php'	    					, 1 ],
    			[ '/syncgw/document/mime/mimvNote.php'	    					, 1 ],
    			[ '/syncgw/document/mime/mimvTask.php'	    					, 1 ],

 				# source files
  	  			# [ '/syncgw/source/'					   		    			, 0 ],
  	  			[ '/syncgw/source/TooBig.png'		   		    				, 1 ],
  				# DAV support end ====================================================

  				# ActiveSync support start ===========================================
 				[ '/syncgw/activesync/'										    , 1 ],

 	            # document handler
  				# [ '/syncgw/document/'						    				, 0 ],
  				[ '/syncgw/document/DocLib.php'									, 1 ],

  				# MIME support
  				[ '/syncgw/document/mime'	    			    				, 0 ],
  				[ '/syncgw/document/mime/mimAs.php'		   						, 1 ],
                [ '/syncgw/document/mime/mimAsDocLib.php'	   					, 1 ],
    			[ '/syncgw/document/mime/mimAsContact.php'		    			, 1 ],
				[ '/syncgw/document/mime/mimAsGAL.php'						   	, 1 ],
  				[ '/syncgw/document/mime/mimAsCalendar.php'				   	    , 1 ],
				[ '/syncgw/document/mime/mimAsTask.php'						   	, 1 ],
				[ '/syncgw/document/mime/mimAsNote.php'						   	, 1 ],
				[ '/syncgw/document/mime/mimAsMail.php'							, 1 ],

   				# code page files
  	  			# [ '/syncgw/source/'				   		    				, 0 ],
  	  			[ '/syncgw/source/cp_airsync.xml'					   		    , 1 ],
  	  			[ '/syncgw/source/cp_airsyncbase.xml'				   		    , 1 ],
  	  			[ '/syncgw/source/cp_contacts.xml'					   		    , 1 ],
				[ '/syncgw/source/cp_contacts2.xml'							    , 1 ],
  	  			[ '/syncgw/source/cp_documentlibrary.xml'			   		    , 1 ],
 	    		[ '/syncgw/source/cp_calendar.xml'					   		    , 1 ],
				[ '/syncgw/source/cp_mail.xml'							   	    , 1 ],
				[ '/syncgw/source/cp_email.xml'		    						, 1 ],
				[ '/syncgw/source/cp_email2.xml'		    					, 1 ],
				[ '/syncgw/source/cp_find.xml'			    					, 1 ],
				[ '/syncgw/source/cp_folderhierarchy.xml'		    			, 1 ],
  				[ '/syncgw/source/cp_gal.xml'							   		, 1 ],
  				[ '/syncgw/source/cp_getitemestimate.xml'				   		, 1 ],
  				[ '/syncgw/source/cp_itemoperations.xml'				   		, 1 ],
  				[ '/syncgw/source/cp_meetingresponse.xml'				   		, 1 ],
  				[ '/syncgw/source/cp_move.xml'							   		, 1 ],
  				[ '/syncgw/source/cp_gal.xml'							   		, 1 ],
  				[ '/syncgw/source/cp_notes.xml'							   	    , 1 ],
 	  			[ '/syncgw/source/cp_ping.xml'							   	    , 1 ],
 	  			[ '/syncgw/source/cp_provision.xml'						   	    , 1 ],
 	  			[ '/syncgw/source/cp_resolvereciepients.xml'			   	    , 1 ],
 	  			[ '/syncgw/source/cp_rightsmanagement.xml'				   	    , 1 ],
  	  			[ '/syncgw/source/cp_search.xml'						   	    , 1 ],
  	  			[ '/syncgw/source/cp_settings.xml'						   	    , 1 ],
  				[ '/syncgw/source/cp_task.xml'							   	    , 1 ],
				[ '/syncgw/source/cp_validatecert.xml'					   	    , 1 ],

  				# source files
 				[ '/syncgw/source/activesync.xml'					   		    , 1 ],
  	  			[ '/syncgw/source/masPolicy.xml'					   		    , 1 ],
  	  			[ '/syncgw/source/masRights.xml'					   		    , 1 ],
  				# ActiveSync support end =============================================

				# MAPI support start =================================================
 				[ '/syncgw/mapi/'										    	, 0 ],
				# MAPI support end ===================================================

				# Mail support start =================================================
  				[ '/syncgw/document/docMail.php'	       				    	, 0 ],
  				[ '/syncgw/ext/PHPMailer/'	       				    			, 0 ],
				# Mail support end ===================================================

  				],
    ],

	// interface handler
	'BE01'		=> [
	'Name' 		=> 'File interface handler',
   	'Files'		=> [
			 	[ '/syncgw/interfaces/file/'  									, 1 ],
     			],
    ],
    'BE02'		=> [
    'Name'		=> 'MySQL interface handler',
    'Files'		=> [
			 	[ '/syncgw/interfaces/mysql/'									, 1 ],
    		    ],
    ],
    'BE03'		=> [
    'Name'		=> 'RoundCube (including MySQL) interface handler',
    'Files'		=> [
			 	[ '/syncgw/interfaces/mysql/'									, 1 ],
    			[ '/syncgw/interfaces/roundcube/'								, 1 ],
			    ],
    ],
    'BE04'		=> [
    'Name'		=> 'Simple notes interface handler',
    'Files'		=> [
			 	[ '/syncgw/interfaces/mysql/'									, 1 ],
    			[ '/syncgw/interfaces/myapp/'									, 1 ],
				],
    ],

	// data stores
    'DS01'		=> [
    'Name'		=> 'Contact handler',
    'Files'		=> [

    			# document handler
  				[ '/syncgw/document/docContact.php'			 					, 1 ],
			 	[ '/syncgw/document/docGAL.php'			 						, 1 ],

    			],
    ],
    'DS02'		=> [
    'Name'		=> 'Calendar and task handler',
    'Files'		=> [

    			# document handler
  				[ '/syncgw/document/docCalendar.php'		       				, 1 ],
			 	[ '/syncgw/document/docTask.php'						   		, 1 ],

     			],
	],
	'DS03'		=> [
    'Name'		=> 'Note handler',
	'Files'		=> [

				# document handler
  				[ '/syncgw/document/docNote.php'							   	, 1 ],

				],
    ],


];
$package; //3 prevent Eclipse warning

?>