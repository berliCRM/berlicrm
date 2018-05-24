<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
$languageStrings = array(
	//Basic Field Names
	'LBL_NEW' => 'New',
	'LBL_WORKFLOW' => 'Workflow',
	'LBL_CREATING_WORKFLOW' => 'Creating WorkFlow',
	'LBL_NEXT' => 'Next',

	//Edit view
	'LBL_STEP_1' => 'Step 1',
	'LBL_ENTER_BASIC_DETAILS_OF_THE_WORKFLOW' => 'Enter basic details of the Workflow',
	'LBL_SPECIFY_WHEN_TO_EXECUTE' => 'Specify when to execute this Workflow',
	'ON_FIRST_SAVE' => 'Only on the first save',
	'ONCE' => 'Until the first time the condition is true',
	'ON_EVERY_SAVE' => 'Every time the record is saved',
	'ON_MODIFY' => 'Every time a record is modified',
        'ON_SCHEDULE' => 'جدول',
	'MANUAL' => 'System',
	'SCHEDULE_WORKFLOW' => 'Schedule Workflow',
	'ADD_CONDITIONS' => 'Add Conditions',
	'ADD_TASKS' => 'Add Tasks',
	
	//Step2 edit view
	'LBL_EXPRESSION' => 'Expression',
	'LBL_FIELD_NAME' => 'Field',
	'LBL_SET_VALUE' => 'Set Value',
	'LBL_USE_FIELD' => 'Use Field',
	'LBL_USE_FUNCTION' => 'Use Function',
	'LBL_RAW_TEXT' => 'Raw text',
	'LBL_ENABLE_TO_CREATE_FILTERS' => 'Enable to create Filters',
	'LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED' => 'This workflow was created in older look. Conditions created in older look cannot be edited. You can choose to recreate the conditions, or use the existing conditions without changing them.',
	'LBL_USE_EXISTING_CONDITIONS' => 'Use existing conditions',
	'LBL_RECREATE_CONDITIONS' => 'Recreate Conditions',
	'LBL_SAVE_AND_CONTINUE' => 'Save & Continue',
	
	//Step3 edit view
	'LBL_ACTIVE' => 'Active',
	'LBL_TASK_TYPE' => 'Task Type',
	'LBL_TASK_TITLE' => 'Task Title',
	'LBL_ADD_TASKS_FOR_WORKFLOW' => 'Add Task for Workflow',
	'LBL_TASK_TYPE' => 'Task type',
	'LBL_EXECUTE_TASK' => 'Execute Task',
	'LBL_SELECT_OPTIONS' => 'Select Options',
	'LBL_ADD_FIELD' => 'Add field',
	'LBL_ADD_TIME' => 'Add time',
	'LBL_TITLE' => 'Title',
	'LBL_PRIORITY' => 'Priority',
	'LBL_ASSIGNED_TO' => 'Assigned to',
	'LBL_TIME' => 'Time',
	'LBL_DUE_DATE' => 'Due Date',
	'LBL_THE_SAME_VALUE_IS_USED_FOR_START_DATE' => 'The same value is used for the start date',
	'LBL_EVENT_NAME' => 'Event Name',
	'LBL_TYPE' => 'Type',
	'LBL_METHOD_NAME' => 'Method Name',
	'LBL_RECEPIENTS' => 'Recepients',
	'LBL_ADD_FIELDS' => 'Add Fields',
	'LBL_SMS_TEXT' => 'Sms Text',
	'LBL_SET_FIELD_VALUES' => 'Set Field Values',
	'LBL_ADD_FIELD' => 'Add Field',
	'LBL_IN_ACTIVE' => 'In Active',
	'LBL_SEND_NOTIFICATION' => 'Send Notification',
	'LBL_START_TIME' => 'Start Time',
	'LBL_START_DATE' => 'Start Date',
	'LBL_END_TIME' => 'End Time',
	'LBL_END_DATE' => 'End Date',
	'LBL_ENABLE_REPEAT' => 'Enable Repeat',
	'LBL_NO_METHOD_IS_AVAILABLE_FOR_THIS_MODULE' => 'No method is available for this module',
	'LBL_FINISH' => 'Finish',
	'LBL_NO_TASKS_ADDED' => 'No Task',
	'LBL_CANNOT_DELETE_DEFAULT_WORKFLOW' => 'You Cannot delete default Workflow',
	'LBL_MODULES_TO_CREATE_RECORD' => 'Modules to create record',
	'LBL_EXAMPLE_EXPRESSION' => 'Expression',
	'LBL_EXAMPLE_RAWTEXT' => 'Rawtext',
	'LBL_VTIGER' => 'Vtiger',
	'LBL_EXAMPLE_FIELD_NAME' => 'Field',
	'LBL_NOTIFY_OWNER' => 'notify_owner',
	'LBL_ANNUAL_REVENUE' => 'annual_revenue',
	'LBL_EXPRESSION_EXAMPLE2' => "if mailingcountry == 'India' then concat(firstname,' ',lastname) else concat(lastname,' ',firstname) end",
	'LBL_RUN_WORKFLOW' => 'تشغيل سير العمل',
	'LBL_AT_TIME' => 'في وقت',
	'LBL_HOURLY' => 'كل ساعة',
	
	'LBL_DAILY' => 'يوميا',
	'LBL_WEEKLY' => 'الأسبوعية',
	'LBL_ON_THESE_DAYS' => 'على هذه الأيام',
	'LBL_MONTHLY_BY_DATE' => 'شهرية حسب التاريخ',
	'LBL_MONTHLY_BY_WEEKDAY' => 'شهريا من قبل ليوم في الأسبوع',
	'LBL_YEARLY' => 'سنويا',
	'LBL_SPECIFIC_DATE' => 'على تاريخ محدد',
	'LBL_CHOOSE_DATE' => 'اختيار التاريخ',
	'LBL_SELECT_MONTH_AND_DAY' => 'حدد الشهر والتسجيل',
	'LBL_SELECTED_DATES' => 'تواريخ مختارة',
	'LBL_EXCEEDING_MAXIMUM_LIMIT' => 'تجاوز الحد الأقصى',
	'LBL_NEXT_TRIGGER_TIME' => 'في المرة القادمة الزناد على',
	'LBL_MESSAGE' => 'رسالة',
	'LBL_FROM' => 'من',
	'Optional' => 'اختياري',
	'LBL_ADD_TASK' => 'مهمة',
        'Portal Pdf Url' => 'البوابة رابط قوات الدفاع الشعبي',
        'LBL_ADD_TEMPLATE'  => 'إضافة قالب',
        'LBL_LINEITEM_BLOCK_GROUP' => 'كتلة لينيتيمس للمجموعة الضريبية',
        'LBL_LINEITEM_BLOCK_INDIVIDUAL' => 'كتلة لينيتيمس للضرائب الفردية',
	
	
	//Translation for module
	'Calendar' => 'هل ل',
);

$jsLanguageStrings = array(
	'JS_STATUS_CHANGED_SUCCESSFULLY' => 'Status changed Successfully',
	'JS_TASK_DELETED_SUCCESSFULLY' => 'Task deleted Successfully',
	'JS_SAME_FIELDS_SELECTED_MORE_THAN_ONCE' => 'Same fields selected more than once',
	'JS_WORKFLOW_SAVED_SUCCESSFULLY' => 'Workflow saved successfully'
);