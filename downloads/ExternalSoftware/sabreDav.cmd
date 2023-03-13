@echo off
::  
:: 	Create sabreDAV package for sync*gw
::
::	https://github.com/sabre-io/dav/releases
::
::	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
:: 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
::
setlocal 
call :msg Installing latest sabreDAV release...

call :msg Checking XAMPP installation directory...
if "%XAMPP-BIN%" EQU "" (
	call :err Setup environement variable "XAMPP-BIN" [base directory of your XAMPP installation]!
	exit /b 1
)
set PHP=%XAMPP-BIN%php\php.exe 

call :msg Checking for sync*gw installation...
set OUT=..\..\syncgw\ext
if not exist "%OUT%" (
	call :err Directory "%OUT% does not exist!
	exit /b 1
)

call :msg Checking for "composer.phar" file...
if not exist "composer.phar" (
	%PHP% -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	%PHP% -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	%PHP% composer-setup.php
	%PHP% -r "unlink('composer-setup.php');"
) else (
	%PHP% composer.phar self-update
)

call :msg Downloading sabreDAV files...
%PHP% composer.phar require sabre/dav
if not exist composer.* (
	call :err Error downloading sabreDAV!
	exit /b 1
)

call :msg Recreate "%OUT%"...
set OUT=%OUT%%\Sabre
if exist "%OUT%" (
	call :deldir "%OUT%"
)
mkdir "%OUT%" 
call :install vendor\sabre\dav\lib\* 		"%OUT%"
if errorlevel 1 exit /b 1
call :install vendor\sabre\dav\LICENSE 		"%OUT%"
if errorlevel 1 exit /b 1
call :install vendor\sabre\event\lib\* 		"%OUT%\Event"
if errorlevel 1 exit /b 1
call :install vendor\sabre\event\LICENSE 	"%OUT%\Event"
if errorlevel 1 exit /b 1
call :install vendor\sabre\http\lib\* 		"%OUT%\HTTP"
if errorlevel 1 exit /b 1
call :install vendor\sabre\http\LICENSE 	"%OUT%\HTTP"
if errorlevel 1 exit /b 1
call :install vendor\sabre\uri\lib\* 		"%OUT%\Uri"
if errorlevel 1 exit /b 1
call :install vendor\sabre\uri\LICENSE 		"%OUT%\Uri"
if errorlevel 1 exit /b 1
call :install vendor\sabre\vobject\lib\* 	"%OUT%\VObject"
if errorlevel 1 exit /b 1
call :install vendor\sabre\vobject\LICENSE 	"%OUT%\VObject"
if errorlevel 1 exit /b 1
call :install vendor\sabre\xml\lib\* 		"%OUT%\Xml"
if errorlevel 1 exit /b 1
call :install vendor\sabre\xml\LICENSE 		"%OUT%\Xml"
if errorlevel 1 exit /b 1
call :install vendor\psr\log\src\* 			"%OUT%\Psr\Log"
if errorlevel 1 exit /b 1
call :install vendor\psr\log\LICENSE 		"%OUT%\Psr\Log"
if errorlevel 1 exit /b 1

call :msg Deleting "vendor" directory...
call :deldir vendor
del composer.json >NUL
del composer.lock >NUL

call :patch Sabre CardDAV/Plugin.php CardDAV
if errorlevel 1 exit /b 1
call :patch Sabre CalDAV/Plugin.php CalDAV
if errorlevel 1 exit /b 1

call :msg Latest sabreDAV release sucessfully installed!
exit /b 0

:: call :msg Hello world...
:msg
echo. [42m  %*   [0m
exit /b 0

:: call :err Error...
:err
echo. [41m   %*   [0m
exit /b 0

:: call :deldir name
:deldir
call :msg Deleting directory %1...
:repeat
	rd /s /q %1
	if errorlevel 1 goto repeat
exit /b 0

:: call :install vendor\LICENSE %OUT%
:install
xcopy /s /i /q %1 %2 >NUL
if errorlevel 1 (
	call :err Error installing "%1" to %2!
	exit /b 1
) else (
	call :msg Installed "%1" sucessfully to "%2"...
	exit /b 0
)

:: call :patch Sabre CardDAV/Plugin.php CardDAV
:patch
set wslOUT=%OUT:\=/%
if not exist "patches\%1" (
	mkdir "patches\%1"
)

:: check if patch file exists
if not exist "patches\%1\%3" (
	call :err Missing patch file "patches\%1\%3!
	exit /b 1
)

call :msg Applying "patches/%1/%3" to "%OUT%\%2"...
wsl patch "%wslOUT%/%2" < "patches/%1/%3" 
exit /b 0
)
