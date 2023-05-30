KikwikGmapBundle
================

Google Map and Geocoder support for Symfony 5



Installation
------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kikwik/gmap-bundle
```

Configuration
-------------

Add your Api keys to .env file: 
- `GMAP_API_KEY` for server geocoding 
- `GMAP_API_KEY_JS` for javascript maps:

```dotenv
###> geocoder ###
# https://console.cloud.google.com/apis/credentials?hl=it&project=my-project
# credential: "My server api key"
# allowed IP address: xxx.xxx.xxx.xxx | yyy.yyy.yyy.yyy
GMAP_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# https://console.cloud.google.com/apis/credentials?hl=it&project=my-project
# credential: "My javascript api key"
# allowed domains: https://*.my-domain.ltd/*  |  https://my-domain.ltd/*
GMAP_API_KEY_JS=yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy
###< geocoder ###
```

Autowiring
----------

The bundle will configure an autowired provider with signature `Provider $googleMapsGeocoder` that you can use as follow:

```php
namespace App\Service;

use Geocoder\Provider\Provider;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;

class MyService
{
    private $googleMapsGeocoder;

    public function __construct(Provider $googleMapsGeocoder)
    {
        $this->googleMapsGeocoder = $googleMapsGeocoder;
    }

    public function doGeocode(string $address)
    {
        // create the GeocodeQuery with the address to geocode
        $geocodeQuery = GeocodeQuery::create('piazza Duomo 1, Milano')
            ->withLocale('it');
            
        // ask geocode to the $googleMapsGeocoder provider,
        // the result is an array of GoogleAddress objects   
        /** @var GoogleAddress $geocodeResults[] */
        $geocodeResults = $googleMapsGeocoder->geocodeQuery($geocodeQuery);
        
        // first result should be the best match
        return $geocodeResults[0] ?? null;
    }
}
```

Geocodable Entity
-----------------

Make your entities (and repository) geocodable by implementing `GeocodableEntityInterface` (and `GeocodableRepositoryInterface`)

Use the provided traits to be quick:

```php
namespace App\Entity;

use Kikwik\GmapBundle\Geocodable\GeocodableEntityInterface;
use Kikwik\GmapBundle\Geocodable\GeocodableEntityTrait;

class Place implements GeocodableEntityInterface
{
    use GeocodableEntityTrait;
    
    // ...
}
```

```php
namespace App\Repository;

use Kikwik\GmapBundle\Geocodable\GeocodableRepositoryInterface;
use Kikwik\GmapBundle\Geocodable\GeocodableRepositoryTrait;

class PlaceRepository extends ServiceEntityRepository implements GeocodableRepositoryInterface
{
    use GeocodableRepositoryTrait;
    
    // ...
}
```

Then you can set address to the entity and ask to geocode her self (passing the provider):

```php
namespace App\Controller;

use Geocoder\Provider\Provider;

class GmapController extends AbstractController
{
    /**
     * @Route("/gmap/new", name="app_gmap_new")
     */
    public function createNewPlace(Provider $googleMapsGeocoder, EntityManagerInterface $entityManager)
    {
        // create an object that implement GeocodableEntityInterface
        /** @var Kikwik\GmapBundle\Geocodable\GeocodableEntityInterface $place */
        $place = new Place();

        // fill the address fields
        $place->setStreet('Piazza Duomo');
        $place->setStreetNumber('1');
        $place->setZipCode('20100');
        $place->setCity('Milano');
        $place->setProvince('MI');
        $place->setCountry('Italia');

        // Ask geocode by passing the provider
        $place->doGeocode($googleMapsGeocoder);

        $entityManager->persist($place);
        $entityManager->flush();

        return $this->redirect($place->getGmapsUrl());
    }
}
```

Geocode Command
---------------

With the `kikwik:gmap:geocode` command you can batch geocode all the entities that need to be geocoded (never geocoded or with address changed after the last geocode) 

```console
$ php bin/console kikwik:gmap:geocode --limit=5
```

Use the `--failed` option to try to geocode again the failed ones

```console
$ php bin/console kikwik:gmap:geocode --limit=5 --failed
```

Display Maps
------------


- Call the `kw_gmap_script` twig function inside the javascripts block to initialize the GMap library, 
- then create a new `kwMap` object 
- and call its `init` function that return a promise that is resolved when the map is loaded

```twig
{% block javascripts %}
    {{ parent() }}
 
    {{ kw_gmap_script_tags() }}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let mapElements = document.querySelectorAll('.kw-map');
            mapElements.forEach(function (mapElement){
                let map = new kwMap();
                map.init(mapElement)
                        .then(function (){

                            map.getGMap().addListener('bounds_changed', function() {
                                const searchText = document.getElementById('map-search-txt');
                                const searchResults = document.getElementById('search-results');
                                const searchResultList = searchResults.querySelector('.js-location-list');
                                const searchResultCount = searchResults.querySelector('.js-location-count');
                                if(searchText.value)
                                {
                                    // get visible markers
                                    let visibleMarkers = map.getVisibleMarkers();
                                    // update counter
                                    searchResultCount.textContent = '('+visibleMarkers.length+')';
                                    // empty result list
                                    searchResultList.innerHTML = '';
                                    // add results to list
                                    for(let visibleMarker of visibleMarkers)
                                    {
                                        let node = document.createElement('li');
                                        node.id = 'result-'+visibleMarker.id;
                                        node.innerHTML = visibleMarker.info;
                                        searchResultList.appendChild(node);
                                    }
                                    // show results
                                    searchResults.classList.remove('d-none');
                                }
                                else
                                {
                                    // hide results
                                    searchResults.classList.add('d-none');
                                }
                            });


                            const mapSourceRadios = document.querySelectorAll('.js-map-source');
                            mapSourceRadios.forEach(function (mapSourceRadio){
                                mapSourceRadio.addEventListener('click', function() {
                                    map.clearMarkers();
                                    let url = this.value;
                                    mapElement.dataset.mapRemoteMarkers = url;
                                    map.loadMarkers();
                                })
                            })
                        })
            });
        });
    </script>
{% endblock %}
```

Then place a div on the page for each map, and use the twig helpers:
  - `{{ kw_map_data_center(-31.56391, 147.154312) }}` - set map center, parameters are a couple of float
  - `{{ kw_map_data_center(place) }}` - set map center, parameter is a GeocodableEntityInterface object
  - `{{ kw_map_data_zoom(3) }}` - set map zoom, parameter is an integer
  - `{{ kw_map_data_markers(places) }}` - load markers, parameter is an array of GeocodableEntityInterface objects
  - `{{ kw_map_data_cluster({ maxZoom: 10, minPoints: 5 }, 'darkgreen') }}` - activate cluster feature, parameters are an array of SuperCluster options, (see https://github.com/mapbox/supercluster#options) and an optional color (this activate the SingleColorRenderer)
  - `{{ kw_map_data_remote_markers(asset('path/to/file.json')) }}` - load remote markers, parameter is the remote url
  - `{{ kw_map_data_search_address('#map-address','#map-address-submit', { findNearestMarker: true }) }}` - bound a search form, parameters are the css selector of the input text, the css selector of the submit button and an array of options
  - `{{ kw_map_data_street_view('#street-view',place) }}` - enable street view, parameters are the css selector of the container and a GeocodableEntityInterface object
  - `{{ kw_map_data_street_view('#street-view',41.9027835, 12.4963655) }}` - enable street view, parameters are the css selector of the container and a couple of float

Here all the data-attribute supported:
  - `data-map-center` a json string that represent a LatLngLiteral
  - `data-map-zoom` an integer value
  - `data-map-markers` a json string that represent an array of marker descriptor, each marker descriptor must have the following fields
    - `lat` latitude value (float)
    - `lng` longitude value (float)
    - `info` the google.maps.InfoWindow content (optional)
    - `icon` the icon file (optional)
    - `identifier` a sting that identify the marker (optional)
  - `data-map-cluster` a json string that represent the SuperCluster options (see https://github.com/mapbox/supercluster#options)
  - `data-map-cluster-color` a color string for the cluster's SingleColorRenderer
  - `data-map-remote-markers` an url from which load markers in json format
  - `data-map-search-address` the css selector of the input text used to center the map
  - `data-map-search-submit` the css selector of the submit button used to center the map
  - `data-map-search-find-nearest-marker` set to "1" for a zoom out after a successful search, until a marker is visible in the map 
  - `data-map-street-view` the css selector of the element that will contain the street view 
  - `data-map-street-view-position` a json string that represent a LatLngLiteral



some examples:

```twig
<form>
    <input type="text" id="map-address" placeholder="Ricerca per città, indirizzo, CAP...">
    <button type="submit" id="map-address-submit">Cerca ›</button>
</form>

Map with clustered external data and search box:
<div class="ratio ratio-1x1">
    <div class="kw-map"
            {{ kw_map_data_remote_markers(asset('agenzie.json')) }}
            {{ kw_map_data_cluster({ maxZoom: 10, minPoints: 10 }) }}
            {{ kw_map_data_search_address('#map-address','#map-address-submit', { findNearest: true }) }}
    ></div>
</div>
            
Empty map centered in australia:
<div class="ratio ratio-1x1">
    <div class="kw-map"
            {{ kw_map_data_center(-31.56391, 147.154312) }}
    ></div>
</div>

Empty map centered in place (an GeocodableEntityInterface object):
<div class="ratio ratio-1x1">
    <div class="kw-map" 
            {{ kw_map_data_center(place) }}
    ></div>
</div>

Map with marker in all places (an array of GeocodableEntityInterface object):
<div class="ratio ratio-1x1">
    <div class="kw-map" {{ kw_map_data_markers(places) }}></div>
</div>

Map with zoom=3, marker in all places (an array of GeocodableEntityInterface object) centered in the first one, street view on the third one
<div class="ratio ratio-1x1">
    <div class="kw-map"
            {{ kw_map_data_center(places[0]) }}
            {{ kw_map_data_zoom(3) }}
            {{ kw_map_data_markers(places) }}
            {{ kw_map_data_search_address('#map-address','#map-address-submit') }}
            {{ kw_map_data_street_view('#street-view',places[2]) }}
    ></div>
</div>

<div class="ratio ratio-21x9">
    <div id="street-view"></div>
</div>
```


Address Autocomplete
--------------------

Use the `AddressAutocompleteType` in your forms to geocode adresses in separate components

```php
use Kikwik\GmapBundle\Form\Type\AddressAutocompleteType;

class GmapController extends AbstractController
{
    /**
     * @Route("/gmap/autocomplete", name="app_gmap_autocomplete")
     */
    public function addressAutocomplete(Request $request)
    {
        $submittedData = null;
        $form = $this->createFormBuilder()
            ->add('indirizzo1',AddressAutocompleteType::class, [
            ])
            ->add('indirizzo2',AddressAutocompleteType::class, [
                'autocomplete_fields' => ['latitude','longitude']
            ])
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $submittedData = $form->getData();
            dump($submittedData['indirizzo1']['autocomplete']);
            dump($submittedData['indirizzo1']['street']);
            dump($submittedData['indirizzo1']['streetNumber']);
            dump($submittedData['indirizzo1']['zipCode']);
            dump($submittedData['indirizzo1']['locality']);
            dump($submittedData['indirizzo1']['city']);
            dump($submittedData['indirizzo1']['province']);
            dump($submittedData['indirizzo1']['region']);
            dump($submittedData['indirizzo1']['country']);
            dump($submittedData['indirizzo1']['latitude']);
            dump($submittedData['indirizzo1']['longitude']);
        }


        return $this->render('gmap/addressAutocomplete.html.twig',[
            'form'=>$form->createView(),
            'submittedData' => $submittedData,
        ]);
    }
}
```

remember to load gmap scripts in your template:

```twig
{% block javascripts %}
    {{ parent() }}
 
    {{ kw_gmap_script_tags() }}
    
{% endblock %}
```