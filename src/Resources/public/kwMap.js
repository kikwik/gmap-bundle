async function kwMap(mapElement, streetElement) {
    const { Map } = await google.maps.importLibrary("maps");


    let mapCenter = { lat: 41.9027835, lng: 12.4963655}; // center of Italy  by default
    let mapZoom = 4;
    let markersData = [];
    let streetPosition = null;
    let streetMarker = null;

    // check attribute: data-map-center
    if(mapElement.dataset.mapCenter)
    {
        mapCenter = JSON.parse(mapElement.dataset.mapCenter);
    }

    // // single marker
    // if(mapElement.dataset.lat && mapElement.dataset.lng)
    // {
    //     // set varible for map center and zoom
    //     mapCenter = { lat: parseFloat(mapElement.dataset.lat), lng: parseFloat(mapElement.dataset.lng) };
    //     mapZoom = 15;
    //
    //     // set variable for the marker of the single object to display (same coord of mapCenter)
    //     let markerDatum = mapCenter;
    //     markerDatum.icon = mapElement.dataset.icon ?? null;
    //     markerDatum.info = mapElement.dataset.info ?? null;
    //     markersData.push(markerDatum);
    //
    //     if(streetElement)
    //     {
    //         // set the streetView position
    //         streetPosition = mapCenter;
    //     }
    // }

    // create the map
    var map = new Map(mapElement, {
        center: mapCenter,
        zoom: mapZoom,
    });

    let markers = [];
    let mapBounds = new google.maps.LatLngBounds();

    // create the marker(s)
    if(mapElement.dataset.mapMarkers)
    {
        jsonData = JSON.parse(mapElement.dataset.mapMarkers);
        for(jsonDatum of jsonData)
        {
            markers.push(addMarker(jsonDatum));
            mapBounds.extend(new google.maps.LatLng(jsonDatum.lat, jsonDatum.lng));
        }
    }

    if(!mapElement.dataset.mapCenter)
    {
        if(markers.length == 1)
        {
            map.setCenter(markers[0].getPosition());
            map.setZoom(15);
        }
        else
        {
            map.setCenter(mapBounds.getCenter());
            map.fitBounds(mapBounds);
        }
    }


    new markerClusterer.MarkerClusterer({ map, markers });


    // // create the marker(s)
    // for(const markerDatum of markersData)
    // {
    //     let marker = addMarker(markerDatum.lat, markerDatum.lng, markerDatum.info, markerDatum.icon);
    //
    //     if(streetPosition && !streetMarker)
    //     {
    //         // street view associated to the first marker
    //         streetMarker = marker;
    //     }
    // }


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
                    markers.push(addMarker(jsonDatum));
                    mapBounds.extend(new google.maps.LatLng(jsonDatum.lat, jsonDatum.lng));
                }
                map.fitBounds(mapBounds);
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
        return marker;
    }
}
