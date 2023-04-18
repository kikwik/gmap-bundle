class kwMap
{

    async init(mapElement) {
        const { Map } = await google.maps.importLibrary("maps");

        this.mapElement = mapElement;
        this.markers = [];
        this.mapBounds = new google.maps.LatLngBounds();
        this.infoWindow = new google.maps.InfoWindow();
        this.markerCluster = null;

        // create the map
        this.map = new Map(mapElement, {
            center: { lat: 41.9027835, lng: 12.4963655},    // center of Italy by default
            zoom: 4,                                        // about a country by default
        });

        this.loadMarkers();
        this.resetView();
        this.makeCluster();

        // check attribute: data-map-search-address, data-map-search-submit and data-map-search-zoom
        if(this.mapElement.dataset.mapSearchAddress && this.mapElement.dataset.mapSearchSubmit && this.mapElement.dataset.mapSearchZoom)
        {
            const geocoder = new google.maps.Geocoder();
            const submitBtn = document.querySelector(this.mapElement.dataset.mapSearchSubmit);
            submitBtn.addEventListener('click',(e) => {
                e.preventDefault();
                const textInput = document.querySelector(this.mapElement.dataset.mapSearchAddress);
                geocoder.geocode( { 'address': textInput.value}, (results, status) => {
                    if (status == 'OK') {
                        this.map.setCenter(results[0].geometry.location);
                        this.map.setZoom(parseInt(this.mapElement.dataset.mapSearchZoom));
                    } else {
                        this.resetView();
                    }
                });
            })
        }

        // check attribute: data-map-street-view and data-map-street-view-position
        if(this.mapElement.dataset.mapStreetView && this.mapElement.dataset.mapStreetViewPosition)
        {
            const streetElement = document.querySelector(this.mapElement.dataset.mapStreetView);
            const streetPosition = JSON.parse(this.mapElement.dataset.mapStreetViewPosition);
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

            this.map.setStreetView(panorama);
        }
    }

    getGMap() {
        return this.map;
    }

    getMarkers() {
        return this.markers;
    }

    getVisibleMarkers() {
        let visibleMarkers = [];
        if(this.markers.length > 0 && this.map.getBounds())
        {
            const bounds = this.map.getBounds()
            this.markers.forEach(m => {
                if (bounds.contains(m.getPosition())) {
                    visibleMarkers.push(m)
                }
            });
        }
        return visibleMarkers;
    }

    clearMarkers() {
        while(this.markers.length)
        {
            let marker = this.markers.pop();
            marker.setMap(null);
        }
        if(this.markerCluster)
        {
            this.markerCluster.clearMarkers();
        }
    }

    loadMarkers() {
        // check attribute: data-map-markers
        if(this.mapElement.dataset.mapMarkers)
        {
            const jsonData = JSON.parse(this.mapElement.dataset.mapMarkers);
            for(let jsonDatum of jsonData)
            {
                this.addMarker(jsonDatum);
            }
        }

        // check attribute: data-map-remote-markers
        if(this.mapElement.dataset.mapRemoteMarkers)
        {
            // load markers
            fetch(this.mapElement.dataset.mapRemoteMarkers)
                .then((response) => {
                    return response.json();
                })
                .then((jsonData) => {
                    for(let jsonDatum of jsonData)
                    {
                        this.addMarker(jsonDatum);
                    }
                    this.resetView();
                    this.makeCluster();
                })
                .catch((error) => {
                    console.log('Error loading '+mapElement.dataset.mapRemoteMarkers+': '+error);
                })
        }
    }

    makeCluster() {
        if(this.markers.length > 1) // multiple markers
        {
            // check attribute: data-map-cluster
            if(this.mapElement.dataset.mapCluster)
            {
                const options = JSON.parse(this.mapElement.dataset.mapCluster);
                const algorithm = new markerClusterer.SuperClusterAlgorithm(options);
                this.markerCluster = new markerClusterer.MarkerClusterer({
                    algorithm,
                    map: this.map,
                    markers: this.markers
                });
            }
        }
    }

    addMarker(jsonMarker) {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(jsonMarker.lat), lng: parseFloat(jsonMarker.lng) },
            map: this.map,
        });
        if(jsonMarker.icon)
        {
            marker.setIcon(jsonMarker.icon)
        }
        if(jsonMarker.info)
        {
            marker.info = jsonMarker.info;
            // open infowindow
            marker.addListener("click", () => {
                this.infoWindow.setContent(jsonMarker.info);
                this.infoWindow.open({
                    anchor: marker,
                    map: this.map,
                });
            });
        }
        if(jsonMarker.id)
        {
            marker.id = jsonMarker.id;
        }

        this.markers.push(marker);
        this.mapBounds.extend(new google.maps.LatLng(parseFloat(jsonMarker.lat), parseFloat(jsonMarker.lng)));
    }

    resetView() {
        // check attribute: data-map-center
        if(this.mapElement.dataset.mapCenter)
        {
            this.map.setCenter(JSON.parse(this.mapElement.dataset.mapCenter));
        }

        // check attribute: data-map-zoom
        if(this.mapElement.dataset.mapZoom)
        {
            this.map.setZoom(parseInt(this.mapElement.dataset.mapZoom));
        }

        if(this.markers.length == 1)  // one marker
        {
            if(!this.mapElement.dataset.mapCenter) {
                this.map.setCenter(this.markers[0].getPosition());
            }
            if(!this.mapElement.dataset.mapZoom) {
                this.map.setZoom(15);
            }
        }
        else if(this.markers.length > 1) // multiple markers
        {
            if(!this.mapElement.dataset.mapCenter && !this.mapElement.dataset.mapZoom)
            {
                this.map.fitBounds(this.mapBounds);
            }
        }
    }
}

