@echo off
cd ..
set mysql_dir=MYSQLINSTALLDIR
set mysql_username=MYSQLUSERNAME
set mysql_password=MYSQLPASSWORD
set mysql_port=MYSQLPORT
set mysql_bundled=MYSQLBUNDLEDSTATUS
set apache_dir=APACHEINSTALLDIR
set apache_bin=APACHEBIN
set apache_conf=APACHECONF
set apache_port=APACHEPORT
set apache_bundled=APACHEBUNDLED
set apache_service=APACHESERVICE

set VTIGER_HOME=%cd%


if %apache_bundled% == true goto StopApacheCheck
goto StopMySQL

:StopApacheCheck
if %apache_service% == true goto StopApacheService
cd /d %apache_dir%
rem shut down apache
echo ""
echo "stopping vtigercrm apache"
echo ""
bin\ShutdownApache logs\httpd.pid
goto StopMySQL

:StopApacheService
cd /d %apache_dir%
rem shut down apache
echo ""
echo "stopping vtigercrm504 apache service"
echo ""
bin\apache -n vtigercrm504 -k stop
echo ""
echo "uninstalling vtigercrm504 apache service"
echo ""
bin\apache -k uninstall -n vtigercrm504
rem .\bin\ShutdownApache.exe logs\httpd.pid
goto StopMySQL


:StopMySQL
if %mysql_bundled% == true (
	rem cd /d %VTIGER_HOME%\mysql\bin
	rem  shutdown mysql 
	cd /d %mysql_dir%\bin
	mysqladmin --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% shutdown
	echo ""
	echo "vtiger CRM  MySQL Sever is shut down"
	echo ""
	cd /d %VTIGER_HOME%\bin
)
