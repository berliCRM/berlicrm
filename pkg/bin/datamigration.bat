echo off
set ins_dir4_0_1=%~1
set BINDIR=%cd%
set VTIGER_HOME=%cd%
echo VTIGERHOME=%VTIGER_HOME%
set version="4.0.1"

:getinstalldir
if NOT "X%ins_dir4_0_1%" == "X" goto checkdir

echo "*******************"
echo "*******************"
echo
echo

set /P diffmac1="Is the vtiger CRM 4.0.1 mysql db in the same machine as the vtigerCRM4_2GA mysql db installation? (Y/N): "
echo
echo
if "%diffmac1%"=="Y" goto samemac
if "%diffmac1%"=="y" goto samemac

set diffmac_uname=
set diffmac_password=
set diffmac_port=
set diffmac_hostname=
set /P diffmac_hostname=Enter the hostname of  the machine hosting the vtiger CRM 4.0.1:
set /P diffmac_uname=Enter the vtiger CRM 4.0.1 mysql db username:
set /P diffmac_password=Enter the vtiger CRM 4.0.1 mysql db password: 
set /P diffmac_port=Enter the vtiger CRM 4.0.1 mysql db port: 

set mysql_dir=%BINDIR%\..\mysql\bin
echo mysql_dir is %mysql_dir%
echo
echo writing to the mysql_params.bat file
echo set mysql_username=%diffmac_uname%> mysql_params.bat
echo set mysql_password=%diffmac_password%>> mysql_params.bat
echo set mysql_port=%diffmac_port%>> mysql_params.bat
echo set mysql_hostname=%diffmac_hostname%>> mysql_params.bat
echo set mysql_bundled=false>> mysql_params.bat
echo set mysql_dir=%mysql_dir%>> mysql_params.bat
echo
rem set /P hi=bye
echo set FOREIGN_KEY_CHECKS=0; > vtiger_4_2_dump.txt
set mysql_dir_4_2=%BINDIR%\..\mysql\bin
echo mysql_dir again is %mysql_dir_4_2%
echo
echo
echo username is %diffmac_uname%
echo password is %diffmac_password%
echo port is %diffmac_port%
echo hostname is %diffmac_hostname%

echo about to take the dump of the 4.0.1 db and put it to vtiger_4_2_dump.txt file
echo
echo 
"%mysql_dir_4_2%\mysqldump" --host=%diffmac_hostname% --user=%diffmac_uname% --password=%diffmac_password% --port=%diffmac_port% vtigercrm4_0_1 >> vtiger_4_2_dump.txt
IF ERRORLEVEL 1 (
	 echo "Unable to take the vtiger CRM %version% database backup. vtigercrm database may be corrupted"
	 goto exitmigration
) 

echo 4.0.1 dump taken successfully !
rem set /P hi=bye
echo
echo
echo
echo
call mysql_params.bat
echo present directory is %cd%
echo about to print the data into the migrator_connection.php file
rem set /P hi=bye
echo
	echo ^<?php > ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_host_name_old = '%diffmac_hostname%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_username_old = '%diffmac_uname%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_password_old = '%diffmac_password%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_port_old = '%diffmac_port%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo ?^> >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
rem set /P hi=bye
call :echodumpstatus4diffmac
goto set4_2version

:samemac

echo
echo
echo 
set /p ins_dir4_0_1="Enter the vtiger CRM 4.0.1 installation bin directory (For example: C:\Program Files\vtigerCRM4_0_1\bin: "
goto checkdir

:findstrdir
echo "4.0.1 install dir is %ins_dir4_0_1%"
echo %WINDIR%
set FIND_STR="%WINDIR%\system32\findstr.exe"
set SLEEP_STR="%WINDIR%\system32\ping.exe"
goto readMySQLparams

:checkdir
if NOT EXIST "%ins_dir4_0_1%\startvTiger.bat" (
	echo "Kindly specify a valid vtigerCRM 4.0.1 installation directory please"
	set ins_dir4_0_1=
	goto getinstalldir	
)
goto findstrdir

:readMySQLparams
echo in read mysqlparams as a result of invocation for 4.2
echo present dir is %cd%
echo
echo
rem set /P hi=bye
echo Reading the vtiger CRM %version% MySQL Parameters
if %version% == "4.0.1" (
	echo "Inside 4.0.1 loop"
echo 'about to parse the startvTiger.bat of the 4.0.1 server and populate to mysql_params file
	%FIND_STR% /C:"set mysql_" "%ins_dir4_0_1%\startvTiger.bat" > mysql_params.bat
)	
if %version% == "4.2" (
	echo "Inside 4.2 loop"
echo 'about to parse the startvTiger.bat of the 4.2 server and populate to mysql_params file
	%FIND_STR% /C:"set mysql_" startvTiger.bat > mysql_params.bat
)	
echo after writing to the mysql_params.bat file
rem set /P hi=bye
call mysql_params.bat

echo %mysql_username%
echo %mysql_password%
echo %mysql_port%
echo %mysql_bundled%
echo %mysql_dir%


rem set /P hi=bye


if %version% == "4.0.1" (
	set /p mysql_host_name_4_0_1="Specify the host name of the vtiger CRM 4.0.1 mysql server:  "

	echo ^<?php > ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_host_name_old = '%mysql_host_name_4_0_1%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_username_old = '%mysql_username%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_password_old = '%mysql_password%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo $mysql_port_old = '%mysql_port%'; >> ..\apache\htdocs\vtigerCRM\migrator_connection.php
	echo ?^> >> ..\apache\htdocs\vtigerCRM\migrator_connection.php

echo about to invoke isMySQLRunning
goto isMySQLRunning
)

goto isMySQLRunning


:isMySQLRunning
echo MYSQLDIR=%mysql_dir%
echo "Checking whether the vtiger CRM %version% MySQL server is already running"
echo
"%mysql_dir%\bin\mysql" --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% -e "show databases" > NUL
IF ERRORLEVEL 1 goto startmysql
ECHO  "vtiger CRM %version% MySQL Server is already started and running"
if %version% == "4.0.1" goto dump4_0_1mysql
if %version% == "4.2" goto dumpin4_2mysql
rem set /P hi=bye

:startmysql
echo "Starting %version% vtiger MySQL on port specified by the user"
echo
cd /D %mysql_dir%
start mysqld -b .. --datadir=../data --port=%mysql_port%
%SLEEP_STR% -n 11 127.0.0.1>nul
"%mysql_dir%\bin\mysql" --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% -e "show databases" > NUL
IF ERRORLEVEL 1 goto notstarted
echo "Started vtiger CRM %version% MySQL on port specified by the user"
echo
cd /d %VTIGER_HOME%
if %version% == "4.0.1" goto dump4_0_1mysql
if %version% == "4.2" goto dumpin4_2mysql

:notstarted
cd /d %VTIGER_HOME%
echo
echo
ECHO "Unable to start the vtiger CRM %version% MySQL server at port %mysql_port%. Check if the port is free"
echo ""
echo
echo
echo
echo
set /p pt=Free the port and Press Any Key to Continue...
goto startmysql

:dump4_0_1mysql
echo  in dump4_0_1mysql method
echo
echo
echo set FOREIGN_KEY_CHECKS=0; > vtiger_4_2_dump.txt
"%mysql_dir%\bin\mysqldump" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% vtigercrm4_0_1 >> vtiger_4_2_dump.txt
IF ERRORLEVEL 1 (
	 echo "Unable to take the vtiger CRM %version% database backup. vtigercrm database may be corrupted"
	 goto exitmigration
)
echo "Data dump taken successfully in vtiger_4_2_dump.txt"

goto echodumpstatus4samemac

:echodumpstatus4samemac 
echo
echo
echo
echo database being created for vtigercrm4_0_1_bkp
echo
echo
"%mysql_dir%\bin\mysql" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% -e "create database vtigercrm_4_0_1_bkp"

"%mysql_dir%\bin\mysql" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% --force vtigercrm_4_0_1_bkp < vtiger_4_2_dump.txt
rem set /P hi=bye
echo 'about to start the input from the DataMigration.php file '
echo
echo
..\php\php.exe -f ..\apache\htdocs\vtigerCRM\Migrate.php
echo 'exporting the migrated data to the dump file migrated_vtiger_4_0_1_dump file'

echo set FOREIGN_KEY_CHECKS=0; > migrated_vtiger_4_0_1_dump.txt
"%mysql_dir%\bin\mysqldump" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% vtigercrm_4_0_1_bkp >> migrated_vtiger_4_0_1_dump.txt

rem set /P hi=bye
echo ' about to drop the vtigercrm_4_0_1_bkp database '
"%mysql_dir%\bin\mysql" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% -e "drop database vtigercrm_4_0_1_bkp"
rem set /P hi=bye
goto stopMySQL

:stopMySQL
if %mysql_bundled%==true (
        cd /D %mysql_dir%
	echo "Going to stop vtiger CRM %version% MySQL server"
        "%mysql_dir%\bin\mysqladmin" --port=%mysql_port% --user=%mysql_username% --password=%mysql_password% shutdown
        echo "vtiger CRM  MySQL Sever is shut down"
        cd /d %VTIGER_HOME%
	%SLEEP_STR% -n 11 127.0.0.1>nul
)
goto set4_2version



















:set4_2version
set version="4.2"
echo
echo
echo '######################## version set as 4.2 vtiger CRM ######################## '
goto findstrdir


















:dumpin4_2mysql
echo 'about to drop 4_2 db'
"%mysql_dir%\bin\mysql" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% -e "drop database vtigercrm4_2"
echo 'about create if not exists drop 4_2 db'
"%mysql_dir%\bin\mysql" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% -e "create database if not exists vtigercrm4_2"
echo 'about to force-dump data to the 4_2 db from the migrated_vtiger_4_0_1_dump file into the vtigercrm4_2 db'
echo
echo
"%mysql_dir%\bin\mysql" --user=%mysql_username% --password=%mysql_password% --port=%mysql_port% vtigercrm4_2 --force < migrated_vtiger_4_0_1_dump.txt  2> migrate_log.txt
IF ERRORLEVEL 1 (
	 echo "Unable to dump data into the vtiger CRM %version% database vtigercrm4_2. Check the migrate_log.txt in the %VTIGER_HOME% directory"
	 goto exitmigration
)
echo "Data successfully migrated into vtiger CRM 4.2 database"
echo
echo
rem set /P hi=bye
goto end 















:exitmigration
echo "Exiting Migration"
goto end



















rem dump status for different machines

:echodumpstatus4diffmac 
echo
echo mysql directory is %mysql_dir%
"%mysql_dir%\mysql" --host=%diffmac_hostname% --user=%diffmac_uname% --password=%diffmac_password% --port=%diffmac_port% -e "create database vtigercrm_4_0_1_bkp"

echo created database vtigercrm_4_0_1_bkup 

"%mysql_dir%\mysql" --host=%diffmac_hostname% --user=%diffmac_uname% --password=%diffmac_password% --port=%diffmac_port% --force vtigercrm_4_0_1_bkp < vtiger_4_2_dump.txt

echo dumped data from the file vtiger_4_2_dump.txt into the vtigercrm_4_0_1_bkp database

echo
echo
echo
echo 'about to start altering the database to get in sync with the 4.2 structure using the input from DataMigration.php file '
echo
echo present working directory is %cd%
echo
echo
..\php\php.exe -f ..\apache\htdocs\vtigerCRM\Migrate.php

echo set FOREIGN_KEY_CHECKS=0; > migrated_vtiger_4_0_1_dump.txt

"%mysql_dir%\mysqldump" --host=%diffmac_hostname% --user=%diffmac_uname% --password=%diffmac_password% --port=%diffmac_port% vtigercrm_4_0_1_bkp >> migrated_vtiger_4_0_1_dump.txt
echo dumped the database to migrated_vtiger_4_0_1_dump.txt file

rem set /P hi=bye
echo
echo
echo
echo
echo ' about to drop the vtigercrm_4_0_1_bkp database '
"%mysql_dir%\mysql" --host=%diffmac_hostname% --user=%diffmac_uname% --password=%diffmac_password% --port=%diffmac_port% -e "drop database vtigercrm_4_0_1_bkp"

goto end


:end
del mysql_params.bat
rem exit
