#*********************************************************************************
# The contents of this file are subject to the vtiger CRM Public License Version 1.0
# ("License"); You may not use this file except in compliance with the License
# The Original Code is:  vtiger CRM Open Source
# The Initial Developer of the Original Code is vtiger.
# Portions created by vtiger are Copyright (C) vtiger.
# All Rights Reserved.
#
# ********************************************************************************

cd ..
export VTIGER_HOME=`pwd`
echo "***************************************************************************************************"
echo  vtigerCRM home:  	`pwd`
echo "**************************************************************************************************"

mysql_dir='MYSQLINSTALLDIR'
mysql_username='MYSQLUSERNAME'
mysql_password='MYSQLPASSWORD'
mysql_port=MYSQLPORT
mysql_socket='MYSQLSOCKET'
mysql_bundled='MYSQLBUNDLEDSTATUS'
apache_dir='APACHEINSTALLDIR'
apache_bin='APACHEBIN'
apache_conf='APACHECONF'
apache_port='APACHEPORT'
apache_bundled='APACHEBUNDLED'

if [ $apache_bundled == 'true' ];then
	APACHE_HOME=$apache_dir
	export APACHE_HOME
	cd $APACHE_HOME/bin
	echo ""
	echo "Starting apache at port $apache_port"
	echo ""

	./apachectl -k restart
	if [ $? -ne 0 ]; then
		echo ""
		echo "*******************************************************************************"
		echo "Unable to start the apache server. Check whether the port $apache_port is free"
		echo "*******************************************************************************"
		echo ""
		exit
	fi
fi

if [ $apache_bundled == 'false' ];then
	netstat -an | grep LISTEN | grep -w $apache_port
	apache_stat=$?
	if [ $apache_stat -ne 0 ];then
		echo ""
		echo "**************************************************************************************"
		echo "Apache Server is not running. Start the Apache server and then start the vtigerCRM application"
		echo "**************************************************************************************"
		echo ""
		exit
	else
		echo ""
		echo "Apache Server is running"
		echo ""
	fi
fi

MYSQL_HOME=$mysql_dir

export MYSQL_HOME

cd $MYSQL_HOME
echo ""
echo MySQL home:  `pwd`
echo ""

echo ""
echo "Checking  whether the MySQL server is already running"
echo ""
echo "select 1"| ./bin/mysql --user=$mysql_username --password=$mysql_password  --port=$mysql_port --socket=$mysql_socket > /dev/null
exit_status=$?
if [ $exit_status -eq 0 ];then
	echo " "
	echo "MySQL server is running"
	echo " "
fi
if [ $exit_status -ne 0 ]; then
	if [ $mysql_bundled == 'false' ];then
		echo ""
		echo "**************************************************************************************"
		echo "Mysql server in the directory $mysql_dir is not running at port $mysql_port. Start the mysql server and then start vtigerCRM application"
		echo "**************************************************************************************"
		echo ""

		exit
	else
		echo ""
		echo "Mysql Server is not running. Going to start the  mysql server at port $mysql_port"
		echo ""
		#chown -R nobody .
		#chgrp -R nobody .
		./bin/mysqld --skip-bdb --log-queries-not-using-indexes --log-slow-admin-statements --log-error --low-priority-updates --log-slow-queries=vtslowquery.log --basedir=$MYSQL_HOME --datadir=$MYSQL_HOME/data --socket=$mysql_socket --tmpdir=$MYSQL_HOME/tmp --user=root --port=$mysql_port --default-table-type=INNODB > /dev/null &
		sleep 8
		echo "select 1"| ./bin/mysql --user=$mysql_username --password=$mysql_password  --port=$mysql_port --socket=$mysql_socket > /dev/null
		if [ $? -ne 0 ]; then
			echo ""
			echo "****************************************************************************"
			echo "Unable to start the mysql server. Check whether the port $mysql_port is free"
			echo "****************************************************************************"
			echo ""
			exit
		fi
	fi

fi
echo ""
echo "Checking if the vtigercrm540 database already exists"
echo ""
echo "select 1" | ./bin/mysql --user=$mysql_username --password=$mysql_password  --port=$mysql_port --socket=$mysql_socket -D vtigercrm540 >/dev/null
if [ $? -ne 0 ]; then
	echo ""
	echo "Database vtigercrm540 does not exist. Creating database vtigercrm540"
	echo ""
	./bin/mysql --user=$mysql_username --password=$mysql_password  --port=$mysql_port --socket=$mysql_socket -e "create database if not exists vtigercrm540"
fi

host=`hostname`
echo "*****************************************************************************************************"
if [ $apache_bundled == 'false' ];then
	echo "Please access the product at http://${host}:<apache port>/vtigerCRM5/vtigerCRM"
else
	echo "Please access the product at http://${host}:<apache port>"
echo "*****************************************************************************************************"
fi
cd $VTIGER_HOME
exit 0
