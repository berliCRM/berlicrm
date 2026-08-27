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

	function getRecordId(detailViewContainer) {
		return detailViewContainer.prevAll('input#recordId').first().val();
	}

	function isCurrentDetailView(detailViewContainer, sidebar, moduleName, recordId) {
		return sidebar.length && $.contains(document.documentElement, sidebar[0]) &&
				String(sidebar.attr('data-module-name')) === String(moduleName) &&
				String(getRecordId(detailViewContainer)) === String(recordId);
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

	function scheduleCounts(detailViewContainer) {
		var sidebar = detailViewContainer.find('.detailViewInfo > .related').first();
		var pendingCounts = sidebar.find(
				'.detailViewRelatedCount[data-count-pending="1"]');
		var recordId = getRecordId(detailViewContainer);
		var moduleName = sidebar.attr('data-module-name');

		if (!sidebar.length || !pendingCounts.length || !recordId || !moduleName ||
				sidebar.attr('data-related-counts-requested') === '1') {
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
			return;
		}

		sidebar.attr('data-related-counts-requested', '1');
		afterFirstPaint(function() {
			if (!isCurrentDetailView(
					detailViewContainer, sidebar, moduleName, recordId)) {
				return;
			}

			AppConnector.request({
				module: moduleName,
				action: 'DetailViewCounts',
				record: recordId,
				relation_ids: JSON.stringify(relationIds),
				include_comments: includeComments ? 1 : 0
			}).then(function(data) {
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
			});
		});
	}

	function scanDetailViews() {
		$('.detailViewContainer').each(function() {
			scheduleCounts($(this));
		});
	}

	$(function() {
		scanDetailViews();
		$(document)
				.off('postajaxready.vtigerDetailViewCounts')
				.on('postajaxready.vtigerDetailViewCounts', scanDetailViews);
	});
})(jQuery);
