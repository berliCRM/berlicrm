<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *******************************************************************************
 * Vicus eBusiness Solutions (www.vicus.nl) Version Control
 * Dutch language pack for vtiger CRM version 6.x
 * 
 * @package   NL-Dutch
 * @version   $Author: luuk $
 * @version   CVS: $Revision: 1.4 $ $Date: 2014/09/07 21:06:45 $
 * @source    $Source: /var/lib/cvs/vtiger600/languages/nl_nl/Import.php,v $
 * @copyright Copyright (c)2005-2014 Vicus eBusiness Solutions B.V. <info@vicus.nl>
 * @link      http://www.vicus.nl
 ************************************************************************************/
$languageStrings = array(
    'LBL_IMPORT_STEP_1'            => 'Stap 1'                      , 
    'LBL_IMPORT_STEP_1_DESCRIPTION' => 'Selecteer Bestand'          , 
    'LBL_IMPORT_SUPPORTED_FILE_TYPES' => '	Ondersteunde bestandstype(s): .CSV, .VCF', 
    'LBL_IMPORT_STEP_2'            => 'Stap 2'                      , 
    'LBL_IMPORT_STEP_2_DESCRIPTION' => 'Specificeer Formaat'         , 
    'LBL_FILE_TYPE'                => 'Bestandstype'                , 
    'LBL_CHARACTER_ENCODING'       => 'Tekencodering'               , 
    'LBL_DELIMITER'                => 'Scheidingsteken:'            , 
    'LBL_HAS_HEADER'               => 'Heeft koptekst'              , 
    'LBL_IMPORT_STEP_3'            => 'Stap 3'                      , 
    'LBL_IMPORT_STEP_3_DESCRIPTION' => 'Verwerk dubbele records'   , 
    'LBL_IMPORT_STEP_3_DESCRIPTION_DETAILED' => 'Selecteer deze optie om merge criteria te selecteren en aan te zetten', 
    'LBL_SPECIFY_MERGE_TYPE'       => 'Selecteer hoe dubbele records verwerkt moeten worden', 
    'LBL_SELECT_MERGE_FIELDS'      => 'Selecteer de matchende velden om de dubbele records te identificeren', 
    'LBL_AVAILABLE_FIELDS'         => 'Beschikbare velden'          , 
    'LBL_SELECTED_FIELDS'          => ' Velden om te vergelijking'  , 
    'LBL_NEXT_BUTTON_LABEL'        => 'Volgende'                    , 
    'LBL_IMPORT_STEP_4'            => 'Stap 4'                      , 
    'LBL_IMPORT_STEP_4_DESCRIPTION' => 'Map de Kolommen aan de Modulevelden', 
    'LBL_FILE_COLUMN_HEADER'       => 'Kop'                         , 
    'LBL_ROW_1'                    => 'Rij 1'                       , 
    'LBL_CRM_FIELDS'               => 'CRM Velden'                  , 
    'LBL_DEFAULT_VALUE'            => 'Standaardwaarde'             , 
    'LBL_SAVE_AS_CUSTOM_MAPPING'   => 'Opslaan als eigen mapping '  , 
    'LBL_IMPORT_BUTTON_LABEL'      => 'Importeer'                   , 
    'LBL_RESULT'                   => 'Resultaat'                   , 
    'LBL_TOTAL_RECORDS_IMPORTED'   => 'Records successvol ge&iuml;mporteerd', 
    'LBL_NUMBER_OF_RECORDS_CREATED' => 'Records aangemaakt'          , 
    'LBL_NUMBER_OF_RECORDS_UPDATED' => 'Records overschreven'        , 
    'LBL_NUMBER_OF_RECORDS_SKIPPED' => 'Records overgeslagen'        , 
    'LBL_NUMBER_OF_RECORDS_MERGED' => 'Records samengevoegd'        , 
    'LBL_TOTAL_RECORDS_FAILED'     => 'Records gefaalt bij import'  , 
    'LBL_IMPORT_MORE'              => 'Importeer meer'              , 
    'LBL_VIEW_LAST_IMPORTED_RECORDS' => 'Laatst Ge&iuml;porteerde Records', 
    'LBL_UNDO_LAST_IMPORT'         => 'Annuleer laatste import'     , 
    'LBL_FINISH_BUTTON_LABEL'      => 'Afmaken'                     , 
    'LBL_UNDO_RESULT'              => 'Importresultaat terugdraaien', 
    'LBL_TOTAL_RECORDS'            => 'Totaal aantal Records'       , 
    'LBL_NUMBER_OF_RECORDS_DELETED' => 'Aantal records verwijderd'   , 
    'LBL_OK_BUTTON_LABEL'          => 'Ok'                          , 
    'LBL_IMPORT_SCHEDULED'         => 'Import Ingepland'            , 
    'LBL_RUNNING'                  => 'Loopt'                       , 
    'LBL_CANCEL_IMPORT'            => 'Import annuleren'            , 
    'LBL_ERROR'                    => 'Foutmelding:'                , 
    'LBL_CLEAR_DATA'               => 'Gegevens leegmaken'          , 
    'ERR_UNIMPORTED_RECORDS_EXIST' => 'Het is niet mogelijk om meer gegevens in deze bacht te importeren. Start a.u.b. een nieuwe import.', 
    'ERR_IMPORT_INTERRUPTED'       => 'De huidige import wordt verstoord. Probeer het later', 
    'ERR_FAILED_TO_LOCK_MODULE'    => 'Fout bij het reserveren van de module om te importeren. Probeer het later.', 
    'LBL_SELECT_SAVED_MAPPING'     => 'Selecteer Opgeslagen mapping', 
    'LBL_IMPORT_ERROR_LARGE_FILE'  => 'Importfout groot bestand '    ,
    'LBL_FILE_UPLOAD_FAILED'       => 'Bestandsupload is gefaald'          ,
    'LBL_IMPORT_CHANGE_UPLOAD_SIZE' => 'Import Wijzig Omvang Bestandsupload'   , // TODO: Review
    'LBL_IMPORT_DIRECTORY_NOT_WRITABLE' => 'Importmap is niet schrijfbaar',
    'LBL_IMPORT_FILE_COPY_FAILED'  => 'Import bestand kopieeractie gefaald'     , // TODO: Review
    'LBL_INVALID_FILE'             => 'Ongeldig bestandsformaat'           ,
    'LBL_NO_ROWS_FOUND'            => 'Geen regels gevonden'               ,
    'LBL_SCHEDULED_IMPORT_DETAILS' => 'Import is ingepland en zal starten binnen 15 minuten. U zult een e-mail ontvangen zodra de import is afgerond.<br> <br>Zorg dat de uitgaande e-mail server is ingesteld en uw e-mailadres is geconfigureerd om deze e-mail te ontvangen',
    'LBL_DETAILS'                  => 'Details'                     ,
    'skipped'                      => 'Overgeslagen Records'        ,
    'failed'                       => 'Gefaalde Records'            ,
);