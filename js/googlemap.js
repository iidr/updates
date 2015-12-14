function showCourseMap(mapid, lng, lat, iidr_zoom)
{	if (iidr_zoom == undefined)
	{	iidr_zoom = 13;
	}
	var latlng = new google.maps.LatLng(lng, lat);
    var options = { zoom: iidr_zoom, center: latlng, mapTypeId: google.maps.MapTypeId.ROADMAP };
    var map = new google.maps.Map(document.getElementById(mapid), options);
    var marker = new google.maps.Marker({ position: latlng, map: map });
}