/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/   
    jQuery.Class("MapView",{
    
        url:"index.php?module=Google&action=MapAjax&mode=getLocation",
        
        /**
         * function to make api request to google maps.
         * On completion display the image
         */
        showMap:function(){
            var record=jQuery('#map_record').html();
            var module=jQuery('#map_module').html();
            MapView.url+='&recordid='+record+'&source_module='+module;
            jQuery.ajax({
                url:MapView.url
            }).done(function(res){
                var result=JSON.parse(res);
                var address=result["address"];
                var map_url=MapView.getStaticMapURL(address,"250x250");
                var location=jQuery.trim((address).replace(/\,/g," "));
                if(location != '' && location!=null){
                    jQuery("#map_address").html(location);
                    jQuery('#map_address').show();
                }
                jQuery("#map_canvas").append("<img id='map_image'></img>");
                jQuery("#map_image").attr("src",map_url); 
                jQuery("#map_image").addClass('cursorPointer'); 
                jQuery("#map_image").on('click',function(){
                   window.open(MapView.getQueryString(address,'_parent'));
                });             
                jQuery("#map_link").on('click',function(){
                   window.open(MapView.getQueryString(address,'_parent'));
                });
            });
        },
        /**
         * get the googleapis url based on the address and the size of the image.
         */        
        getStaticMapURL : function (address,size){
            var encoded_address=encodeURIComponent(address);
            var url=" http://maps.googleapis.com/maps/api/staticmap?size="+size+"&maptype=roadmap&markers=size:mid%7Ccolor:red%7C"+encoded_address+"&sensor=false";
            return url;
        } ,
        
        getQueryString:function (address){
            address=address.replace(/ /g,'+');
            return "http://maps.google.com/maps?q="+address+"&zoom=14&size=512x512&maptype=roadmap&sensor=false";
        }
        
    },{});

    jQuery(document).ready(function(){
        MapView.showMap();
    });       


