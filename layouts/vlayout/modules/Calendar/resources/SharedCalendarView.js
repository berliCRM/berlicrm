/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/


Calendar_CalendarView_Js("SharedCalendar_SharedCalendarView_Js",{

	currentInstance : false,

	initiateCalendarFeeds : function() {
		Calendar_CalendarView_Js.currentInstance.performCalendarFeedIntiate();
	}
},{

	getAllUserColors : function() {
		var result = {};
		var calendarfeeds = jQuery('[data-calendar-feed]');

		calendarfeeds.each(function(index,element){
			var feedcheckbox = jQuery(element);
			var disabledOnes = app.cacheGet('calendar.feeds.disabled',[]);
			if (disabledOnes.indexOf(feedcheckbox.data('calendar-sourcekey')) == -1) {
				feedcheckbox.attr('checked',true);
				var id = feedcheckbox.data('calendar-userid');
				result[id] = feedcheckbox.data('calendar-feed-color')+','+feedcheckbox.data('calendar-feed-textcolor');
			}
		});

		return result;
	},

    initCalendarFeeds : function() {
		var thisInstance = this;
		var calendarfeeds = jQuery('[data-calendar-feed]');
        thisInstance.feedtypes = [];
        thisInstance.feedcolors = [];
        thisInstance.feeduserids = [];
        thisInstance.feedtextcolors = [];

		calendarfeeds.each(function(index,element){
			var feedcheckbox = jQuery(element);
			var	disabledOnes = app.cacheGet('calendar.feeds.disabled',[]);
			if (disabledOnes.indexOf(feedcheckbox.data('calendar-sourcekey')) == -1) {
				feedcheckbox.prop('checked',true);
			}
            thisInstance.feedtypes.push("Events");
            thisInstance.feeduserids.push(feedcheckbox.data('calendar-userid'));
            thisInstance.feedcolors.push(feedcheckbox.data('calendar-feed-color'));
            thisInstance.feedtextcolors.push(feedcheckbox.data('calendar-feed-textcolor'));
            // thisInstance.feedtypes.push("Calendar");
            // thisInstance.feeduserids.push(feedcheckbox.data('calendar-userid'));
            // thisInstance.feedcolors.push(feedcheckbox.data('calendar-feed-color'));
            // thisInstance.feedtextcolors.push(feedcheckbox.data('calendar-feed-textcolor'));
		});

        // function to fetch enabled feeds
        this.fetchFeeds = function(start, end, timezone, callback) {
            var params = {
				module: 'Calendar',
				action: 'Feed',
				start: start.format("YYYY-MM-DD"),
				end: end.format("YYYY-MM-DD"),
				type: thisInstance.feedtypes,
				userid: thisInstance.feeduserids,
				color : thisInstance.feedcolors,
				textColor : thisInstance.feedtextcolors
			}
            AppConnector.request(params).then(function(events){
                callback(events);
            });
        }

        // add function as event source
        this.getCalendarView().fullCalendar('addEventSource', this.fetchFeeds);
	},

    // assemble arrays of activated feed's parameters
    // collectFeeds : function() {
        // var thisInstance = this;
        // var calendarfeeds = jQuery('[data-calendar-feed]');
        // thisInstance.feedtypes = [];
        // thisInstance.feedcolors = [];
        // thisInstance.feedtextcolors = [];
        // thisInstance.feeduserids = [];
        // calendarfeeds.each(function(index,element){
            // var feedcheckbox = jQuery(element);
            // if (feedcheckbox.prop("checked")) {
                // thisInstance.feedtypes.push("Events");
                // thisInstance.feedcolors.push(feedcheckbox.data('calendar-feed-color'));
                // thisInstance.feedtextcolors.push(feedcheckbox.data('calendar-feed-textcolor'));
                // thisInstance.feeduserids.push(feedcheckbox.data('calendar-userid'));
                // thisInstance.feedtypes.push("Calendar");
                // thisInstance.feedcolors.push(feedcheckbox.data('calendar-feed-color'));
                // thisInstance.feedtextcolors.push(feedcheckbox.data('calendar-feed-textcolor'));
                // thisInstance.feeduserids.push(feedcheckbox.data('calendar-userid'));
            // }
            // console.log( thisInstance.feeduserids);
        // })
    // },
    
	allocateColorsForAllUsers : function() {
		var calendarfeeds = jQuery('[data-calendar-feed]');
		calendarfeeds.each(function(index,element){
			var feedUserElement = jQuery(element);
			var feedUserLabel = feedUserElement.closest('.addedCalendars').find('.label');
			var sourcekey = feedUserElement.data('calendar-sourcekey');
			var color = feedUserElement.data('calendar-feed-color');
			if(color == '' || typeof color == 'undefined') {
				color = app.cacheGet(sourcekey);
				if(color != null){
				} else {
					color = '#'+(0x1000000+(Math.random())*0xffffff).toString(16).substr(1,6);
					app.cacheSet(sourcekey, color);
				}
				feedUserElement.data('calendar-feed-color',color);
				feedUserLabel.css({'background-color':color});
			}
			var colorContrast = app.getColorContrast(color.slice(1));
			if(colorContrast == 'light') {
				var textColor = 'black'
			} else {
				textColor = 'white'
			}
			feedUserElement.data('calendar-feed-textcolor',textColor);
			feedUserLabel.css({'color':textColor});
		});

	},

	isAllowedToAddCalendarEvent : function(calendarDetails){
		var assignedUserId = calendarDetails.assigned_user_id.value;
		if(jQuery('[data-calendar-userid='+assignedUserId+']').is(':checked')) {
			return true;
		} else {
			return false;
		}
	},

	addCalendarEvent : function(calendarDetails) {
		if(calendarDetails.activitytype.value == 'Task'){
			var msg = app.vtranslate('JS_TASK_IS_SUCCESSFULLY_ADDED_TO_YOUR_CALENDAR');
			var customParams = {
				text : msg,
				 type: 'info'
			}
			Vtiger_Helper_Js.showPnotify(customParams);
			return;
		} else {
			this._super(calendarDetails);
		}
	},

	/**
	 * Function used to delete user calendar
	 */
	deleteCalendarView : function(feedcheckbox) {
		var aDeferred = jQuery.Deferred();
		var thisInstance = this;
		var params = {
			module: 'Calendar',
			action: 'CalendarUserActions',
			mode : 'deleteUserCalendar',
			userid : feedcheckbox.data('calendar-userid')
		}

		AppConnector.request(params).then(function(response) {
			var result = response['result'];

			feedcheckbox.closest('.addedCalendars').remove();
			//After delete user reset accodion height to auto
			thisInstance.resetAccordionHeight();
			//Remove the events of deleted user in shared calendar feed
            thisInstance.collectFeeds();
            thisInstance.getCalendarView().fullCalendar('refetchEvents');

			//Update the adding and editing users list in hidden modal
			var userSelectElement = jQuery('#calendarview-feeds').find('[name="usersCalendarList"]');
			userSelectElement.append('<option value="'+result['sharedid']+'">'+result['username']+'</option>');
			var editUserSelectElement = jQuery('#calendarview-feeds').find('[name="editingUsersList"]');
			editUserSelectElement.find('option[value="'+result['sharedid']+'"]').remove();
			jQuery('#calendarview-feeds').find('.invisibleCalendarViews').val('true');

			aDeferred.resolve();
		},
		function(error){
			aDeferred.reject();
		});

		return aDeferred.promise();
	},

	/**
	 * Function to register event for edit user calendar color
	 */
	registerEventForEditUserCalendar : function() {
		var thisInstance = this;
		var parentElement = jQuery('#calendarview-feeds');
		parentElement.on('click', '.editCalendarColor', function(e) {
			e.preventDefault();
			var currentTarget = jQuery(e.currentTarget);
			var addedCalendarEle = currentTarget.closest('.addedCalendars');
			var feedUserEle = addedCalendarEle.find('[data-calendar-feed]');
			var editCalendarViewsList = jQuery('#calendarview-feeds').find('.editCalendarViewsList');
			var selectElement = editCalendarViewsList.find('[name="editingUsersList"]');
			selectElement.find('option:selected').removeAttr('selected');
			selectElement.find('option[value="'+feedUserEle.data('calendar-userid')+'"]').attr('selected', true);
			thisInstance.showAddUserCalendarModal(currentTarget);
		})
	},

	/**
	 * Function to register change event for users list select element in edit user calendar modal
	 */
	registerViewsListChangeEvent : function(data) {
		var parentElement = jQuery('#calendarview-feeds');
		var selectElement = data.find('[name="editingUsersList"]');
		var selectedUserColor = data.find('.selectedUserColor');
		//on change of edit user, update color picker with the selected user color
		selectElement.on('change', function() {
			var userid = selectElement.find('option:selected').val();
			var userColor = jQuery('[data-calendar-userid="'+userid+'"]', parentElement).data('calendar-feed-color');
			selectedUserColor.val(userColor);
			data.find('.calendarColorPicker').ColorPickerSetColor(userColor)
		});
	},

	/**
	 * Function to save added user calendar
	 */
	saveUserCalendar : function(data, currentEle) {
		var thisInstance = this;
		var userColor = data.find('.selectedUserColor').val();
		var userId = data.find('.selectedUser').val();
		var userName = data.find('.selectedUser').data('username');
		var params = {
			module: 'Calendar',
			action: 'CalendarUserActions',
			mode : 'addUserCalendar',
			selectedUser : userId,
			selectedColor : userColor
		};

		AppConnector.request(params).then(function() {
			app.hideModalWindow();

			var parentElement = jQuery('#calendarview-feeds');
			var colorContrast = app.getColorContrast(userColor.slice(1));
			if(colorContrast == 'light') {
				var textColor = 'black'
			} else {
				textColor = 'white'
			}
			if(data.find('.userCalendarMode').val() == 'edit') {
				var feedUserEle = jQuery('[data-calendar-userid="'+userId+'"]', parentElement);
				feedUserEle.data('calendar-feed-color',userColor).data('calendar-feed-textcolor',textColor);
				feedUserEle.closest('.addedCalendars').find('.label').css({'background-color':userColor,'color':textColor});

				//notification message
				var message = app.vtranslate('JS_CALENDAR_VIEW_COLOR_UPDATED_SUCCESSFULLY');
			} else {
				var labelModal = jQuery('.labelModal', parentElement);
				var clonedContainer = labelModal.clone(true, true);
				var labelView = clonedContainer.find('label');
				feedUserEle = labelView.find('[type="checkbox"]');
				feedUserEle.attr('checked', 'checked');
				feedUserEle.attr('data-calendar-feed-color',userColor).attr('data-calendar-feed', 'Events').attr('data-calendar-userid', userId)
						.attr('data-calendar-sourcekey', 'Events33_'+userId).attr('data-calendar-feed-textcolor',textColor);
				feedUserEle.closest('.addedCalendars').find('.label').css({'background-color':userColor,'color':textColor}).text(userName);
				parentElement.append(labelView);

				//After add user reset accodion height to auto
				thisInstance.resetAccordionHeight();

				//Update the adding and editing users list in hidden modal
				var userSelectElement = jQuery('#calendarview-feeds').find('[name="usersCalendarList"]');
				userSelectElement.find('option[value="'+userId+'"]').remove();

				if(userSelectElement.find('option').length <= 0) {
					jQuery('#calendarview-feeds').find('.invisibleCalendarViews').val('false');
				}

				var editUserSelectElement = jQuery('#calendarview-feeds').find('[name="editingUsersList"]');
				editUserSelectElement.append('<option value="'+userId+'">'+userName+'</option>');

				//notification message
				var message = app.vtranslate('JS_CALENDAR_VIEW_ADDED_SUCCESSFULLY');
			}
            thisInstance.collectFeeds();
            thisInstance.getCalendarView().fullCalendar('refetchEvents');
			//show notification after add or edit user
			var params = {
				text: message,
				type: 'info'
			};
			Vtiger_Helper_Js.showPnotify(params);
		},
		function(error){

		});

	},

	performCalendarFeedIntiate : function() {
		this.allocateColorsForAllUsers();
		this.registerCalendarFeedChange();
		this.initCalendarFeeds();
		this.registerEventForDeleteUserCalendar();
		this.registerEventForEditUserCalendar();
		this.resetAccordionHeight();
	},

	restoreAddCalendarWidgetState : function() {
		var key = 'Calendar_sideBar_LBL_ADDED_CALENDARS';
		var value = app.cacheGet(key);
		var widgetContainer = jQuery("#Calendar_sideBar_LBL_ADDED_CALENDARS");
		if(value == 0){
			Vtiger_Index_Js.loadWidgets(widgetContainer,false);
		}
		else{
			Vtiger_Index_Js.loadWidgets(widgetContainer);
		}
	},

	registerEvents : function() {
		this._super();
		this.restoreAddCalendarWidgetState();
		return this;
	}
});
