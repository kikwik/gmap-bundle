class kwMap
{

    async init(mapElement) {
        const { Map } = await google.maps.importLibrary("maps");

        this.mapElement = mapElement;
        this.markers = [];
        this.mapBounds = new google.maps.LatLngBounds();
        this.infoWindow = new google.maps.InfoWindow();
        this.markerCluster = null;
        this.mapSearchSubmit = null;
        this.messageBox = null;

        // create the map
        this.map = new Map(mapElement, {
            center: { lat: 41.9027835, lng: 12.4963655},    // center of Italy by default
            zoom: 4,                                        // about a country by default
        });

        this.loadMarkers();
        this.resetView();
        this.makeCluster();

        // check attribute: data-map-search-address, data-map-search-submit
        if(this.mapElement.dataset.mapSearchAddress && this.mapElement.dataset.mapSearchSubmit)
        {
            const geocoder = new google.maps.Geocoder();
            this.mapSearchSubmit = document.querySelector(this.mapElement.dataset.mapSearchSubmit);
            this.mapSearchSubmit.addEventListener('click',(e) => {
                this.hideMessage();
                e.preventDefault();
                const textInput = document.querySelector(this.mapElement.dataset.mapSearchAddress);
                geocoder.geocode( { 'address': textInput.value}, (results, status) => {
                    if (status == 'OK')
                    {
                        // center map in the searched point
                        this.map.setCenter(results[0].geometry.location);
                        // fit bounds to bounds or viewport of the searched result
                        if(results[0].geometry.bounds) {this.map.fitBounds(results[0].geometry.bounds);}
                        else if(results[0].geometry.viewport) {this.map.fitBounds(results[0].geometry.viewport);}
                        else {this.map.setZoom(18)};
                        
                        if(this.mapElement.dataset.mapSearchFindNearestMarker)
                        {
                            // zoom out to include at least one marker
                            var currentZoom = this.map.getZoom();
                            if(currentZoom != undefined)
                            {
                                while(this.getVisibleMarkers().length == 0 && currentZoom > 1)
                                {
                                    currentZoom--;
                                    this.map.setZoom(currentZoom);
                                }
                                currentZoom--;
                                this.map.setZoom(currentZoom);
                            }
                        }
                    }
                    else
                    {
                        this.resetView();
                        if(textInput.value)
                        {
                            this.showMessage(textInput.value+': indirizzo non trovato');
                        }
                    }
                });
            })
        }

        // check attribute: data-map-street-view and data-map-street-view-position
        if(this.mapElement.dataset.mapStreetView && this.mapElement.dataset.mapStreetViewPosition)
        {
            const streetElement = document.querySelector(this.mapElement.dataset.mapStreetView);
            const streetPosition = JSON.parse(this.mapElement.dataset.mapStreetViewPosition);

            // create the street view object
            const panorama = new google.maps.StreetViewPanorama(streetElement);

            // get the outdoor panorama at streetPosition coordinates
            const sv = new google.maps.StreetViewService();
            sv.getPanorama({ location: streetPosition, radius: 50, source: 'outdoor' }).then(function({data}){
                panorama.setPano(data.location.pano);
                panorama.setVisible(true);
            });

            // set the following Pov on position_changed
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
        this.mapBounds = new google.maps.LatLngBounds();
        if(this.markerCluster)
        {
            this.markerCluster.clearMarkers();
        }
    }

    loadMarkers() {
        // check attribute: data-map-markers
        if(this.mapElement.dataset.mapMarkers)
        {
            // load markers from data-attribute
            const jsonData = JSON.parse(this.mapElement.dataset.mapMarkers);
            for(let jsonDatum of jsonData)
            {
                this.addMarker(jsonDatum);
            }
        }

        // check attribute: data-map-remote-markers
        if(this.mapElement.dataset.mapRemoteMarkers)
        {
            this.showMessage('Caricamento...');
            // load json file from remote url
            fetch(this.mapElement.dataset.mapRemoteMarkers)
                .then((response) => {
                    return response.json();
                })
                .then((jsonData) => {
                    // load markers
                    for(let jsonDatum of jsonData)
                    {
                        this.addMarker(jsonDatum);
                    }
                    // reset zoom and bounds
                    if(this.mapSearchSubmit) {this.mapSearchSubmit.click();}
                                        else {this.resetView();}
                    // reset cluster
                    this.makeCluster();
                    this.hideMessage();
                })
                .catch((error) => {
                    this.showMessage('Error loading '+this.mapElement.dataset.mapRemoteMarkers+': '+error);
                    console.log('Error loading '+this.mapElement.dataset.mapRemoteMarkers+': '+error);
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
                if(this.mapElement.dataset.mapClusterColor)
                {
                    this.markerCluster = new markerClusterer.MarkerClusterer({
                        algorithm,
                        map: this.map,
                        markers: this.markers,
                        renderer: new SingleColorRenderer(this.mapElement.dataset.mapClusterColor)
                    });
                }
                else
                {
                    this.markerCluster = new markerClusterer.MarkerClusterer({
                        algorithm,
                        map: this.map,
                        markers: this.markers
                    });
                }

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

    showMessage(messageTxt) {
        if(!this.messageBox)
        {
            this.messageBox = document.createElement('div');
            this.messageBox.style.display = 'none';
            this.messageBox.style.margin = "15px";
            this.messageBox.style.padding = "5px";
            this.messageBox.style.background = 'white';
            this.messageBox.style.color = 'black';
            this.messageBox.style.border = '1px solid black';
            this.map.controls[google.maps.ControlPosition.LEFT_TOP].push(this.messageBox);
        }
        this.messageBox.textContent = messageTxt;
        this.messageBox.style.display = 'block';
    }

    hideMessage() {
        if(this.messageBox)
        {
            this.messageBox.style.display = 'none';
        }
    }
}

class SingleColorRenderer {
    constructor(color) {  // Constructor
        this.color = color;
    }

    render({ count, position }, stats, map) {
        // create svg url with fill color
        const svg = `<svg fill="${this.color}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">
                  <circle cx="120" cy="120" opacity=".6" r="70" />
                  <circle cx="120" cy="120" opacity=".3" r="90" />
                  <circle cx="120" cy="120" opacity=".2" r="110" />
                </svg>`;
        // adjust zIndex to be above other markers
        const zIndex = Number(google.maps.Marker.MAX_ZINDEX) + count;
        if (google.maps.marker &&
            map.getMapCapabilities().isAdvancedMarkersAvailable) {
            // create cluster SVG element
            const div = document.createElement("div");
            div.innerHTML = svg;
            const svgEl = div.firstElementChild;
            svgEl.setAttribute("width", "50");
            svgEl.setAttribute("height", "50");
            // create and append marker label to SVG
            const label = document.createElementNS("http://www.w3.org/2000/svg", "text");
            label.setAttribute("x", "50%");
            label.setAttribute("y", "50%");
            label.setAttribute("style", "fill: #FFF");
            label.setAttribute("text-anchor", "middle");
            label.setAttribute("font-size", "50");
            label.setAttribute("dominant-baseline", "middle");
            label.appendChild(document.createTextNode(`${count}`));
            svgEl.appendChild(label);
            const clusterOptions = {
                map,
                position,
                zIndex,
                content: div.firstElementChild,
            };
            return new google.maps.marker.AdvancedMarkerElement(clusterOptions);
        }
        const clusterOptions = {
            position,
            zIndex,
            icon: {
                url: `data:image/svg+xml;base64,${window.btoa(svg)}`,
                scaledSize: new google.maps.Size(45, 45),
            },
            label: {
                text: String(count),
                color: "rgba(255,255,255,0.9)",
                fontSize: "12px",
            },
        };
        return new google.maps.Marker(clusterOptions);
    }
}