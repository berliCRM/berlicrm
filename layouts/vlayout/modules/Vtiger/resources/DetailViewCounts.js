/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

(function($) {
	'use strict';

	var countsCompleteEvent = 'vtigerDetailViewCountsComplete';
	var deferredWidgetLoads = {};

	function hasUnfinishedRelatedCounts() {
		var unfinished = false;
		$('.detailViewContainer .detailViewInfo > .related').each(function() {
			var sidebar = $(this);
			if (sidebar.attr('data-related-counts-complete') !== '1' &&
					(sidebar.attr('data-related-counts-requested') === '1' ||
					sidebar.find(
							'.detailViewRelatedCount[data-count-pending="1"]').length)) {
				unfinished = true;
				return false;
			}
		});
		return unfinished;
	}

	function runOrDeferWidgetLoad(loadKey, callback) {
		if (!hasUnfinishedRelatedCounts()) {
			return callback();
		}

		// Only the newest call of each loader is relevant if AJAX navigation
		// replaces the detail contents before the count request has completed.
		deferredWidgetLoads[loadKey] = callback;
	}

	function flushDeferredWidgetLoads() {
		if (hasUnfinishedRelatedCounts()) {
			return;
		}

		var widgetLoads = deferredWidgetLoads;
		deferredWidgetLoads = {};
		$.each(widgetLoads, function(loadKey, callback) {
			callback();
		});
	}

	function deferWidgetLoader(owner, methodName, loadKey) {
		if (!owner || typeof owner[methodName] !== 'function' ||
				owner[methodName].waitsForDetailViewCounts) {
			return;
		}

		var originalMethod = owner[methodName];
		var deferredMethod = function() {
			var context = this;
			var args = arguments;
			return runOrDeferWidgetLoad(loadKey, function() {
				return originalMethod.apply(context, args);
			});
		};
		deferredMethod.waitsForDetailViewCounts = true;
		owner[methodName] = deferredMethod;
	}

	function deferInitialWidgetLoads() {
		// Sidebar widgets and summary/detail widgets have separate loaders. Gate
		// both globally so every module gives the cheaper badge request priority.
		if (typeof Vtiger_Index_Js !== 'undefined') {
			deferWidgetLoader(
					Vtiger_Index_Js, 'loadWidgetsOnLoad', 'sidebarWidgets');
		}
		if (typeof Vtiger_Detail_Js !== 'undefined') {
			deferWidgetLoader(
					Vtiger_Detail_Js.prototype, 'loadWidgets', 'detailWidgets');
		}
	}

	function getRecordId(sidebar) {
		return sidebar.attr('data-record-id');
	}

	function isCurrentDetailView(detailViewContainer, sidebar, moduleName, recordId) {
		return sidebar.length && $.contains(document.documentElement, sidebar[0]) &&
				String(sidebar.attr('data-module-name')) === String(moduleName) &&
				String(getRecordId(sidebar)) === String(recordId);
	}

	function updateCount(sidebar, countKind, relationId, count) {
		var countElements = sidebar.find(
				'.detailViewRelatedCount[data-count-kind="' + countKind + '"]');
		if (countKind === 'relation') {
			countElements = countElements.filter(function() {
				return $(this).attr('data-relation-id') === String(relationId);
			});
		}

		var recordCount = parseInt(count, 10);
		if (isNaN(recordCount) || recordCount < 0) {
			return;
		}

		countElements.each(function() {
			var countElement = $(this);
			if (countElement.attr('data-count-pending') !== '1' ||
					$.trim(countElement.text()) !== '') {
				return;
			}

			if (recordCount > 0) {
				countElement.text('\u00a0(' + recordCount + ')');
			}
			countElement.attr('data-count-pending', '0');
		});
	}

	function afterFirstPaint(callback) {
		if (window.requestAnimationFrame) {
			window.requestAnimationFrame(function() {
				window.requestAnimationFrame(callback);
			});
		} else {
			window.setTimeout(callback, 0);
		}
	}

	function finishCounts(sidebar) {
		if (sidebar && sidebar.length) {
			sidebar.attr('data-related-counts-complete', '1');
		}
		$(document).trigger(countsCompleteEvent, [sidebar]);

		// Let the freshly inserted badges become visible before the widget
		// requests start competing for browser and server resources.
		afterFirstPaint(flushDeferredWidgetLoads);
	}

	function scheduleCounts(detailViewContainer) {
		var sidebar = detailViewContainer.find('.detailViewInfo > .related').first();
		if (!sidebar.length) {
			finishCounts(sidebar);
			return;
		}

		var pendingCounts = sidebar.find(
				'.detailViewRelatedCount[data-count-pending="1"]');
		var recordId = getRecordId(sidebar);
		var moduleName = sidebar.attr('data-module-name');

		if (!pendingCounts.length) {
			finishCounts(sidebar);
			return;
		}
		if (sidebar.attr('data-related-counts-requested') === '1') {
			return;
		}
		if (!recordId || !moduleName) {
			finishCounts(sidebar);
			return;
		}

		var relationIds = [];
		var seenRelationIds = {};
		pendingCounts.filter('[data-count-kind="relation"]').each(function() {
			var relationId = parseInt($(this).attr('data-relation-id'), 10);
			if (relationId > 0 && !seenRelationIds[relationId]) {
				seenRelationIds[relationId] = true;
				relationIds.push(relationId);
			}
		});
		var includeComments = pendingCounts.filter(
				'[data-count-kind="comments"]').length > 0;

		if (!relationIds.length && !includeComments) {
			finishCounts(sidebar);
			return;
		}

		sidebar.attr({
			'data-related-counts-requested': '1',
			'data-related-counts-complete': '0'
		});
		afterFirstPaint(function() {
			if (!isCurrentDetailView(
					detailViewContainer, sidebar, moduleName, recordId)) {
				finishCounts(sidebar);
				return;
			}

			try {
				var countRequest = AppConnector.request({
					module: moduleName,
					action: 'DetailViewCounts',
					record: recordId,
					relation_ids: JSON.stringify(relationIds),
					include_comments: includeComments ? 1 : 0
				});
				countRequest.then(function(data) {
					try {
						if (!data || !data.result ||
								String(data.result.module) !== String(moduleName) ||
								String(data.result.record) !== String(recordId) ||
								!isCurrentDetailView(
										detailViewContainer, sidebar, moduleName, recordId)) {
							return;
						}

						if (data.result.comments !== null &&
								typeof data.result.comments !== 'undefined') {
							updateCount(sidebar, 'comments', null, data.result.comments);
						}

						if ($.isArray(data.result.relations)) {
							$.each(data.result.relations, function(index, relationCount) {
								updateCount(sidebar, 'relation', relationCount.relationId,
										relationCount.count);
							});
						}
					} finally {
						finishCounts(sidebar);
					}
				}, function() {
					finishCounts(sidebar);
				});
			} catch (error) {
				// A failed optional badge request must never keep widgets blocked.
				finishCounts(sidebar);
			}
		});
	}

	function scanDetailViews() {
		$('.detailViewContainer').each(function() {
			scheduleCounts($(this));
		});
	}

	deferInitialWidgetLoads();

	$(function() {
		scanDetailViews();
		$(document)
				.off('postajaxready.vtigerDetailViewCounts')
				.on('postajaxready.vtigerDetailViewCounts', scanDetailViews);
	});
})(jQuery);
