@echo off
::  
:: 	Create PHPMailer package for sync*gw
::
::	https://github.com/PHPMailer/PHPMailer/releases
::
::	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
:: 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
::
setlocal 
call :msg Installing latest PHPMailer release...

call :msg Checking XAMPP installation directory...
if "%XAMPP-BIN%" EQU "" (
	call :err Setup environement variable "XAMPP-BIN" [base directory of your XAMPP installation]!
	exit /b 1
)
set PHP=%XAMPP-BIN%php\php.exe 

call :msg Checking for sync*gw installation...
set OUT=..\..\syncgw\ext
if not exist "%OUT%" (
	call :err Directory "%OUT%" does not exist!
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

call :msg Downloading latest PHPMailer release...
%PHP% composer.phar require phpmailer/phpmailer
if not exist composer.* (
	call :err Error downloading PHPMailer!
	exit /b 1
)

call :msg Recreate "%OUT%"...
set OUT=%OUT%%\PHPMailer
if exist "%OUT%" (
	call :deldir "%OUT%"
)
mkdir "%OUT%" 

call :install vendor\phpmailer\phpmailer\LICENSE "%OUT%"
if errorlevel 1 exit /b 1
call :install vendor\phpmailer\phpmailer\VERSION "%OUT%" 
if errorlevel 1 exit /b 1
call :install vendor\phpmailer\phpmailer\src\*.* "%OUT%"
if errorlevel 1 exit /b 1

call :msg Deleting "vendor" directory...
call :deldir vendor
del composer.json >NUL
del composer.lock >NUL

call :msg Latest PHPMailer release sucessfully installed!
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
call :msg Deleting directory "%1"...
:repeat
	rd /s /q %1
	if errorlevel 1 goto repeat
exit /b 0

:: call :install vendor\LICENSE %OUT%
:install
xcopy /s /i /q %1 %2 >NUL
if errorlevel 1 (
	call :err Error installing "%1" to "%2"!
	exit /b 1
) else (
	call :msg Installed "%1" sucessfully to "%2"...
	exit /b 0
)
