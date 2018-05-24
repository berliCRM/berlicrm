echo off
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


echo %WINDIR%
set FIND_STR="%WINDIR%\system32\findstr.exe"
set SLEEP_STR="%WINDIR%\system32\ping.exe"
goto initiate

:initiate
rem if true means , vtiger crm mysql is being used
if "test" == "%1test" goto start1
set VTIGER_HOME=%1
goto start2

:start1
cd ..
set VTIGER_HOME=%cd%
:start2

if %apache_bundled% == true goto checkBundledApache
if %apache_bundled% == false goto checkUserApache

:checkBundledApache
echo "APACHEBUNDLED"
cd /d %apache_dir%
if %apache_service% == true goto StartApacheService
start bin\Apache -f conf\httpd.conf
IF ERRORLEVEL 1 goto stopservice
goto checkmysql

:StartApacheService
echo ""
echo "making an attempt to kill any existing vtigercrm service"
echo ""
bin\apache -k stop -n vtigercrm504
bin\apache -k uninstall -n vtigercrm504
echo "Uninstalling apache service again for confirmation after sleeping for 10 seconds"
echo ""
%SLEEP_STR% -n 10 127.0.0.1>nul
bin\apache -k stop -n vtigercrm504
bin\apache -k uninstall -n vtigercrm504 
echo ""
echo ""
echo "Installing  vtigercrm504 apache service after sleeping for 10 seconds"
echo ""
%SLEEP_STR% -n 10 127.0.0.1>nul
bin\apache -k install -n vtigercrm504 -f conf\httpd.conf
echo ""
echo "Starting  vtigercrm504 apache service"
echo ""
bin\apache -n vtigercrm504 -k start
IF ERRORLEVEL 1 goto stopservice
goto checkmysql

:checkUserApache
netstat -anp tcp >port.txt
%FIND_STR% "\<%apache_port%\>" port.txt
if ERRORLEVEL 1 goto apachenotrunning
%FIND_STR% "\<%apache_port%\>" port.txt >list.txt
%FIND_STR% "LISTEN.*" list.txt
if ERRORLEVEL 1 goto apachenotrunning
echo ""
echo "Apache is running"
echo ""
goto checkmysql

:apachenotrunning
echo ""
echo ""
echo "Apache in the location %apache_dir% is not running. Start Apache and then start vtiger crm"
echo ""
echo ""
set /p pt=Press Any Key to Continue...
goto end
                
:checkmysql
cd /d %mysql_dir%\bin
echo %cd%

echo ""
echo "Checking the whether the MySQL server is already running"
echo ""
mysql --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% -e "show databases" > NUL
IF ERRORLEVEL 1 goto startmysql 
echo ""
echo ""
ECHO  "MySQL is already started and running"
echo ""
echo ""
goto checkdatabase


:startmysql
echo ""
echo "Starting MySQL on port specified by the user"
echo ""
start mysqld-nt -b .. --skip-bdb --log-queries-not-using-indexes --log-slow-admin-statements --log-error --low-priority-updates --log-slow-queries=vtslowquery.log --default-storage-engine=InnoDB --datadir=../data --port=%mysql_port%
%SLEEP_STR% -n 11 127.0.0.1>nul
mysql --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% -e "show databases" > NUL
IF ERRORLEVEL 1 goto notstarted
echo ""
echo "Started MySQL on port specified by the user"
echo ""
goto checkdatabase


:checkdatabase
echo ""
echo "check to see if vtigercrm504 database already exists"
echo ""
mysql --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% -e "show databases like 'vtigercrm504'" | "%WINDIR%\system32\find.exe" "vtigercrm504" > NUL
IF ERRORLEVEL 1 goto dbnotexists
echo ""
ECHO  "vtigercrm504 database exists"
echo ""
goto end


:dbnotexists
echo ""
ECHO "vtigercrm504 database does not exist"
echo ""
echo %cd%
echo ""
echo "Proceeding to create database vtigercrm504 and populate the same"
echo ""
mysql --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% -e "create database if not exists vtigercrm504"
echo ""
echo "vtigercrm504 database created"
echo ""
goto end

:notstarted
echo ""
echo ""
ECHO "Unable to start the MySQL server at port %mysql_port%. Check if the port is free"
echo ""
echo ""
set /p pt=Press Any Key to Continue...
goto end

:stopservice
echo ""
echo ""
echo ""
echo "********* Service not started as port # %apache_port% occupied ******* "
echo ""
echo ""
echo ""
echo ""
echo "********* I am sorry. I am not able to start the product as the apache port that you have specified:  port # %apache_port% seems to be occupied ******* "
echo ""
echo ""
 echo "You could give me a different port number if you wish by doing the following ...."
echo ""
echo ""
echo "********* Open the apache/conf/httpd.conf file, search for 'Listen' and change the port number ******* "
echo ""
echo ""
echo ""
echo ""
echo "********* Change the apache port in startvTiger.bat and stopvTiger.bat too and then access the product from the browser in the following manner http://localhost:apacheport******* "
echo ""
echo ""
echo "Thank You"
echo ""
echo ""
set /p pt=Press Any Key to Continue...
goto end


:end
cd /d %VTIGER_HOME%\bin


