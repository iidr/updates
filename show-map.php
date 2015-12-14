<!doctype html>
<html>
	<head>
        <meta charset="utf-8">
        <title>Campus Map</title>
        <?php if($_REQUEST['lat']!='' && $_REQUEST['lng']!=''){?>
			<script src='http://maps.google.com/maps/api/js'></script>
            <script type="text/javascript">
                var map;
                var iidr_zoom 	= 12;
                var mapid		= 'map_canvas';
                var lat 		= '<?php echo trim($_REQUEST['lat']);?>'; 
                var lng 		= '<?php echo trim($_REQUEST['lng']);?>';
                
				function initialize(){
					var latlng 		= new google.maps.LatLng(lng, lat);
					var mapOptions 	= { zoom: iidr_zoom, center: latlng, mapTypeId: google.maps.MapTypeId.ROADMAP };
					map 		= new google.maps.Map(document.getElementById(mapid), mapOptions);
					var marker 		= new google.maps.Marker({ position: latlng, map: map });
                }
                
                google.maps.event.addDomListener(window, 'load', initialize);
                google.maps.event.addDomListener(window, "resize", function() {
                  var center = map.getCenter();
                  google.maps.event.trigger(map, "resize");
                  map.setCenter(center); 
                });
                
            </script>
        <?php }?>    
    </head>
    <body>
        <div id = "map_canvas" style="min-width:200px;min-height:400px; width:auto;height:auto;"></div>
    </body>
</html>