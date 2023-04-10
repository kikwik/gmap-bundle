async function kwMap(mapElement, streetElement) {
    const { Map } = await google.maps.importLibrary("maps");

    let mapCenter = { lat: 41.9027835, lng: 12.4963655};
    let mapZoom = 4;
    let mapBounds = null;
    let markersData = [];
    let streetPosition = null;
    let streetMarker = null;

    // single marker
    if(mapElement.dataset.lat && mapElement.dataset.lng)
    {
        // set varible for map center and zoom
        mapCenter = { lat: parseFloat(mapElement.dataset.lat), lng: parseFloat(mapElement.dataset.lng) };
        mapZoom = 15;

        // set variable for the marker of the single object to display (same coord of mapCenter)
        let markerDatum = mapCenter;
        markerDatum.icon = mapElement.dataset.icon ?? null;
        markerDatum.info = mapElement.dataset.info ?? null;
        markersData.push(markerDatum);

        if(streetElement)
        {
            // set the streetView position
            streetPosition = mapCenter;
        }
    }

    // create the map
    var map = new Map(mapElement, {
        center: mapCenter,
        zoom: mapZoom,
    });

    // create the marker(s)
    for(const markerDatum of markersData)
    {
        let marker = addMarker(markerDatum.lat, markerDatum.lng, markerDatum.info, markerDatum.icon);

        if(streetPosition && !streetMarker)
        {
            // street view associated to the first marker
            streetMarker = marker;
        }
    }


    if(streetPosition)
    {
        // create the street view
        const panorama = new google.maps.StreetViewPanorama(
            streetElement,
            {
                position: streetPosition,
            }
        );

        const { spherical } = await google.maps.importLibrary("geometry");
        panorama.addListener('position_changed', function(){
            let heading = spherical.computeHeading(panorama.getPosition(), streetMarker.getPosition());
            panorama.setPov({heading: heading, pitch: 0})
        })

        map.setStreetView(panorama);
    }


    // multiple markers (ajax)
    if(mapElement.dataset.merkersUrl)
    {
        // load markers
        fetch(mapElement.dataset.merkersUrl)
            .then(function (response){
                return response.json();
            })
            .then(function (jsonData){
                mapBounds = new google.maps.LatLngBounds();
                for(jsonDatum of jsonData)
                {
                    addMarker(jsonDatum.lat, jsonDatum.lng, jsonDatum.info, jsonDatum.icon);
                    mapBounds.extend(new google.maps.LatLng(jsonDatum.lat, jsonDatum.lng));
                }
                mapCenter = mapBounds.getCenter();
                map.fitBounds(mapBounds);
            })
            .catch(function (error){
                console.log('Error loading '+mapElement.dataset.clusterUrl+': '+error);
            })
    }


    return map;


    function addMarker(lat, lng, info, icon)
    {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(lat), lng: parseFloat(lng) },
            map: map,
        });
        if(icon)
        {
            marker.setIcon(icon)
        }
        if(info)
        {
            // add infowindow
            const infowindow = new google.maps.InfoWindow({content: info});
            marker.addListener("click", () => {
                infowindow.open({
                    anchor: marker,
                    map,
                });
            });
        }
        return marker;
    }
}
