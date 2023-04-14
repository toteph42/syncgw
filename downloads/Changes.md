## Version 9.19.67, published on 2023-04-14 ##
* Fixed: Bug in recurring event handling
* Fixed: TRACE_MOD now survives upgrade handling
* Fixed: Typo in docLib.php
* Changed: fldRecurrence::regenerate() made static
* Changed: ActiveSync <ResolveReciepients> now supports debug user during trace replay execution
* Changed: ActiveSync <ResolveReciepients> now searches all primary and secondary e-mail addresses
* Changed: Creating work around for calendar plugin bug due to missing support offerings
* Changed: Catching invalid code page selection in ActiveSync WBXML
* Added: Added "noindex" tag to GUI to prevent search engines to crawl
* Changed: guiFeature.php integrated into guiSoftware.php
* Changed: mysql schema in Task roundcube interface handler
* Changed: Sabre class loader
* Changed: All day events calendar plugin bug fixed (reports wrong end time)

## Version 9.19.62, published on 2023-03-22 ##
* Fixed: Minor bug in dayligth saving conversion
* Added: Util::mkTZOffset()
* Changed: Required Calendar plugin version now 3.5.11
* Changed: Required Tasklist plugin version now 3.5.10
* Changed: Repeating completed task are now regenerated (fix issue #1)
* Changed: A COMPLETED task is set to 100% work is done
* Changed: Rearraged list of GUI extension
* Changed: Added some MIME types to devMAS device skeleton to enable down- and upload of data records
* Fixed: Enable input of IP and user name in configuration for tracing purposes
* Fixed: Some typos in field handler
* Changed: Connected to plugin syncgw_rc
* Fixed: Catching error when TRACE_DIR is set wrong
* Changed: Creating now raw rlease files to support direct downloading
* Changed: PHPMailer upgraded to version 6.8.0

## Version 9.19.49, published on 2023-02-27 ##
* Added: Merge of all address books to default address book
* Added: <IMAPLoginName>, <SMPTLoginName> and <AccountName> added to user object
* Added: GUI upload plugin enhanced to support upload of *.jpg file as photo for user 
* Removed: Special hacks for "Internet Explorer"
* Changed: XML::setParent() returns FALSE, if no further parent is available
* Added: XML:dupVar() duplicates node
* Fixed: XML::setName() now also perserve attributes 
* Changed: PHPMailer upgraded to version 6.7.1
* Fixed: Login error for stdandard user in GUI
* Changed: GUI help destination URL changed
* Removed: General SyncML support
* Removed: SyncML synchronization modus selection
* Removed: SyncML authentication modus
* Removed: SyncML client override modus
* Changed: testclass() function for fields move to seperate test directory
* Changed: Code cleanup

## Version 9.19.66, published on 2022-09-26 ##
* Changed: Error handling in RoundCube data base connector enhanced
* Fixed: ActiveSync <Reminder> time not always exported properly
* Changed: Test scripts and data reworked
* Fixed: Translation of contact groups to user selected language in RoundCube
* Fixed: Modification of recurring event not always exchanged properly
* Fixed: Record loading in mail handler
* Changed: Upgraded to PHPMailer 6.6.4
* Changed: New internal configuration variables for mail handler
* Changed: Mail handler now always return read status of mail
* Changed: ActiveSync <NativeBodyType> code changed to AirSyncBase for e-Mail handler
* Fixed: ActiveSync <ResolveRecipients> only returns availability time if requested in <Options>
* Changed: Catching MySQL error "2006 MySQL server has gone away" and retrying after given time period
* Changed: Some messages updated
* Changed: Limited max. object size during trace viewing or debugging to 3MB
* Fixed: E-Mail addresses with mutated vowel in name
* Fixed: Adding missing start time in RoundCube task recurrence if only <DueDate> is given

## Version 9.19.59, published on 2022-08-25 ##
* Added: HTTP return code 456 (Blocked) added
* Changed: If user is banned, then HTTP return code 456 is send under ActiveSync
* Added: Support for content type 'application/vnd.ms-sync' (short content type name)
* Added: Implemented support for ActiveSync 2.5 to support Windows 11 native mail application
* Added: Some new field handler
* Added: ActiveSync server default policy settings now automatically send
* Changed: Default folder type implemented in RoundCube handler
* Fixed: <FolderSync>send wrong folder types
* Changed: Implementation of function return type ?string
* Changed: Strong prototyping improved
* Changed: <fldAttribute> enhanced to support mail box types as group attributes and types
* Changed: Return value of Util::unxTime() to string
* Fixed: User::loadUsr() called with invalid parameters
* Changed: Calling HTTP:checkIn() class function after base modifications of _SERVER header
* Added: Support for <Autodiscover> schema http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006a
* Added: Support for "johndoh/globaladdressbook" (Shared Contacts)
* Added: Support for Win 10 / 11 mail program
* Changed: ActiveSync <Search> limited to Global Address Book
* Changed: ActiveSync <DueDate> field handler now also creates and loads <UTCDueDate>
* Changed: ActiveSync <StartDate> field handler now also creates and loads <UTCStartDate>
* Fixed: Reset of folder groups in <FolderSync>
* Changed: Lock file for ActiveSync moved to Handler to support <Ping> and <Sync>
* Added: Support for ActiveSync option <SaveInSent> in <SendMail>, <SmartForward> and <SmartReply>
* Added: Support for MS-ASAcceptMultiPart header in ActiveSync
* Fixed: Error handling in <fldPhoto> enhanced
* Added: PHPMailer 6.6.5
* Added: New query mode DataStore::GRPS will read groups and folders only
* Deleted: Released support from Notes plugin "offerel/primitivenotes"
* Added: Added support from Notes plugin "dondominio/ddnotes"
* Added: Support for PHP 8.1.6
* Fixed: Catch proper <InstanceId> for ActiveSync in <Exception> handling
* Changed: Cache invalid variables in MySQL processing
* Added: New class function chkTrcRec() to allow external database handler to modify trace records
* Added: Support for time zone Etc/UTC and Etc/GMT (same as UTC)

## Version 9.19.01, Published on 2022-02-24 ##
* Fixed:: Multiple excluded dates in recurring events not handled properly (libcalendar bug)
* Changed:: Support for <InstanceId> in repeating pattern
* Fixed:: Deleting single instance of repeating event
* Changed:: Config::updVar() now accepts any parameter type
* Changed:: Config::debug status converted to binary field
* Fixed:: Path handling in .zip archives
* Changed:: Update to support PHP 8.1.2
* Changed:: All unknown time zone identifier were now Removed: from input record
* Fixed:: Unknown variable in Util::normFileName()

## Version 9.18.42, Published on 2022-02-24 ##
* Fixed:: Bug in time zone offset handling in RoundCube

## Version 9.17.99, Published on 2021-12-22 ##
* Changed: Update to SabreDAV 4.3.1
* Changed: Timezone configuration parameter Removed:
* Changed: Local IP shown for cron jobs in log file
* Changed: Show timezone offset in configuration dialogue
* Fixed: Error in stripping REQUEST_URI during configuration
* Fixed: Conversion of German time in Util::unxTime()
* Fixed: Work without REQUEST_URI in DAV handler

## Version 9.17.99, Published on 2021-12-22 ##
* Changed: Update to SabreDAV 4.2.2
* Changed: stripping off any @domain user id extension
* Fixed: Minor bug in GUI_Delete()
* Changed: Update to SabreDAV 4.2.1
* Changed: ActiveSync <Ping> processing
* Changed: Prevent ActiveSync double processing record during one <Sync>
* Changed: Support for SabreDAV 4.2.0
* Fixed: Small bug in <Trigger> vCard field conversion
* Changed: Support for RoundCube calendar plugin 3.5.7
* Changed: Support for RoundCube libcalendar plugin 3.5.6
* Changed: Rename of some internal field names
* Changed: fldOrganization vcard 4.0 stipps of unused ";" at end
* Changed: Ignoring time zone with value of "0"
* Fixed: Coding error in fldRDate()
* Changed: Session handling in user interface
* Fixed: Bug in ActiveSync out-of-office handling
* Fixed: Minor bug in Encoding handling - <Cp> not properly found
* Changed: Late loading of contacts implemented to improve performance
* Changed: Late loading of calendars implemented to improve performance
* Changed: Late loading of tasks implemented to improve performance
* Changed: Late loading of notes implemented to improve performance
* Changed: Late loading of mail boxes implemented to improve performance

## Version 9.17.44, Published on 2021-09-25 ##
* Added: Possibility to run record expiration using a cron job
* Added: MIME support for ActiveSync GAL (global address list) data store
* Added: MIME Support for ActiveSync DocLib (document library) data store
* Fixed: Handling missing name during renaming of trace
* Changed: ActiveSync <Options><BodyPreference> were not longer transient
* Added: Support for ActiveSync synchronization of E-Mail meta data
* Changed: Support for phpmailer 6.5.0
* Changed: Added: SMTP authentication selection in RoundCubeMail handler configuration
* Fixed: Download of ICS attachments
* Fixed: Character set conversion in mail handling

## Version 9.16.72, Published on 2021-08-13 ##
* Added: Unzip script for Unix systems
* Changed: check.php now searches for mb_convert_encoding() function
* Changed: ActiveSync <Autodiscover> updated according to MicroSoft support

## Version 9.16.70, Published on 2021-07-08 ##
* Changed: Support for SabreDav 4.1.5
* Changed: Moved rmDir() to Util.php
* Fixed: Problems with recurring event exception
* Changed: Trace files are now created as plain files (to fix performance issues)
* Fixed: Bug in ActiveSync <Options> handling
* Added: Support for ActiveSync <ConversationIndex> and <ConversationId>
* Changed: Some log messages

## Version 9.16.44, Published on 2021-05-21 ##
* Changed: Data store synchronization only performed on request or once per session
* Added: Selection of data store to trace
* Fixed: Hex. decoding bug in vCard
* Fixed: "Flagged" task support
* Changed: Error processing revised
* Changed: Support for "primivenotesplugin" version 2.0.6
* Changed: Some remaining Funambol patches Removed:
* Removed: Support for bookmark synchronization with MIME type 'text/x-vbookmark'
* Removed: Support for folder synchronization with MIME type 'application/vnd.omads-folder+xml'
* Fixed: Bug in detection of RoundCube group creation

## Version 9.15.53, Published on 2021-02-22 ##
* Changed: Minor bug in display of trace record in Standard Edition Fixed:
* Changed: Additional logging level Added:
* Changed: Selection of log level with more granularity
* Changed: Message 13007-13009 extended to include user id
* Changed: Trace recording artificially slowed down to prevent MySQL-Server from being overloaded
* Changed: Translation updates
* Fixed: fld multiplying in certain circumstance

## Version 9.15.24, Published on 2021-02-02 ##
* Changed: Requires at minimum PHP 4.x
* Changed: Support for PHP 8.x
* Changed: Moved all Dbg function into a new debug class
* Changed: SIF support dropped
* Changed: Some SyncML device driver protocols dropped
* Changed: Centralized namespace conversion xmls to "xmls-ns" to HTTP handler
* Changed: Support for SabreDav 4.1.4
* Changed: List of supported character set updated
* Changed: Minor update to sync*gw DTD
* Changed: Implementation of ActiveSync <InstanceId> in <Sync> command
* Fixed: Some WBXML translation bugs
* Changed: cnvStr() moved to XML class
* Changed: Locking scheme
* Fixed: Minor bug in ActiveSync <Search> response
* Changed: fld <Body> now supports internal typ MD (Markdown)
* Changed: Debugging improved to support data base updated during trace recording
* Fixed: Minor bug in Out-of-office processing in ActiveSync

## Version 9.12.80, Published on 2020-05-26 ##
* Fixed: Code page bug in ActiveSync <Sync> Fixed:
* Changed: Recurrence rule now dropping ActiveSync <DeadOccur>, if value is "0"
* Added: Alarm Time for RoundCube Tasks Added:

## Version 9.12.77, Published on 2020-05-20 ##
* Added: RoundCube Calendar handler extended to support excusion of dates in recurrence events
* Added: RoundCube Calendar handler extended to support exception dates in recurrence events
* Fixed: Minor bug in Timezone handling for vCalendar protocol Fixed:
* Fixed: Minor bug in SIF-E processing Fixed:
* Added: Creation of all default folders during log on
* Added: ActiveSync <FolderSync> enhanced to support virtual data stores
* Added: ActiveSync <GetItemestimate> modified to support https://testconnectivity.microsoft.com
* Changed: sync*gw now behaves as any ActiveSync server
* Changed: File locking for Windows 10 modified
* Added: ActiveSync <Ping> processing improved to prevent multiple <Ping> running in parallel
* Changed: WBXML now default "Public Identifier" to ActiveSync AirSync
* Added: vCalendar support for recurring date exceptions Added:

## Version 9.11.43, Published on 2020-04-03 ##
* Changed: Some code modifications to enable support for PHP 7.4.3
* Added: Filtering of control characters in RoundCube handler extended

## Version 9.11.37, Published on 2020-01-26 ##
* Changed: GUI interface modified to show all buttons in one line
* Fixed: Bug in <AlldayEvent> processing SIF-C and ActiveSync Fixed:
* Fixed: Bug in <Anniversary> processing SIF-C Fixed:
* Fixed: Bug in <Alarm> processing for SIF-C Fixed:

## Version 9.11.31, Published on 2020-01-10 ##
* Changed: Update on available character sets
* Fixed: Typo in processing of status "CANCELLED" Fixed:
* Removed: Property value escaping Removed: from RoundCube handlers
* Added: RoundCube mail datastore handler created
* Fixed: Typo in RoundCube task list datastore handler Fixed:
* Added: Implementation of ActiveSync <GetHierarchy> command
* Changed: Now all comment lines during creation of software packages were Removed:
* Changed: Birthday and anniversary ActiveSync import Changed: to support date only (dropping any given time)
* Changed: RoundCube contact handler slightly modified to avoid contact import loops
* Changed: During synchronization of internal and external data store and deletion of folders, now also potential saved <Ping> list entries were deleted
* Changed: Some messages during loading of trace records modified
* Added: Support for ActiveSync message/rfc822 messages
* Added: mkGroup() updated to support mail box types
* Changed: Change to ActiveSync task document handler to support status COMPLETED
* Added: MicroSoft ActiveSync characters Added:
* Added: Implementation of RoundCube mail box data store handler
* Added: Implementation of ActiveSync <SendMail> command
* Added: Implementation of ActiveSync <SmartReply> command
* Added: Implementation of ActiveSync <SmartForward> command
* Added: Implementation of multi part responses for ActiveSync <ItemOperations><Fetch>

## Version 9.11.08, Published on 2019-12-08 ##
* Changed: Updated to support PHP 7.3.8
* Added: Some additional validation checks Added:
* Added: E-Mail field Added:
* Changed: Category moved to parent <Categories>
* Removed: Dropped support of ActiveSync V2.5
* Fixed: Minor bug in MySQL handler Fixed: when trying to read non-existing record
* Changed: SyncML code page handling extended
* Fixed: Minor bug in HTTP compression Fixed:
* Fixed: DAV Synchronization does not delete calendar attachments
* Changed: ActiveSync <ItemOperations> expanded to support mails
* Added: Device information fields Added:
* Changed: Trace handling during trace replay modified
* Added: <Attachment><Add> and <Attachment><Del> support Added:
* Added: Reports now more supported commands in ActiveSync OPTIONS command
* Changed: Double restore of attachments records while loading trace record skipped
* Changed: User login in RoundCube will save e-mail address in sync*gw
* Added: Support for RoundCube "calendar" plugin version 3.5.2
* Added: Support for RoundCube "tasklist" plugin version 3.5.2
* Added: Support for RoundCube "primitivenotes" plugin version 1.5.4
* Added: Implemented file locking for config file writing
* Changed: Speed optimization for attachment handling
* Changed: Selection of VCARD format for SabreDAV synchronization Removed: (support for client specified format)
* Changed: Synchronization of birthday calendar disabled, because it's not required
* Fixed: Bug in software packaging

## Version 9.08.57, Published on 2019-08-31 ##
* Changed: HTTP password conversion modified - no urldecode()
* Fixed: Minor bug in RoundCube notes handler Fixed:
* Changed: Prevent log messages in ActiveSync <ResolveRecipient>'

## Version 9.08.54, Published on 2019-05-27 ##
* Added: Notes data store handler for RoundCube created
* Changed: X-HTML flag Changed: for <Body> to X-TYP (file extension)

## Version 9.08.48, Published on 2019-05-21 ##
* Fixed: ActiveSync <Search> is now not longer case sensitive
* Fixed: Empty body data will not be send with compression to bypass Java bug
* Fixed: Photo conversion bug for text/vcard Fixed:

## Version 9.08.46, Published on 2019-05-19 ##
* Changed: RoundCube connector now supports 'username_domain' configuration parameter
* Changed: Rename of SabreDAV XML handler

## Version 9.08.43, Published on 2019-05-03 ##
* Changed: Filtering invalid control characters when loading data from RoundCube
* Fixed: Minor bug in RoundCube contact category assignment Fixed:
* Fixed: Minor bug in picture conversion for exotic picture format Fixed:
* Removed: Obsolete <Provision> message for ActiveSync Removed:

## Version 9.08.31, Published on 2019-04-29 ##
* I'm proud to provide my new release 9. This release is a major step in our approach moving forward to an object oriented programming. In my brand new field level approach, each field object (e.g. a telephone number) is responsible for validation, storage and delivering in all available protocols. This makes it much more easy to figure out specific field problems and adding new fields to sync*gw.

## Version 8.04.78, Published on 2019-04-23 ##
* Fixed: Bug in first creation of configuration file
* Changed: ActiveSync <SoftDelete>

## Version 8.04.75, Published on 2019-04-12 ##
* Fixed: Bug in administrator interface
* Changed: File locking in shared hosting environment

## Version 8.04.72, Published on 2019-02-27 ##
* Changed: Updated to supportPHP 7.1.1
* Fixed: Bug in <MaxObjextSize> handling using SyncML protocol
* Changed: "etag" processing in CardDav and CalDAV optimized
* Changed: Attachment storage moved to database
* Added: Attachment size limitation for CardDAV, CalDAV and SyncML protocol implemented
* Added: No attachments can be shown on Apple iPhones due to bug in ActiveSync implementation
* Fixed: Found work around for Apple bug where special HTTP headers are expected
* Fixed: Bug in ISO date format
* Fixed: Date/time conversion in vCalendar 2.0 protocol
* Fixed: <Size> creation for SyncML protocol
* Added: CATEGORY support for RoundCube tasks
* Fixed: ActiveSync name tag <N> import
* Changed: ActiveSync OPTIONS can be requested by client device without user login
* Changed: ActiveSync <Ping> race handling normalized (concurrent script calls by client device)
* Fixed: <MoreData> bug in SyncML
* Changed: Rendering engines for ActiveSync <Note>, <Task>, <Calendar> and <Contact> protocol rewritten
* Added: Lock handling established for config, trace, attachment and ActiveSync protocol
* Changed: RoundCube contact database driver rewritten
* Changed: RoundCube calendar database driver rewritten
* Changed: RoundCube task database driver rewritten
* Changed: Environment test script extended with locking support check
* Added: Additional function to force reload of external datastore records
* Added: gzip and deflate encoding in HTTP communication
* Added: Support for RFC7405
* Changed: Internal data storage format for contact upgraded to vCard 4.0
* Changed: Internal data storage format for task upgraded
* Changed: Internal data storage format for calendar upgraded
* Changed: Database driver for RoundCube tasklist plugin implemented
* Changed: Strict parameter checking for vCard protocol implemented
* Changed: Strict value checking for vCard protocol implemented
* Changed: Strict parameter checking for vCal protocol implemented
* Changed: Strict value checking for vCal protocol implemented
* Changed: Strict parameter checking for vNote protocol implemented
* Changed: Strict value checking for vNote protocol implemented
* Added: SIF-C protocol version 1.0 and 1.1 implemented
* Added: Option Added: in server configuration to select which vCard version should be used, when synchronizing using CardDAV protocol (defaults to V3.0)
* Changed: Download in GUI now use GUID as file name
* Changed: Download in GUI now available for attachments
* Changed: Data store cleanup in GUI now use mysql truncate (if mysql driver is available)
* Changed: Rename in GUI to existing record cause now error message
* Changed: myapp sample handler revised

## Version 7.07.74, Published on 2018-04-17 ##
* Added: Filtering RoundCube contacts without telephone addresses

## Version 7.07.70, Published on 2018-04-08 ##
* Fixed: Bug in concurrent processing
* Changed: RoundCube message driver partially Changed: to type application
* Changed: Status code description for ActiveSync revised
* Fixed: Multiple data stores enabled but not used for SabreDAV
* Fixed: Bug in SabreDAV
* Fixed: Data store group creation was only performed in internal data store
* Added: Task list data store CalDAV synchronization

## Version 7.07.49, Published on 2018-03-27 ##
* Fixed: Input field length in GUI

## Version 7.07.48, Published on 2018-03-26 ##
* Changed: ActiveSync processing optimized
* Changed: User locking updated

## Version 7.07.34, Published on 2018-03-02 ##
* Changed: Some fixes for PHP 7.2.2 support
* Changed: SabreDAV upgraded to 3.2.2

## Version 7.07.23, Published on 2018-01-17 ##
* Added: RoundCube Task plugin handler
* Changed: Some typos in ActiveSync asContact
* Changed: ActiveSync Task handler
* Changed: Support for Truncation in ActiveSync Added:
* Changed: Code optimization for ActiveSync

## Version 7.07.09, Published on 2018-01-01 ##
* Changed: __FILE__ replaced by __DIR__
* Changed: "Default" group handling modified
* Added: extAuthorize() extended
* Added: Synchronization with external data store extended to support change-flag for SabreDAV
* Removed: DTSTAMP Removed: from data import
* Added: Empty <Sync> for ActiveSync implemented
* Changed: <SyncKey> handling for ActiveSync extended
* Changed: PHP code optimization in MIME handler
* Added: Attachment support for ActiveSync 16.x calendar entries Added:
* Changed: WBXML decoding bug Fixed:
* Changed: String handling in WBXML for ActiveSync improved
* Changed: String attachments and photos in file system allows unlimited file size
* Changed: Changed: from crc32() to crc32b()
* Added: Heartbeat interval configuration for ActivSync
* Added: Support for multiple accounts on one device
* Changed: ActiveSync protocol engine revised
* Removed: SAN Handler
* Removed: MMS handler
* Added: RoundCube data base connector extended to support calendar data store

## Version 6.01.00, Published on 2015-03-20 ##
* Fixed: Bug in PHP error catching
* Fixed: Error in ActiveSync <Ping> handling
* Fixed: Typo in ORGANIZER ActiveSync tag
* Changed: Memory consumption and execution optimized
* Changed: Upgrade to SabreDAV 1.8.12
* Changed: Mysql database engine switch to InnoDB (from MyISAM)
* Changed: Source basis updated to PHP 5.3
* Changed: First phlyMail calendar used for synchronization (Changed: from root)
* Added: Trace on specific IP address
* Added: Back end handler for RoundCube
* Added: Special configuration flag to enable log debugging of data store synchronization

## Version 5.01.06, Published on 2014-12-17 ##
* Fixed: ActiveSync command without body parameter
* Fixed: Log file date issue
* Fixed: Log file deletion issue
* Fixed: ActiveSync contact change/deletion

## Version 5.01.05, Published on 2014-10-26 ##
* Changed: Minor bug in display of traces
* Added: Automatic log rotate (and display selection)

## Version 5.00.97, Published on 2014-05-17 ##
* Changed: ActiveSync <FolderSync> rewritten
* Changed: Task seperated to it's own data store
* Added: Upload of folder definition
* Changed: CalDAV extended to support task
* Changed: Storing of .INI file revised
* Changed: Mapping handler extended to support multiple attributes
* Added: <TruncationSize> for ActiveSync protocoll implemented
* Changed: phlyMail address folder now treated as categories
* Fixed: Error in imge resampling function when changing size in same image format
* Fixed: Edit and view in admin interface fails for some special characters
* Changed: Download file extension for vNote Changed: from .txt to .vnt
* Changed: Download file extension for vBookMark Changed: from .txt to .vbk
* Changed: Download file extension for vTodo Changed: from .txt to .tsk
* Changed: Default folder renamed to data store name
* Changed: phlyMail alarms now of type DISPLAY (not AUDIO)
* Changed: Time zone handling Changed: to PHP internal time zones
* Changed: Support for UTF-8 data extended
* Added: Support for ActiveSync Calendar synchronization
* Added: Support for ActiveSync Task synchronization
* Changed: Support for ActiveSync Notes synchronization rewritten
* Added: Support for ActiveSync Contact synchronization
* Fixed: Country specific character support for text/x-vcalendar 1.0
* Fixed: New line handling in quoted printable protocol

## Version 4.05.20, Published on 2013-10-29 ##
* Fixed: Typo in JavaScript source file
* Fixed: Web interface title
* Fixed: Banned user handling revised
* Changed: Web browser interface revised
* Changed: Creation of session identifier
* Added: Support for phlyMail 4.04.51
* Changed: "Data base check" updated
* Fixed: Unassigned task records stored in trace data
* Fixed: GEO could not be entered in vCard editor
* Fixed: Clear log file does not work if log is open by another program
* Added: Category and class field Added: for Notes in editor
* Added: Support for ActiveSync Notes data store

## Version 4.04.81, Published on 2013-09-02 ##
* Added: New tag <Banned> in user object to ban user
* Changed: Mapper function now accepts empty fields
* Added: New <Transparent> flag in mapping table definition
* Added: Support for application/x-zip MIME typ
* Fixed: Error message 1222 sometimes appears in log without error situation
* Fixed: Device loading error in PHP 5.5.1
* Fixed: Error in deletion of Calendar event using WebDAV protocol
* Changed: Document handling function revised
* Fixed: Typo in administrator interface
* Fixed: User name field accessible while login as administrator in web interface
* Fixed: Trace sometime shown wrong sorted
* Changed: Additional mapper function setHID()

## Version 4.04.38, Published on 2013-07-29 ##
* Fixed: Special hook for SyncML implementation bug Memotoo
* Fixed: Comment doublets created in XML objects during debugging Removed:
* Fixed: Directory path assignment in file handler
* Changed: All CSS and JavaScript files now compressed
* Changed: Saving of record status during device change optimized
* Changed: Record status DB:STAT_SEND renamed to DB::STAT_ADD
* Changed: Enable creation of nested folder
* Changed: <GUID> now is unique within user scope (not only within data store scope). This is a preparation for upcoming ActiveSync support
* Changed: Deletion of internal records slightly modified to prevent session record from being unexpected deleted
* Changed: Update of time zone data base
* Changed: Rewrite of group (folder) handling across all modules
* Changed: Upgrade to SabreDAV 1.8.6
* Changed: Synchronization of internal with external data store restricted to one run per session
* Changed: Groups (folder) are imported in SyncML with higher priority to prevent receiving record with missing group assessment

## Version 3.03.84, Published on 2013-04-19 ##
* Added: Support for phlyMail 4.4
* Added: Support for SabreDAV 1.8.5
* Fixed: Hide Debug-Button if no debug user is specified
* Fixed: Delete of record with multiple group assignment in web interface
* Added: Restore of original PHP error logging parameters
* Fixed: Cleanup in sub group sometimes does not work
* Fixed: Debugging of REPORT request in WebDAV failed
* Changed: Time zone definition data base updated
* Added: Suppress warning for time zone "system" (send by Synthesis client)

## Version 3.03.82, Published on 2013-01-21 ##
* Changed: Code how to extract path in browser interface
* Changed: Support for SabreDAV 1.81
* Changed: Configuration handler check availability of handler files

## Version 3.03.53, Published on 2013-01-16 ##
* Added: Accepting application/download MIME type as trace file for uploading
* Fixed: Installtion in local base directory
* Changed: Update on Time Zone data base

## Version 3.03.50, Published on 2012-10-29 ##
* Changed: Support for SabreDav 1.7.1
* Added: Special hack for CardDav 0.3.8.5 client to enable receiving of contact photo
* Changed: Update of Time zone data base

## Version 3.03.47, Published on 2012-09-26 ##
* Changed: Remove usage of potential insecure function eval()
* Fixed: Environment check output is doubled

## Version 3.03.44, Published on 2012-08-09 ##
* Changed: W3C conformance in administrator interface enlarged
* Added: New "RawEdit" button and functionality Added:
* Added: Edit of VTIMEZONE object Added:
* Changed: Move all VJOURNAL, VFREEBUSY, VTIMEZON to top of <Data> element
* Added: Support of TZID parameter in vCalendar editor
* Added: Support for text/icalendar in upload
* Changed: Time zone data base updated
* Changed: SabreDAV 1.6.4 implemented
* Fixed: Clear message window after using "Sync" button

## Version 3.03.31, Published on 2012-07-19 ##
* Fixed: Modified user record (after Edit) is not saved in internal data base
* Changed: Skipping X- parameter value
* Changed: Device change code revised
* Changed: Device change code only called after successful login
* Fixed: Alternate devices are modified improperly during loading of trace record in preparation of debugging

## Version 3.03.27, Published on 2012-07-15 ##
* Fixed: Sending SAN messages failed due to JavaScript bug
* Added: More log message for SAN processing
* Added: New parameter "device" required for sending SAN message

## Version 3.03.19, Published on 2012-07-13 ##
* Fixed: Deleting required input fields in JavaScript
* Fixed: Last record shown in edit mode independent of which record was selected
* Fixed: Add field to record in edit mode
* Fixed: Selecting SAN handler does not return to configuration page

## Version 3.03.15, Published on 2012-07-07 ##
* Changed: Apply special handling for application/vnd.wap.mms-message device information send to Funambol clients
* Changed: Memory consumption in sync*gw browser interface called in FireFox dramatically reduced
* Fixed: On device change, dummy record with missing type field is created
* Added: sync*gw version and upgrade level information Added: to trace data

## Version 3.03.11, Published on 2012-06-28 ##
* Added: Additional error message if MIME type cannot be detected
* Added: Support for Funambol iPhone Sync Client 10.0.12
* Added: Support for Funambol BlackBerry Plug-in 10.0.11
* Added: Support for Funambol Windows Sync Client 10.1.7
* Added: Support for Funambol Android Sync Client 10.1.3

## Version 3.03.08, Published on 2012-06-22 ##
* Added: Record sanity check in data base handler
* Added: Data base check script Added: in download section
* Added: Show corrupted records in sync*gw web interface
* Added: Disable download of corrupted record
* Added: Inconsistent record now returned by DB::READ_ID
* Added: Skipping invalid records during loading of trace records in preparation of debugging
* Changed: <GUID> shown in View and Edit record
* Added: Support for multiple sessions in browser

## Version 3.02.95, Published on 2012-06-18 ##
* Changed: AdminClass documentation updated in Developer Guide
* Changed: Return parameter of AdminClass->Install() and AdminClass->Delete()
* Added: Support for SabreDav 1.6.3
* Fixed: Typo in browser/Handler.php prevent from specifying administrator password during installation
* Changed: Product ID is filtered out during receiving of book marks
* Changed: Updated time zone data base
* Changed: vCard 2.1: TYPE parameter now generally omitted
* Fixed: Bug in special synchronization modus "Replace client data store with records from server" (SyncML)

## Version 3.02.78, Published on 2012-04-21 ##
* Fixed: Error in displaying log file with only record less than 20
* Added: General conversion of base64 encoded data records
* Added: Refuse to import fields with only blanks in phlyMail contact records
* Fixed: Input of full qualified e-mail (with brackets) in record editor
* Fixed: Load of internal records with invalid external record reference in trace now enabled
* Added: Support for SabreDav 1.5.9 and 1.6.2
* Fixed: Typo in RFC6350 module
* Added: Additional TYPE checking to vCard editor
* Fixed: Warning message in phlyMail repeating calendar events handling

## Version 3.02.68, Published on 2012-03-26 ##
* Added: Extended View / Edit for Contact, Groups, Bookmarks and Notes records
* Changed: Ajax engine revised - supports now wider range of browser engines
* Changed: Minor modifications to phlyMail contact mapping table
* Changed: Login to sync*gw without prior successful synchronization enabled

## Version 3.02.25, Published on 2012-03-15 ##
* Fixed: Maintenance of <LastMod> field enhanced to prevent early session timeout of session and trace records
* Changed: Error message during upload of invalid .ZIP file
* Added: XML class able to load XML document with <!DOCTYPE>
* Added: DTD tables for sync*gw XML documents
* Fixed: SyncML syntax recognition for <Get> command enhanced
* Added: SyncML getHandlerID() additionally checks <Meta> tag
* Fixed: Recognition of <Source> parameter in SyncML <Copy> command
* Fixed: fld <DCREATED> for vNote data records will now be accepted
* Changed: Suspend/Resume SyncML session optimized
* Added: SandBox for debugging extended
* Changed: XHTML standard conformity extended in browser interface
* Fixed: Added: <x-change> field in group records (required for CalDAV/CardDAV processing)
* Changed: Support for SabreDAV 1.6.1
* Added: New Attributes X-TYPE, X-CHARSET and X-CONTEXT for vCard data records

## Version 3.01.59, Published on 2012-02-29 ##
* Changed: Carriage return automatically Removed: from all fields in phlyMail Calendar and Contact handler
* Fixed: Typo in DB.php internal XML node name <Mapping>
* Changed: Integration of SabreDav 1.5.8 and 1.6.0. Framework is select automatically based on available PHP version
* Fixed: Change of <DevID> during debugging
* Fixed: If data store is Changed: after a successful synchronization, updated device information object is not requested from client

## Version 3.01.47, Published on 2012-02-24 ##
* Fixed: Missing "View" button in trace data store
* Fixed: Trace loading needs to load default group first
* Changed: View PHP version in environment check
* Added: Support for BlackBerry Funambol client 10.0.1
* Added: Support for Android Funambol client 10.0.4

## Version 3.01.38, Published on 2012-02-22 ##
* Changed: Internal data base layout and data structures
* Changed: Implementation of SabreDAV 1.5.7
* Added: Support for SyncML group in all data stores
* Changed: Support for nested group in all data stores
* Added: Support for multiple group assignment in all data stores
* Changed: Obsolete patches for Funambol Removed:
* Changed: fld value "0" recognized as empty field in external to internal mapper function
* Added: Support for Outlook Funambol client 10.1.6
* Fixed: Source data store name in SyncML synchronization does not fit device expectations
* Fixed: Parameter length limitation checking Fixed:
* Changed: phlyMail installation check in phlyMail back end handler
* Changed: Workaround for ZipArchive creation bug in PHP 5.2.6
* Added: Check for pure XML code during Funambol S4JSIFN, S4JSIFT and S4JSIFE protocol data exchange

## Version 2.04.47, Published on 2012-01-07 ##
* Fixed: Deleted birthday calendar events while birthday in phlyMail contact record exists
* Fixed: Typo in phlyMail calendar handler prevents server from writing log messages
* Changed: Loading of trace records modified to better support birthday calendar events in phlyMail

## Version 2.04.42, Published on 2012-01-03 ##
* Added: ChunkSize configuration parameter - preventing session timeouts by client device
* Fixed: <Size> under special conditions not created
* Changed: Upgrade processing optimized and accelerated

## Version 2.04.36, Published on 2012-01-02 ##
* Fixed: Birthday event in phlyMail handler not always recognized
* Fixed: Load of XML string removes too much blanks
* Changed: Session timeout setting
* Fixed: Bug in vCard 4.0 conversion
* Fixed: Bug in mySQLi table creation
* Changed: Upgrade to SabreDav version 1.5.5
* Changed: Setting of scroll position in browser interface
* Fixed: Bug in Language selection in administrator menu

## Version 2.03.46, Published on 2012-01-02 ##
* Changed: "LogOff" button Added: to WebDAV browser interface
* Changed: "Import" button Added: to WebDAV browser interface

## Version 2.03.42, Published on 2011-12-11 ##
* Added: Support for RFC6350 (vCard 4.0)
* Added: Support for automatic WebDAV configuration
* Added: Web browser access for CardDav and CalDav directories
* Fixed: Error in array() comparison in Mapper class
* Added: Contact and calendar group support
* Added: Support for contact and calendar groups in phlyMail connection handler implemented
* Added: Implementation of PHP "interface" definitions for external data
* Changed: Response to question 06 in FAQ reviewed base handler
* Changed: Internal document attribute <X-MIME> and <X-VER> Removed:

## Version 2.02.85, Published on 2011-11-28 ##
* Added: Full WebDAV-CardDav support (base on SabreDAV)
* Added: RFC2426 module now handles data which are flagged as base64 encoded but are not encoded at all
* Added: Take special care about <UID> field in vCard and vCal export function
* Added: New configuration parameter in phlyMail enabling time zone usage
* Added: General time zone management support
* Added: RFC4122 compliant function Added: to build version 4 compatible UUID
* Changed: Access to $_SERVER variable encapsulated in class HTTP
* Changed: Date of last modification of internal data records swap to stack records
* Changed: Upload of data record (e.g. Contact) now performed always to debug user
* Changed: phlyMail back end message 2203 only display one during sync. session
* Changed: Using UUID instead of numerical <GUID>
* Fixed: Typo in sgwServer::getTmpFile()
* Fixed: Bug in <Delete> of inexistent internal record
* Fixed: If there is no separate data store for Tasks available, then they're not displayed
* Fixed: Typo in Pro-Sync module
* Fixed: Support for non-standard vCard tags (e.g. X-EPOCSECONDNAME)

## Version 2.01.85, Published on 2011-11-03 ##
* Changed: Error messages during phlyMail connection setup enhanced
* Fixed: mySQL tables were not allocated during phlyMail sync*gw installation
* Changed: Trace of HTTP header data Added:
* Fixed: Synchronization status checking always defaults to "Two-way sync with field level compare"
* Fixed: Processing of specific back end handler array() data during synchronization of vCard / vCal records
* Fixed: Property parameter check for internal parameter disabled
* Fixed: Repeating phlyMail events lose follow up dates during synchronization to client device
* Fixed: myapp application back end handler sample PHP files in "Developer Guide" missing

## Version 2.01.38, Published on 2011-10-12 ##
* Fixed: phlyMail application back end handler now sets local timezone during initialization
* Changed: In vCalendar data store handler, all evens are set to default TRANSP=
* Fixed: In sync*gw administrator panel, cursor was sometimes positioned wrong
* Fixed: Under special conditions upgrade handler did a wrong version calculation resulting in double call of upgrade functions

## Version 2.01.11, Published on 2011-10-10 ##
* Changed: Special protection of internal tables stored in XML
* Changed: A typo in log message 1205 was Fixed:
* Fixed: In processing of VTIMEZONE data we recognized a error in mapping functions

## Version 2.00.97, Published on 2011-09-27 ##
* Changed: Whole PHP code rewritten
* Changed: Code base Changed: to PHP 5.1.2 (or higher)
* Changed: Enhanced <Suspend> and <Resume> session handling
* Added: Support for Outlook Funambol 10.0.1
* Added: Support for Symbian "Anna" new calendars

{Go back](https://github.com/toteph42/syncgw/README.md)
