async function kwMap(mapElement, streetElement) {
    const { Map } = await google.maps.importLibrary("maps");

    let markers = [];
    let mapBounds = new google.maps.LatLngBounds();

    // create the map
    const map = new Map(mapElement, {
        center: { lat: 41.9027835, lng: 12.4963655},    // center of Italy by default
        zoom: 4,                                        // about a country by default
    });

    // check attribute: data-map-center
    if(mapElement.dataset.mapCenter)
    {
        map.setCenter(JSON.parse(mapElement.dataset.mapCenter));
    }

    // check attribute: data-map-zoom
    if(mapElement.dataset.mapZoom)
    {
        map.setZoom(parseInt(mapElement.dataset.mapZoom));
    }

    // check attribute: data-map-markers
    if(mapElement.dataset.mapMarkers)
    {
        jsonData = JSON.parse(mapElement.dataset.mapMarkers);
        for(jsonDatum of jsonData)
        {
            addMarker(jsonDatum);
        }
    }

    reCenterMap();
    reZoomMap();

    // TODO: check attribute: data-map-cluster

    new markerClusterer.MarkerClusterer({ map, markers });


    // TODO: check attribute: data-map-street-view
    // if(streetPosition)
    // {
    //     // create the street view
    //     const panorama = new google.maps.StreetViewPanorama(
    //         streetElement,
    //         {
    //             position: streetPosition,
    //         }
    //     );
    //
    //     const { spherical } = await google.maps.importLibrary("geometry");
    //     panorama.addListener('position_changed', function(){
    //         let heading = spherical.computeHeading(panorama.getPosition(), streetMarker.getPosition());
    //         panorama.setPov({heading: heading, pitch: 0})
    //     })
    //
    //     map.setStreetView(panorama);
    // }


    // multiple markers (ajax)
    if(mapElement.dataset.merkersUrl)
    {
        // load markers
        fetch(mapElement.dataset.merkersUrl)
            .then(function (response){
                return response.json();
            })
            .then(function (jsonData){
                for(jsonDatum of jsonData)
                {
                    addMarker(jsonDatum);
                }
                reCenterMap();
                reZoomMap();
                new markerClusterer.MarkerClusterer({ map, markers });
            })
            .catch(function (error){
                console.log('Error loading '+mapElement.dataset.clusterUrl+': '+error);
            })
    }


    return map;


    function addMarker(jsonMarker)
    {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(jsonMarker.lat), lng: parseFloat(jsonMarker.lng) },
            map: map,
        });
        if(jsonMarker.icon)
        {
            marker.setIcon(jsonMarker.icon)
        }
        if(jsonMarker.info)
        {
            // add infowindow
            const infowindow = new google.maps.InfoWindow({content: jsonMarker.info});
            marker.addListener("click", () => {
                infowindow.open({
                    anchor: marker,
                    map,
                });
            });
        }

        markers.push(marker);
        mapBounds.extend(new google.maps.LatLng(parseFloat(jsonMarker.lat), parseFloat(jsonMarker.lng)));
    }

    function reCenterMap()
    {
        if(!mapElement.dataset.mapCenter) // if data-map-center is not defined, recalculate the center of the map
        {
            if(markers.length == 1)
            {
                // one marker
                map.setCenter(markers[0].getPosition());
            }
            else if(markers.length > 1)
            {
                // multiple markers
                map.setCenter(mapBounds.getCenter());
                map.fitBounds(mapBounds);
            }
        }
    }

    function reZoomMap()
    {
        if(!mapElement.dataset.mapZoom) // if data-map-zoom is not defined, recalculate the zoom of the map
        {
            if(markers.length == 1)
            {
                // one marker
                map.setZoom(15);
            }
            else if(markers.length > 1)
            {
                // multiple markers
                // map.fitBounds(mapBounds);
            }
        }
    }
}
