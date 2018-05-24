<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************
 *  Language     : Español es_es
 *  Version      : 6.0.0
 *  Created Date : 2013-05-10
 *  Author       : JPL TSolucio, S. L. Joe Bordes
 *  Last change  : 2013-05-11
 *  Author       : JPL TSolucio, S. L. Joe Bordes
 *************************************************************************************/
$languageStrings = array(
	//Basic Field Names
	'LBL_NEW' => 'Nuevo',
	'LBL_WORKFLOW' => 'Flujo de Trabajo',
	'LBL_CREATING_WORKFLOW' => 'Creando Flujo de Trabajo',
	'LBL_EDITING_WORKFLOW' => 'Editando Flujo de Trabajo',
	'LBL_NEXT' => 'Siguiente',

	//Edit view
	'LBL_STEP_1' => 'Paso 1',
	'LBL_ENTER_BASIC_DETAILS_OF_THE_WORKFLOW' => 'Introduce detalles del Flujo de Trabajo',
	'LBL_SPECIFY_WHEN_TO_EXECUTE' => 'Cuando ejecutar el flujo de trabajo',
	'ON_FIRST_SAVE' => 'Solo al crear',
	'ONCE' => 'Solo la primera vez que se cumple la condición',
	'ON_EVERY_SAVE' => 'Cada vez que se guarda la entidad',
	'ON_MODIFY' => 'Cada vez que se modifica la entidad',
        'ON_SCHEDULE' => 'Horario',
	'MANUAL' => 'Sistema',
	'SCHEDULE_WORKFLOW' => 'Programa Flujo de Trabajo',
	'ADD_CONDITIONS' => 'Añadir Condición',
	'ADD_TASKS' => 'Añadir Tarea',

	//Step2 edit view
	'LBL_EXPRESSION' => 'Expresión',
	'LBL_FIELD_NAME' => 'Campo',
	'LBL_SET_VALUE' => 'Asignar Valor',
	'LBL_USE_FIELD' => 'Utilizar Campo',
	'LBL_USE_FUNCTION' => 'Utilizar Función',
	'LBL_RAW_TEXT' => 'Texto',
	'LBL_ENABLE_TO_CREATE_FILTERS' => 'Habilitar para crear Filtros',
	'LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED' => 'Este flujo de trabajo se creó con una versión anterior. Sus condiciones no pueden ser editadas. Puedes generar de nuevo las condiciones, o utilizar las que hay sin modificarlas.',
	'LBL_USE_EXISTING_CONDITIONS' => 'Utilizar condiciones existentes',
	'LBL_RECREATE_CONDITIONS' => 'Generar de nuevo las condiciones',
	'LBL_SAVE_AND_CONTINUE' => 'Guardar y Continuar',
	
	//Step3 edit view
	'LBL_ACTIVE' => 'Activo',
	'LBL_TASK_TYPE' => 'Tipo Tarea',
	'LBL_TASK_TITLE' => 'Título Tarea',
	'LBL_ADD_TASKS_FOR_WORKFLOW' => 'Añadir tarea al flujo de trabajo',
	'LBL_EXECUTE_TASK' => 'Ejecutar Tarea',
	'LBL_SELECT_OPTIONS' => 'Seleccionar Opciones',
	'LBL_ADD_FIELD' => 'Añadir campo',
	'LBL_ADD_TIME' => 'Añadir tiempo',
	'LBL_TITLE' => 'Título',
	'LBL_PRIORITY' => 'Prioridad',
	'LBL_ASSIGNED_TO' => 'Asignado a',
	'LBL_TIME' => 'Tiempo',
	'LBL_DUE_DATE' => 'Fecha vencimiento',
	'LBL_THE_SAME_VALUE_IS_USED_FOR_START_DATE' => 'Se utilizará el mismo valor para la fecha de inicio',
	'LBL_EVENT_NAME' => 'Nombre Evento',
	'LBL_TYPE' => 'Tipo',
	'LBL_METHOD_NAME' => 'Nombre Método',
	'LBL_RECEPIENTS' => 'Destinatarios',
	'LBL_ADD_FIELDS' => 'Añadir campos',
	'LBL_SMS_TEXT' => 'Texto SMS',
	'LBL_SET_FIELD_VALUES' => 'Asignar valores a campos',
	'LBL_IN_ACTIVE' => 'Inactivo',
	'LBL_SEND_NOTIFICATION' => 'Enviar Notificación',
	'LBL_START_TIME' => 'Hora Inicio',
	'LBL_START_DATE' => 'Fecha Inicio',
	'LBL_END_TIME' => 'Hora Fin',
	'LBL_END_DATE' => 'Fecha Fin',
	'LBL_ENABLE_REPEAT' => 'Repetir',
	'LBL_NO_METHOD_IS_AVAILABLE_FOR_THIS_MODULE' => 'No hay métodos disponibles para este módulo',
	'LBL_FINISH' => 'Terminar',
	'LBL_NO_TASKS_ADDED' => 'Sin Tareas',
	'LBL_CANNOT_DELETE_DEFAULT_WORKFLOW' => 'No se puede eliminar el flujo de trabajo por defecto',
	'LBL_MODULES_TO_CREATE_RECORD' => 'Módulos para crear registro',
	'LBL_EXAMPLE_EXPRESSION' => 'Expresión',
	'LBL_EXAMPLE_RAWTEXT' => 'Texto',
	'LBL_VTIGER' => 'Vtiger',
	'LBL_EXAMPLE_FIELD_NAME' => 'Campo',
	'LBL_NOTIFY_OWNER' => 'notify_owner',
	'LBL_ANNUAL_REVENUE' => 'annual_revenue',
	'LBL_EXPRESSION_EXAMPLE2' => "if mailingcountry == 'India' then concat(firstname,' ',lastname) else concat(lastname,' ',firstname) end",
	'LBL_FROM' => 'De',
	'Optional' => 'Opcional',
	'LBL_ADD_TASK' => 'Añadir Tarea',
        'Portal Pdf Url' =>'URL del PDF para el Portal del Cliente',
        'LBL_ADD_TEMPLATE' => 'Añadir plantilla',
        'LBL_LINEITEM_BLOCK_GROUP' => 'Impuesto de Grupo',
        'LBL_LINEITEM_BLOCK_INDIVIDUAL' => 'Impuesto Individual',
        'LBL_RUN_WORKFLOW' => 'Ejecutar flujo de trabajo',
	'LBL_AT_TIME' => 'En el Tiempo',
	'LBL_HOURLY' => 'Cada hora',
	
	'LBL_DAILY' => 'Diario',
	'LBL_WEEKLY' => 'Semanal',
	'LBL_ON_THESE_DAYS' => 'En estos días',
	'LBL_MONTHLY_BY_DATE' => 'Mensualmente por fecha',
	'LBL_MONTHLY_BY_WEEKDAY' => 'Mensualmente por día de la semana',
	'LBL_YEARLY' => 'Anual',
	'LBL_SPECIFIC_DATE' => 'En Específica Fecha',
	'LBL_CHOOSE_DATE' => 'Seleccionar fecha',
	'LBL_SELECT_MONTH_AND_DAY' => 'Seleccione Mes y Fecha',
	'LBL_SELECTED_DATES' => 'Fechas Seleccionadas',
	'LBL_EXCEEDING_MAXIMUM_LIMIT' => 'Superado el límite máximo',
	'LBL_NEXT_TRIGGER_TIME' => 'Tiempo de activación en Siguiente en',
	'LBL_MESSAGE' => 'Mensaje',
	//Translation for module
	'Calendar' => 'Calendario',
);

$jsLanguageStrings = array(
	'JS_STATUS_CHANGED_SUCCESSFULLY' => 'Se ha cambiado el estado correctamente',
	'JS_TASK_DELETED_SUCCESSFULLY' => 'Se ha eliminado la tarea',
	'JS_SAME_FIELDS_SELECTED_MORE_THAN_ONCE' => 'Se ha seleccionado el mismo campo más de una vez',
	'JS_WORKFLOW_SAVED_SUCCESSFULLY' => 'Se ha guardado el flujo de trabajo',
);