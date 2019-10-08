{strip}
<!DOCTYPE html>
<head>
<title>{vtranslate('LBL_CONFIG', 'crmtogo')}</title> 
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8"> 
<link REL="SHORTCUT ICON" HREF="../../layouts/vlayout/modules/crmtogo/resources/images/favicon.ico">	
<script type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/jquery-1.11.2.min.js"></script>
<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/jquery.mobile.icons.min.css" >
<script  type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/jquery-ui.min.js"></script>
<script>  
    // rename to avoid conflict with jquery mobile
    $.fn.uislider = $.fn.slider;
</script>
<script type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/jquery.mobile-1.4.5.min.js"></script>
<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/jquery.mobile.structure-1.4.5.min.css" >
<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/theme.css" >
<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/theme.css" >
<link rel="stylesheet" href="../../layouts/vlayout/modules/crmtogo/resources/css/jquery-ui.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<style>
  /* !important is needed sometimes */
 ::-webkit-scrollbar {
    width: 12px !important;
 }

 /* Track */
::-webkit-scrollbar-track {
   -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3) !important;
   -webkit-border-radius: 10px !important;
   border-radius: 10px !important;
 }

 /* Handle */
 ::-webkit-scrollbar-thumb {
   -webkit-border-radius: 10px !important;
   border-radius: 10px !important;
   background: #41617D !important; 
   -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5) !important; 

 }
 ::-webkit-scrollbar-thumb:window-inactive {
   background: #41617D !important; 
 }
</style>
</head>
<body>
<div data-role="page" data-theme="b" id="settings_page" >
	<div id="header" data-role="header" data-theme="{$COLOR_HEADER_FOOTER}" data-position="fixed" class="ui-grid-b ui-responsive">
		<h4>{vtranslate('LBL_CONFIG','crmtogo')}</h4>
		<a href="#"  onclick="window.history.back()" class="ui-btn ui-corner-all ui-icon-back ui-btn-icon-notext">{vtranslate('LBL_CANCEL', 'crmtogo')}</a>
	</div>
    <form>
	<div role="main" class="ui-content">
 		<div>
			{vtranslate('LBL_SETTINGS_COMMENT', 'crmtogo')}
		</div>
		<div class="ui-field-contain">
			<ul data-role="listview" data-divider-theme="b" data-inset="true" id="sortable">
            <li data-role="list-divider" role="heading">{vtranslate('LBL_ACTIVE_MODULE', 'crmtogo')}</li>
			{foreach item=_MODULE from=$_MODULES}
				{if $_MODULE->name() neq 'Events'}
				<li data-theme="c" id={$_MODULE->name()}>
					<div data-role="fieldcontain">
						<label for="flip_{$_MODULE->name()}"><span style="display: inline-block" class="ui-icon ui-icon-arrowthick-2-n-s"></span>{vtranslate($_MODULE->label(), 'crmtogo')}:</label>
						{if $_MODULE->active() eq 1}
							<input data-role="flipswitch" data-on-text="{vtranslate('LBL_ON', 'crmtogo')}" data-off-text="{vtranslate('LBL_OFF', 'crmtogo')}" name="flip_{$_MODULE->name()}" id="flip_{$_MODULE->name()}" checked="" type="checkbox" >
						{else}
							<input data-role="flipswitch" data-on-text="{vtranslate('LBL_ON', 'crmtogo')}" data-off-text="{vtranslate('LBL_OFF', 'crmtogo')}" name="flip_{$_MODULE->name()}" id="flip_{$_MODULE->name()}" type="checkbox">		 
						{/if}
					</div>
				</li>
				{/if}
			{/foreach}
			</ul>
			<div>
				<fieldset data-role="controlgroup" id="themecolor">
					<legend>{vtranslate('LBL_THEME_SELECTION', 'crmtogo')}</legend>
						{assign var=$COLOR_HEADER_FOOTER|cat:"theme" value='checked="checked"'}
						<input type="radio" name="radio-choice-2" id="radio-choice-21" value="a" data-theme="c" {$atheme} />
						<label for="radio-choice-21">{vtranslate('LBL_THEME_COLOR_A', 'crmtogo')}</label>

						<input type="radio" name="radio-choice-2" id="radio-choice-22" value="b" data-theme="c" {$btheme} />
						<label for="radio-choice-22">{vtranslate('LBL_THEME_COLOR_B', 'crmtogo')}</label>

						<input type="radio" name="radio-choice-2" id="radio-choice-23" value="c" data-theme="c"{$ctheme} />
						<label for="radio-choice-23">{vtranslate('LBL_THEME_COLOR_C', 'crmtogo')}</label>
				</fieldset>
			</div>		
			<div>
				<label for="slider-1">{vtranslate('LBL_NAVI_SELECTION', 'crmtogo')}</label>
				<input type="range" name="navislider" id="navislider" data-theme="b" data-track-theme="c" value="{$NAVISETTING}" min="5" max="25" data-highlight="true">
			</div>
		</div>
	</div>	
	</form>
	<div  id="footer" data-role="footer" data-theme="{$COLOR_HEADER_FOOTER}" data-position="fixed">
		{vtranslate('LBL_COPYRIGHTS', 'crmtogo')}
	</div>
</div>
</body>
jQuery(document).ready(function($) {
<script type="text/javascript" src="../../layouts/vlayout/modules/crmtogo/resources/settings.js" ></script>


});
{/strip}