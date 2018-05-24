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
 * @version   CVS: $Revision: 1.6 $ $Date: 2014/09/07 22:42:08 $
 * @source    $Source: /var/lib/cvs/vtiger600/languages/nl_nl/HelpDesk.php,v $
 * @copyright Copyright (c)2005-2014 Vicus eBusiness Solutions B.V. <info@vicus.nl>
 * @link      http://www.vicus.nl
 ************************************************************************************/
$languageStrings = array(
    'HelpDesk'                     => 'Helpdesk'                    , 
    'SINGLE_HelpDesk'              => 'Ticket'                      , 
    'LBL_ADD_RECORD'               => 'Voeg Ticket toe'             , 
    'LBL_RECORDS_LIST'             => 'Ticketlijst'                 , 
    'LBL_TICKET_INFORMATION'       => 'Ticket informatie'           , 
    'LBL_TICKET_RESOLUTION'        => 'Oplossing'                   , 
    'Ticket No'                    => 'Ticket Nr'                   , 
    'Severity'                     => 'Ernstigheid'                 , 
    'Update History'               => 'Geschiedenis verversen'      , 
    'Hours'                        => 'Uren'                        , 
    'Days'                         => 'Dagen'                       , 
    'Title'                        => 'Titel'                       , 
    'Solution'                     => 'Oplossing'                   , 
    'From Portal'                  => 'Van Portaal'                 , 
    'Big Problem'                  => 'Groot probleem'              , 
    'Small Problem'                => 'Klein probleem'              , 
    'Other Problem'                => 'Probleem'                    , 
    'Normal'                       => 'Normaal'                     , 
    'High'                         => 'Hoog'                        , 
    'Urgent'                       => 'Urgent'                      , 
    'Minor'                        => 'Klein'                       , 
    'Major'                        => 'Groot'                       , 
    'Feature'                      => 'Mogelijkheden'               , 
    'Critical'                     => 'Kritiek'                     , 
    'Open'                         => 'Open'                        , 
    'Wait For Response'            => 'Wacht op reactie'            , 
    'Closed'                       => 'Gesloten'                    , 
    'LBL_STATUS'                   => 'Status'                      , 
    'LBL_SEVERITY'                 => 'Ernstigheid'                 , 
    'LBL_CONVERT_FAQ'              => 'Converteer naar FAQ'         , 
    'LBL_RELATED_TO'               => 'Gerelateerd aan'             ,

    //added to support i18n in ticket mails
    'Hi' => 'Hallo',
    'Dear' => 'Beste',
    'LBL_PORTAL_BODY_MAILINFO' => 'Ticket is',
    'LBL_DETAIL' => 'de omschrijving is:',
    'LBL_REGARDS' => 'met vriendelijke groet',
    'LBL_TEAM' => 'Helpdesk team',
    'LBL_TICKET_DETAILS' => 'Ticket omschrijving',
    'LBL_SUBJECT' => 'Onderwerp: ',
    'created' => 'Gemaakt',
    'replied' => 'Beantwoord',
    'reply' => 'Er is een antwoord naar',
    'customer_portal' => 'in "portaal" van '.$HELPDESK_SUPPORT_NAME.'.',
    'link' => 'Via de de volgende link zijn de gegeven antwoorden te bekijken:',
    'Thanks' => 'Bedankt',
    'Support_team' => $HELPDESK_SUPPORT_NAME,

    //this label for customerportal.
	// 140906 portal is use not only for customers so translated to portaal from now on
    'LBL_STATUS_CLOSED' => 'Closed', //Do not convert this label. This is used to check the status. If the status 'Closed' is changed in vtigerCRM server side then you have to change in customerportal language file also.
    'LBL_STATUS_UPDATE' => 'Ticketstatus is veranderd naar',
    'LBL_COULDNOT_CLOSED' => 'Ticket kan niet',
    'LBL_CUSTOMER_COMMENTS' => 'De klant heeft de volgende informatie toegevoegd aan uw antwoord:',
    'LBL_RESPOND' => 'Gaarne vroegtijdig bericht op bovengenoemde ticket.',
    'LBL_REGARDS' => 'Met vriendelijke groet',
    'LBL_SUPPORT_ADMIN' => 'Ondersteuningsbeheerder',
    'LBL_RESPONDTO_TICKETID' => 'Reageer op Ticketnummer',
    'LBL_RESPONSE_TO_TICKET_NUMBER' => 'Reactie op Ticketnummer',
    'LBL_CUSTOMER_PORTAL' => 'in portaal - SPOED',
    'LBL_LOGIN_DETAILS' => 'Hierbij uw portaal logingegevens:',
    'LBL_MAIL_COULDNOT_SENT' => 'E-mail kon niet verstuurd worden',
    'LBL_USERNAME' => 'Gebruikersnaam :',
    'LBL_PASSWORD' => 'Wachtwoord :',
    'LBL_SUBJECT_PORTAL_LOGIN_DETAILS' => 'Met betrekking tot uw portaal login gegevens',
    'LBL_GIVE_MAILID' => 'Gaarne uw e-mailadres',
    'LBL_CHECK_MAILID' => 'Gaarne uw e-mailadres voor de klanten portal bekijken',
    'LBL_LOGIN_REVOKED' => 'Uw inlogaccount is ingetrokken. Neem contact op met uw accountmanager.',
    'LBL_MAIL_SENT' => 'Een e-mail is naar uw e-mailadres verstuurd met daarin Portaal login gegevens',
    'LBL_ALTBODY' => 'Dit is een e-mail pagina met platte tekst voor niet HTML e-mail clients',
    'HelpDesk ID' => 'Ticket ID',
    'Ticket ID' => 'Ticket ID',
    'LBL_TICKET_NUMBER' => 'Ticketnmmer',
);

// veb 140903 added, maybe missing a label?
$languageStrings['High Prioriy Tickets'] = 'Hoge Prioriteit Tickets'; # auto added 2014-04-17 20:04:19