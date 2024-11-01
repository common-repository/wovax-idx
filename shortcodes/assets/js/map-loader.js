// Since we can have multiple maps we need to store each maps div id
var WovaxIDXMapInfo = new class {
    constructor() {
        this.maps   = [];
    }
    setMarker(low, high) {
        this.marker = {
            'low' : low,
            'high':	high
        };
    }
    add(id, latitude, longitude) {
        this.maps.push(
            {
                'id'       : id,
                'latitude' : latitude,
                'longitude': longitude
            }
        );
    }
    loadMaps() {
        var marker_icon = this.marker.low;
        if(window.devicePixelRatio > 2){
            marker_icon = this.marker.high;
        }
        var len = this.maps.length;
        for (var i = 0; i < len; i++) {
            var map        = this.maps[i];
            var mapOptions = {
                center: new google.maps.LatLng(map.latitude, map.longitude),
                zoom: 15
            }
            var el = document.getElementById(map.id);
            if(el == null) {
                console.log("Map loader no element exists with ID: " + map.id);
                continue;
            }
            map = new google.maps.Map(el, mapOptions);
            new google.maps.Marker({
                position: mapOptions.center,
                map     : map,
                icon    : marker_icon
            });
        }
    }
};
// Call back function for google maps api
function wovax_idx_map_loader() {
    WovaxIDXMapInfo.loadMaps();
}