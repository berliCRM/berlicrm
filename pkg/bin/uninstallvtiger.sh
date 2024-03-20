#*********************************************************************************
# The contents of this file are subject to the vtiger CRM Public License Version 1.0
# ("License"); You may not use this file except in compliance with the License
# The Original Code is:  vtiger CRM Open Source
# The Initial Developer of the Original Code is vtiger.
# Portions created by vtiger are Copyright (C) vtiger.
# All Rights Reserved.
#
# ********************************************************************************

INS_DIR="../.."
WRKDIR=`pwd`
PREV_DIR=".."

APACHE_STATUS=`cat startvTiger.sh | grep ^apache_bundled | cut -d "=" -f2 | cut -d "'" -f2`
cd ${INS_DIR}
cd ${PREV_DIR}
if [ ${APACHE_STATUS} == "false" ]
then
	diff conf/httpd.conf conf/vtiger_conf/vtigercrm-5.4.0/httpd.conf > /dev/null;
	if [ $? -eq 0 ]
	then
		cp conf/vtigerCRMBackup/vtigercrm-5.4.0/httpd.vtiger.crm.conf conf/httpd.conf
		echo "The httpd.conf file successfully reverted"
	else
		echo "The httpd.conf file under apache/conf has been edited since installation. Hence the uninstallation will not revert the httpd.conf file. The original httpd.conf file is present in <apache home>/conf/vtigerCRMBackup/vtigercrm-5.4.0/httpd.vtiger.crm.conf. Kindly revert the same manually"
	fi

	diff modules/libphp4.so modules/vtiger_modules/vtigercrm-5.4.0/libphp4.so > /dev/null;
	if [ $? -eq 0 ]
        then
		cp modules/vtigerCRMBackup/vtigercrm-5.4.0/libphp4.vtiger.crm.so modules/libphp4.so
		echo "The libphp4.so file successfully reverted"
	else
		echo "The libphp4.so file under apache/modules has been edited since installation. Hence the uninstallation will not revert the libphp4.so file. The original libphp4.so file is present in <apache home>/modules/vtigerCRMBackup/vtigercrm-5.4.0/libphp4.vtiger.crm.so. Kindly revert the same manually"
	fi

	cd -

	if [ -d $PWD/vtigercrm-5.4.0 ]; then
		echo "Uninstalling vtigerCRM from the system..."
		rm -rf ../conf/vtiger_conf/vtigercrm-5.4.0
		rm -rf ../modules/vtiger_modules/vtigercrm-5.4.0
		rm -rf vtigercrm-5.4.0
		echo "Uninstallation of vtigerCRM completed"
		cd ${HOME}
	fi

else
	cd -
	if [ -d $PWD/vtigercrm-5.4.0 ]; then
                echo "Uninstalling vtigerCRM from the system..."
		rm -rf ../conf/vtiger_conf/vtigercrm-5.4.0
                rm -rf ../modules/vtiger_modules/vtigercrm-5.4.0
                rm -rf vtigercrm-5.4.0
                echo "Uninstallation of vtigerCRM completed"
                cd ${HOME}
        fi
fi
