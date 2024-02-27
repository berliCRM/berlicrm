/* +***********************************************************************************************************************************
 * The contents of this file are subject to the berliCRM Public License Version 1.0 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is from the crm-now GmbH.
 * The Initial Developer of the Original Code is crm-now. Portions created by crm-now are Copyright (C) www.crm-now.de. 
 * Portions created by vtiger are Copyright (C) www.vtiger.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */
var Settings_ListViewColors_Js = {

	registerModuleChangeEvent : function() {
		jQuery('#pickListModules').on('change',function(e){
            var element = jQuery(e.currentTarget);
            var selectedModule = jQuery(e.currentTarget).val();
            if(selectedModule.length <= 0) {
                Settings_Vtiger_Index_Js.showMessage({'type': 'error','text':app.vtranslate('JS_PLEASE_SELECT_MODULE')});
 				Settings_ListViewColors_Js.getColorFieldsForModule('');
				return;
            }
			jQuery('#colorFieldsValuesContainer').html('');
			app.changeSelectElementView(jQuery('#modulePickListContainer'));
			Settings_ListViewColors_Js.getColorFieldsForModule(selectedModule);
		});
	},

	getColorFieldsForModule : function(selectedModule) {
			if (selectedModule =='') {
				//reset Fields
				jQuery('#FieldChoiceContainer').html('<select name="pickListModules" id="pickListModules" class="select2-choice"><option selected value="">'+app.vtranslate('JS_SELECT_FIELD_FIRST')+'</option></select>');
				return;
			}
			var params = {
				module : app.getModuleName(),
				parent : app.getParentModuleName(),
				source_module : selectedModule,
				view : 'IndexAjax',
				mode : 'getColorFieldsForModule'
			}
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(params).then(function(data){
				jQuery('#FieldChoiceContainer').html(data);
				progressIndicatorElement.progressIndicator({'mode':'hide'});
			})

	},
	
	registerModuleFieldListChangeEvent : function() {
		jQuery('#FieldChoiceContainer').on('change',function(e){
			if (jQuery('#pickListModules').val() =='') {
                Settings_Vtiger_Index_Js.showMessage({'type': 'error','text':app.vtranslate('JS_PLEASE_SELECT_MODULE')});
                return;
			}
            var element = jQuery(e.currentTarget);
            var selectedField = jQuery(e.currentTarget).find("#coloredFieldsList option:selected").val();
            if(selectedField.length <= 0) {
                Settings_Vtiger_Index_Js.showMessage({'type': 'error','text':app.vtranslate('JS_PLEASE_SELECT_FIELD')});
                return;
            }
			var params = {
				module : app.getModuleName(),
				parent : app.getParentModuleName(),
				source_module : jQuery('#pickListModules').val(),
				view : 'IndexAjax',
				mode : 'getColorValuesForField',
				selectedField : selectedField
			}
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(params).then(function(data){
				jQuery('#colorFieldsValuesContainer').html(data);
				progressIndicatorElement.progressIndicator({'mode':'hide'});
				//register event after DOM is created
				Settings_ListViewColors_Js.registerRecyclingBinClickEvent();
				Settings_ListViewColors_Js.initializeColorpickerForFields();
			})
		})
	},
	
	initializeColorpickerForFields : function() {
		var fieldids = [];
		 $('[id^=colorSelectorValueid_]').each(function () {
			fieldids.push('#'+$(this).attr('id'));
		  });
		var fields = fieldids.toString(); 

		$(fields).ColorPicker({
			onSubmit: function(hsb, hex, rgb, el) {
				var pickListModules = app.getModuleName();
				var params = {
					module : app.getModuleName(),
					parent : app.getParentModuleName(),
					source_module : jQuery('#pickListModules').val(),
					view : 'IndexAjax',
					mode : 'saveColorValuesForField',
					selectedColor : '#'+hex,
					selectedField : $(el).attr('name'),
					selectedValue : $(el).attr('id'),
					recordValue : $(el).attr('data-recordvalue')
				}
				var progressIndicatorElement = jQuery.progressIndicator({
					'position' : 'html',
					'blockInfo' : {
						'enabled' : true
					}
				});
				AppConnector.request(params).then(function(data){
					progressIndicatorElement.progressIndicator({'mode':'hide'});
				})
				$(el).val('#'+hex);
				$(el).css('backgroundColor', '#'+hex);
				$(el).ColorPickerHide();
			},
			onBeforeShow: function () {
				$(this).ColorPickerSetColor(this.value);
			}
		})
		.bind('keyup', function(){
			$(this).ColorPickerSetColor(this.value);
		});
	},
	
	registerRecyclingBinClickEvent : function() {
		$('[id^=removecolor_]').click(function(e) {
			e.stopPropagation();           
			var num = this.id.replace('removecolor_','');
			var params = {
				module : app.getModuleName(),
				parent : app.getParentModuleName(),
				source_module : jQuery('#pickListModules').val(),
				view : 'IndexAjax',
				mode : 'saveColorValuesForField',
				selectedColor : '',
				selectedField : this.name,
				selectedValue : this.id,
				recordValue : $('#colorSelectorValueid_'+num).attr('data-recordvalue')
			}
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(params).then(function(data){
				progressIndicatorElement.progressIndicator({'mode':'hide'});
				$('#colorSelectorValueid_'+num).css('background','#FFFFFF');
				$('#colorSelectorValueid_'+num).val('');
				$('#colorSelectorValueid_'+num).html('');
			})
        });
	},

	registerEvents : function() {
		Settings_ListViewColors_Js.registerModuleChangeEvent();
		Settings_ListViewColors_Js.registerModuleFieldListChangeEvent();
	}
}

jQuery(document).ready(function(){
	Settings_ListViewColors_Js.registerEvents();
	$("#pickListModules").removeAttr('disabled');
})
