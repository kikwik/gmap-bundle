async function kwMap(mapElement, streetElement) {
    const { Map } = await google.maps.importLibrary("maps");

    const markerCoordinates = { lat: parseFloat(mapElement.dataset.lat), lng: parseFloat(mapElement.dataset.lng) };


    let map = new Map(mapElement, {
        center: markerCoordinates,
        zoom: 15,
    });

    const marker = new google.maps.Marker({
        position: markerCoordinates,
        icon: mapElement.dataset.icon,
        map: map,
    });

    if(streetElement)
    {
        const panorama = new google.maps.StreetViewPanorama(
            streetElement,
            {
                position: markerCoordinates,
            }
        );

        const { spherical } = await google.maps.importLibrary("geometry");
        panorama.addListener('position_changed', function(){
            let heading = spherical.computeHeading(panorama.getPosition(), marker.getPosition());
            panorama.setPov({heading: heading, pitch: 0})
        })

        map.setStreetView(panorama);
    }

    return map;
}