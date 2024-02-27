Vtiger_List_Js('berlimap_List_Js', {
   
    // Base url
    urlParams : 'index.php?module=berlimap&view=Detail',
    
    // Object to hold the map class based on the selected custom view
    vectorLayer : false,
    
    // Object to hold the map class for own location
	iconLayer: false,
	
    // Object to hold the map object itself
    map : false,
	
    // array to hold the current location
    currentloc : [],
	
    // Object to hold the popup container
    overlay : false,
	
	//popup contents
	content: '',
	
	//switch for distance calculation
	calculatedistance: true,
    
	registerShowButton : function(){
		var thisInstance = this;
		jQuery('#showButton').on('click',function(e){
			e.preventDefault();
			$('#showButton').prop('disabled', true);
			//check API key
			if ($('#geoapikey').val() =='') {
				var mparams = {
					title : app.vtranslate('JS_GEODATA_LIMIT'),
					text: app.vtranslate('JS_GEODATA_LIMIT_INFO'),
					animation: 'show',
					type: 'slideStop'
				};
				Vtiger_Helper_Js.showPnotify(mparams);
				$('#showButton').prop('disabled', false);
				return;
			}
			//get the own location from browser
			if (navigator.geolocation) {
				var timeoutVal = 10 * 1000 * 1000;
					navigator.geolocation.getCurrentPosition(
					displayPosition, 
					displayError,
					{ enableHighAccuracy: true, timeout: timeoutVal, maximumAge: 0 }
				);
			}
			else {
				var mparams = {
					title : app.vtranslate('JS_LOCALDATA_GEODATA'),
					text: app.vtranslate('JS_BROWSERERROR_TXT'),
					animation: 'show',
					type: 'error'
				};
				Vtiger_Helper_Js.showPnotify(mparams);
			}

			function displayPosition(position) {
				thisInstance.currentloc.latitude=position.coords.latitude 
				thisInstance.currentloc.longitude=position.coords.longitude 
				var mparams = {
					title : app.vtranslate('JS_LOCALDATA_INFORMATION'),
					text:  'Latitude: ' + position.coords.latitude + '<br>Longitude: ' + position.coords.longitude,
					animation: 'show',
					type: 'info'
				};
				Vtiger_Helper_Js.showPnotify(mparams);
				//switch on distance calculation
				thisInstance.calculatedistance = true;
			}
			function displayError(error) {
				var errors = { 
					1: 'Permission denied',
					2: 'Position unavailable',
					3: 'Position request timeout'
				};
				var mparams = {
					title : app.vtranslate('JS_LOCALDATA_INFORMATION'),
					text:  app.vtranslate(errors[error.code]),
					animation: 'show',
					type: 'error'
				};
				Vtiger_Helper_Js.showPnotify(mparams);
				//switch off distance calculation
				thisInstance.calculatedistance = false;
			}
			if (thisInstance.map) {
				thisInstance.map.removeLayer(thisInstance.vectorLayer);
				thisInstance.map.vectorLayer = false;
			}
			// show help text
			var mparams = {
				title : app.vtranslate('JS_SEARCH_GEODATA'),
				text: app.vtranslate('JS_SEARCH_GEODATA_TXT'),
				animation: 'show',
				type: 'info'
			};
			Vtiger_Helper_Js.showPnotify(mparams);
			
			// get locations for all in custom list
 			var element = jQuery(e.currentTarget);
			var target =  $("#modulefilter");
			var viewid = $( "#modulefilter option:selected" ).val();;
			var module = $( "#modulefilter option:selected" ).attr('data-module');
			var params = {
				'vid' : viewid,
				'targetModule' : module,
				'module' : 'berlimap',
				'action' : 'ListAjax',
				'mode' : 'getGeoData'
			}
			var progressIndicatorElement = jQuery.progressIndicator();
			AppConnector.request(params).then(
				function(responseData){
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					$('#showButton').prop('disabled', false);
					if(responseData.success){
						var mparams = {
							title : app.vtranslate('JS_GEODATA_RESULT_NUMBER'),
							text: app.vtranslate('JS_GEODATA_RESULT_NUMBER_TXT1')+Object.keys(responseData.result.locations).length+app.vtranslate('JS_GEODATA_RESULT_NUMBER_TXT2'),
							animation: 'show',
							type: 'info'
							};
						Vtiger_Helper_Js.showPnotify(mparams);
						var geodata = responseData.result.locations;
						if(responseData.result.limitwarning > 0){
							var mparams = {
								title : app.vtranslate('JS_OVER_24H_LIMIT'),
								text: app.vtranslate('JS_OVER_24H_MESSAGE1')+responseData.result.limitwarning+app.vtranslate('JS_OVER_24H_MESSAGE2'),
								animation: 'show',
								type: 'error'
								};
							Vtiger_Helper_Js.showPnotify(mparams);
						}
						thisInstance.showMapOverlay(geodata);
					}
					else {
						var mparams = {
							title : app.vtranslate('BIG PROBLEM'),
							text: responseData.error.code,
							animation: 'show',
							type: 'error'
						};
						Vtiger_Helper_Js.showPnotify(mparams);
						return false;
					}
				},

				function(textStatus, errorThrown){
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					var mparams = {
						title : textStatus,
						text: errorThrown,
						animation: 'show',
						type: 'error'
					};
					Vtiger_Helper_Js.showPnotify(mparams);
					return false;
				}
			);
		})
	},
	
    showMapOverlay : function(geodata) {
		var thisInstance = this;
		var recordid ='';
		var approx ='';
		var iconFeatures=[];
        var iconpath = $('#iconpath').val();
		// create icons for each location
		$.each( geodata, function( recordid, geolocation ) {
			approx = geolocation.approx;
			
			var iconFeature = new ol.Feature({
			  geometry: new  
				//convert string to integer
				ol.geom.Point(ol.proj.fromLonLat([+geolocation.longitude, +geolocation.latitude])),
				name: geolocation.name,
				targetURL: geolocation.targetURL,
				longitude: geolocation.longitude,
				latitude: geolocation.latitude,
				currentlongitude: thisInstance.currentloc.longitude,
				currentlatitude: thisInstance.currentloc.latitude,
				rainfall: 500
			});
			iconFeatures.push(iconFeature);
			
		});
		//add an array of features
		var vectorSource = new ol.source.Vector({
		  features: iconFeatures 
		});
		//define icon style for all entries
		var iconStyle = new ol.style.Style({
			image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
			anchor: [0.5, 46],
			anchorXUnits: 'fraction',
			anchorYUnits: 'pixels',
			opacity: 0.75,
			src: iconpath
		  }))
		});
		thisInstance.vectorLayer = new ol.layer.Vector({
		  source: vectorSource,
		  style: iconStyle
		});

		thisInstance.map.addLayer(thisInstance.vectorLayer); 
		thisInstance.map.render();
		/////////////////////////////////////////////////////////////////////////
		// Add a click handler to the map to render the popup.
		/////////////////////////////////////////////////////////////////////////
		thisInstance.map.on('singleclick', function(evt) {
			$(popup).show();
			var contactref = thisInstance.map.forEachFeatureAtPixel(evt.pixel, function(feature) {
				return [feature.get('name'), feature.get('targetURL') , feature.get('longitude'), feature.get('latitude') , feature.get('currentlongitude'), feature.get('currentlatitude')];
			})
			var coordinate = evt.coordinate;
			if ( typeof contactref !== 'undefined'  && contactref[0] != '' ) {
				if (contactref[0] == 'mylocation') {
					thisInstance.content.innerHTML = '<p>'+app.vtranslate('JS_GEODATA_MY_LOCATION')+'</p>';
					thisInstance.overlay.setPosition(coordinate);
				}
				else if (typeof contactref[1] !== 'undefined' && contactref[1] != ''){
					//get distance to current location
					var distance = 0;

					var params = {
						'currentloclong' : contactref[4],
						'currentloclatt' : contactref[5],
						'targetloclong' : contactref[2],
						'targetloclatt' : contactref[3],
						'unit' : 'K',
						'module' : 'berlimap',
						'action' : 'ListAjax',
						'mode' : 'getGeoDistance'
					}
					var progressIndicatorElement = jQuery.progressIndicator();
					AppConnector.request(params).then(
						function(responseData){
							progressIndicatorElement.progressIndicator({'mode' : 'hide'});
							$('#showButton').prop('disabled', false);
							if(responseData.success){
								distance = responseData.result;
								if (thisInstance.calculatedistance == true) {
									thisInstance.content.innerHTML = '<a href="'+contactref[1]+'" target="_blank">'+contactref[0]+'</a><br>Entfernung vom Standort: '+distance+' km';
								}
								else {
									thisInstance.content.innerHTML = '<a href="'+contactref[1]+'" target="_blank">'+contactref[0]+'</a>';
								}
								thisInstance.overlay.setPosition(coordinate);
							}
							else {
								var mparams = {
									title : app.vtranslate('BIG PROBLEM'),
									text: responseData.error.code,
									animation: 'show',
									type: 'error'
								};
								Vtiger_Helper_Js.showPnotify(mparams);
								return false;
							}
						},

						function(textStatus, errorThrown){
							progressIndicatorElement.progressIndicator({'mode' : 'hide'});
							var mparams = {
								title : textStatus,
								text: errorThrown,
								animation: 'show',
								type: 'error'
							};
							Vtiger_Helper_Js.showPnotify(mparams);
							return false;
						}
					);
					
					
					
				}
				else {
					thisInstance.content.innerHTML = '<p>'+app.vtranslate('JS_GEODATA_NO_LOCATION')+'</p>';
					thisInstance.overlay.setPosition(coordinate);
				}
			}
		});
		
		thisInstance.map.on('pointermove', function(evt) {
			thisInstance.map.getTargetElement().style.cursor = thisInstance.map.hasFeatureAtPixel(evt.pixel) ? 'pointer' : '';
		});

	},
	
	init : function() {
        this.map.parent = this;
        delete this.init;
        return this;
    },

    showMap: function() {
		var thisInstance = this;
		// define popup param
		var container = document.getElementById('popup');
		thisInstance.content = document.getElementById('popup-content');
		var closer = document.getElementById('popup-closer');
		// Add a click handler to hide the popup
		closer.onclick = function() {
			thisInstance.overlay.setPosition(undefined);
			closer.blur();
			return false;
		};
		// Create an overlay to anchor the popup to the map.
		thisInstance.overlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
			element: container,
			autoPan: true,
			autoPanAnimation: {
				duration: 250
			}
		}));
		var berlinLonLat = [13.40,52.52];
		var berlinWebMercator = ol.proj.fromLonLat(berlinLonLat);
		thisInstance.map = new ol.Map({
			layers: [
				new ol.layer.Tile({
					source: new ol.source.OSM()
				})
			],
			target: 'map',
			overlays: [thisInstance.overlay],
			view: new ol.View({
				center: berlinWebMercator,
				zoom: 6
			})
		});
		thisInstance.currentloc.longitude = berlinLonLat[0];
		thisInstance.currentloc.latitude = berlinLonLat[1];
		trackMe(thisInstance.map.getView());
		
		
		function trackMe(view) {
			var geolocation = new ol.Geolocation({
				tracking: true
			});
			geolocation.on('change:position', function (evt) {
				var coordinate = geolocation.getPosition();
				thisInstance.currentloc.longitude = coordinate[0];
				thisInstance.currentloc.latitude = coordinate[1];
				userLonLat = coordinate;
				setMarker({
					longitude: coordinate[0],
					latitude: coordinate[1],
					id: -1
				}, 
				$('#myiconpath').val(), 
				1000);
			});
		};
		
		function setMarker(ua, imageSrc, zIndex){
			var iconFeature = new ol.Feature({
				geometry: new ol.geom.Point(ol.proj.transform([parseFloat(ua.longitude), parseFloat(ua.latitude)], 'EPSG:4326', 'EPSG:3857')),
				data: ua,
			});
			iconFeature.setId(ua.id);
			var vectorSource = new ol.source.Vector({
				features: [iconFeature]
			});
			var iconStyle = new ol.style.Style({
				image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
					anchor: [0.5, 27],
					anchorXUnits: 'fraction',
					anchorYUnits: 'pixels',
					opacity: 1,
					src: imageSrc
				}))
			});
			var vectorLayer = new ol.layer.Vector({
				source: vectorSource,
				style: iconStyle
			});
			if(zIndex){
				vectorLayer.setZIndex(zIndex);
			}
			thisInstance.map.addLayer(vectorLayer);
		};
	
	},
	
    registerEvents : function() {
        this._super();
        this.init();
		this.registerShowButton();
		this.showMap();
    }

});