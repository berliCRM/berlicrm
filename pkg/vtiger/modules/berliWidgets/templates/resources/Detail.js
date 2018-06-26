$(function() {
    // IE, Safari compatibility
	document.ondragstart = function () {
		return false; 
	};
    // preventing page from redirecting
    $(".dropcontainer").on("dragover", function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(".upload-area p").text(app.vtranslate('JS_FILE_DRAG')); 
    });

    $(".dropcontainer").on("drop", function(e) { 
		e.preventDefault(); 
		e.stopPropagation(); 
	});

    // Drag enter
    $('.upload-area').on('dragenter', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $(".upload-area p").text(app.vtranslate('JS_FILE_DROP')); 
    });
	
    // Drag dragleave
    $('.upload-area').on('dragleave', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $(".upload-area p").text(app.vtranslate('JS_FILE_DRAGANDDROP'));
    });

    // Drag over
    $('.upload-area').on('dragover', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $(".upload-area p").text(app.vtranslate('JS_FILE_DROP'));
    });

    // Drop
    $('.upload-area').on('drop', function (e) {
        e.stopPropagation();
        e.preventDefault();

		if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
		  alert('The File APIs are not fully supported in this browser.');
		  return;
		}   

		var input = document.getElementById('file');
		if (!input) {
		  alert("Couldn't find the file input element.");
		  return;
		}
		else if (!input.files) {
		  alert("This browser doesn't seem to support the `files` property of file inputs.");
		  return;
		}
		e.dataTransfer = e.originalEvent.dataTransfer;
		var file = e.dataTransfer.files;
        var fd = new FormData();

        fd.append('file', file[0]);

        $(".upload-area p").text(app.vtranslate('JS_FILE_UPLOAD'));
        uploadData(fd);
    });

    // file selected
    $("#file").change(function(){
        var fd = new FormData();

        var files = $('#file')[0].files[0];
        fd.append('file',files);

        $(".upload-area p").text(app.vtranslate('JS_FILE_UPLOAD'));
        uploadData(fd);
    });
});

// Sending AJAX request and upload file
	/*
	 * Function to check Duplication of report Name
	 * returns boolean true or false
	 */
function uploadData(formdata){
	var aDeferred = jQuery.Deferred();
	var recordid = $('#recordid').val();
	
	$.ajax({
		url: 'index.php?module=berliWidgets&action=saveDroppedDocument&recordid='+recordid,
		type: 'post',
		data: formdata,
		contentType: false,
		processData: false,
		dataType: 'json'
	})
	.always(function() {
		// remove loading image maybe
	})
	.done(function( msg ) {
 		var params = {
			title: app.vtranslate('JS_FILE_UPLOADED'),
			text: app.vtranslate('JS_FILE_UPLOADED_MESSAGE'),
			animation: 'show',
			type: 'info'
		};
		if(msg.result['success']==false){
			var params = {
				title: app.vtranslate('JS_UPLOAD_ERROR'),
				text: app.vtranslate(msg.result['error']),
				animation: 'show'
			};
		}
		Vtiger_Helper_Js.showPnotify(params);
		
		$(".upload-area p").text(app.vtranslate('JS_FILE_DRAGANDDROP'));
		return false;
	})
	.fail(function() {
		alert( "Error: no document created" );
		return false;
	});
}