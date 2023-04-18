async function kwMap(mapElement, streetElement) {
    const { Map } = await google.maps.importLibrary("maps");

    const markers = [];
    const mapBounds = new google.maps.LatLngBounds();
    const infoWindow = new google.maps.InfoWindow();

    // create the map
    const map = new Map(mapElement, {
        center: { lat: 41.9027835, lng: 12.4963655},    // center of Italy by default
        zoom: 4,                                        // about a country by default
    });

    doLoadMarkers();
    doCenterMap();
    doMakeCluster();

    addSearchListener();
    addStreetView();

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
            // open infowindow
            marker.addListener("click", () => {
                infoWindow.setContent(jsonMarker.info);
                infoWindow.open({
                    anchor: marker,
                    map,
                });
            });
        }

        markers.push(marker);
        mapBounds.extend(new google.maps.LatLng(parseFloat(jsonMarker.lat), parseFloat(jsonMarker.lng)));
    }

    function doCenterMap()
    {
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

        if(markers.length == 1)  // one marker
        {
            if(!mapElement.dataset.mapCenter) {
                map.setCenter(markers[0].getPosition());
            }
            if(!mapElement.dataset.mapZoom) {
                map.setZoom(15);
            }
        }
        else if(markers.length > 1) // multiple markers
        {
            if(!mapElement.dataset.mapCenter && !mapElement.dataset.mapZoom)
            {
                map.fitBounds(mapBounds);
            }
        }
    }

    function doLoadMarkers()
    {
        // check attribute: data-map-markers
        if(mapElement.dataset.mapMarkers)
        {
            jsonData = JSON.parse(mapElement.dataset.mapMarkers);
            for(jsonDatum of jsonData)
            {
                addMarker(jsonDatum);
            }
        }

        // check attribute: data-map-remote-markers
        if(mapElement.dataset.mapRemoteMarkers)
        {
            // load markers
            fetch(mapElement.dataset.mapRemoteMarkers)
                .then(function (response){
                    return response.json();
                })
                .then(function (jsonData){
                    for(jsonDatum of jsonData)
                    {
                        addMarker(jsonDatum);
                    }
                    doCenterMap();
                    doMakeCluster();
                })
                .catch(function (error){
                    console.log('Error loading '+mapElement.dataset.mapRemoteMarkers+': '+error);
                })
        }
    }

    function doMakeCluster()
    {
        if(markers.length > 1) // multiple markers
        {
            // check attribute: data-map-cluster
            if(mapElement.dataset.mapCluster)
            {
                const options = JSON.parse(mapElement.dataset.mapCluster);
                const algorithm = new markerClusterer.SuperClusterAlgorithm(options);
                new markerClusterer.MarkerClusterer({ algorithm, map, markers });
            }
        }
    }

    function addSearchListener()
    {
        // check attribute: data-map-search-address, data-map-search-submit and data-map-search-zoom
        if(mapElement.dataset.mapSearchAddress && mapElement.dataset.mapSearchSubmit && mapElement.dataset.mapSearchZoom)
        {
            const geocoder = new google.maps.Geocoder();
            const submitBtn = document.querySelector(mapElement.dataset.mapSearchSubmit);
            submitBtn.addEventListener('click',function (e){
                e.preventDefault();
                const textInput = document.querySelector(mapElement.dataset.mapSearchAddress);
                geocoder.geocode( { 'address': textInput.value}, function(results, status) {
                    if (status == 'OK') {
                        map.setCenter(results[0].geometry.location);
                        map.setZoom(parseInt(mapElement.dataset.mapSearchZoom));
                    } else {
                        doCenterMap();
                        // alert(text.value+' ' + status);
                    }
                });
            })
        }
    }

    async function addStreetView()
    {
        // check attribute: data-map-street-view and data-map-street-view-position
        if(mapElement.dataset.mapStreetView && mapElement.dataset.mapStreetViewPosition)
        {
            const streetElement = document.querySelector(mapElement.dataset.mapStreetView);
            const streetPosition = JSON.parse(mapElement.dataset.mapStreetViewPosition);
            // create the street view
            const panorama = new google.maps.StreetViewPanorama(
                streetElement,
                {
                    position: streetPosition,
                }
            );

            const { spherical } = await google.maps.importLibrary("geometry");
            panorama.addListener('position_changed', function(){
                const finalHeading = spherical.computeHeading(panorama.getPosition(), streetPosition);
                panorama.setPov({heading: finalHeading, pitch: 0})
            })

            map.setStreetView(panorama);
        }
    }
}
