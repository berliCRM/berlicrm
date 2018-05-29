/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

 jQuery.Class("Settings_Google_Index_Js",{

    saveGoogleParameter : function() {
		jQuery('#savegoogleconfig').click(function(e){
			e.preventDefault();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
			var form = jQuery('form[name="googlesettings"]');
			var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.mapapikey == '')) {
                data = {
                    title : app.vtranslate('KEY_REQUIRED'),
                    text: app.vtranslate('LBL_SAVE_ERROR'),
					animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }

			var params = 'index.php?module=Google&view=List&parent=Settings&action=saveGoogleSettings&inputdata='+jQuery.param(inputdata);
			AppConnector.request(params).then(
				function(data) {
					var responseData  = JSON.parse(data);
					if (responseData.result['success'] ==true) {
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_SUCCESS'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					}
					else {
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_SAVE_ERROR'),
                            animation: 'show',
                            type: 'error'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					}
				}
			);
			return true;
        });
    },
    checkGoogleMapKey : function() {
		jQuery('#checkmapdatakey').click(function(e){
			e.preventDefault();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
			if( $('#map_image').length ) {
				jQuery("#map_canvas").children("img").remove();
			}
			var form = jQuery('form[name="googlesettings"]');
			var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.mapapikey == '')) {
                data = {
                    title : app.vtranslate('KEY_REQUIRED'),
                    text: app.vtranslate('LBL_CHECK_ERROR'),
					animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }
			var address = jQuery("#mapaddress").val();
			var encoded_address=encodeURIComponent(address);
			var map_url=" https://maps.googleapis.com/maps/api/staticmap?size=250x250&maptype=roadmap&markers=size:mid%7Ccolor:red%7C"+encoded_address+"&sensor=false&key="+inputdata.mapapikey;
			jQuery("#map_canvas").append("<img id='map_image'></img>");
			jQuery( "#map_image" ).on( "error", function() {
				var params = {
					title : app.vtranslate('LBL_TEST_FAILURE'),
                    text: app.vtranslate('LBL_INVALID_MAPKEY_ERROR'),
                    animation: 'show',
                    type: 'error'
                };
                Vtiger_Helper_Js.showPnotify(params);
				jQuery("#map_canvas").children("img").remove();
			});	
			jQuery( "#map_image" ).on( "load", function() {
				var params = {
					title : app.vtranslate('LBL_TEST_SUCCESS'),
                    text: app.vtranslate('LBL_CHECK_OK'),
                    animation: 'show',
                    type: 'info'
                };
                Vtiger_Helper_Js.showPnotify(params);
			});	
			jQuery("#map_image").attr("src",map_url); 

        });
    },
	
    checkGoogleGeoKey : function() {
		jQuery('#checkgeodatakey').click(function(e){
			e.preventDefault();
			jQuery.pnotify_remove_all();
			jQuery(window).data("pnotify", []);
			var form = jQuery('form[name="googlesettings"]');
			var inputdata = form.serializeFormData();
			var data = new Object();
			Vtiger_Helper_Js.checkServerConfigResponseCache = '';
 			if( (inputdata.geodataapikey == '')) {
                data = {
                    title : app.vtranslate('KEY_REQUIRED'),
                    text: app.vtranslate('LBL_CHECK_ERROR'),
					animation: 'show',
					type: 'error'
                };
				Vtiger_Helper_Js.showPnotify(data);
                return false;
            }

			var params = 'index.php?module=Google&view=List&parent=Settings&action=checkGoogleSettings&check=geo&geodataapikey='+inputdata.geodataapikey;
			AppConnector.request(params).then(
				function(data) {
					var responseData  = JSON.parse(data);
					if (responseData.result['success'] ==true) {
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_CHECK_OK'),
                            animation: 'show',
                            type: 'info'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					}
					else {
						var error_msg = responseData.result['error'];
						var params = {
							title : app.vtranslate('JS_MESSAGE'),
                            text: app.vtranslate('LBL_INVALID_KEY_ERROR')+error_msg['0']+'. '+app.vtranslate('LBL_INVALID_KEY_HINT'),
                            animation: 'show',
                            type: 'error'
                        };
                        Vtiger_Helper_Js.showPnotify(params);
					}
				}
			);
			return true;
        });
    },
	
    getStaticMapURL : function (address,size,mapapikey){
        var encoded_address=encodeURIComponent(address);
        var url=" https://maps.googleapis.com/maps/api/staticmap?size="+size+"&maptype=roadmap&markers=size:mid%7Ccolor:red%7C"+encoded_address+"&sensor=false&key="+mapapikey;
        return url;
    },

    registerEvents : function(){
        var container = jQuery('.GoogleSettingContainer');
		this.saveGoogleParameter();
		this.checkGoogleMapKey();
		this.checkGoogleGeoKey();
	}

});
