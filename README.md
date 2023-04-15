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

Display Maps for a single object
--------------------------------

- Place a div on the page for every map, with these data attributes:
  - `data-map-center` a json string that represent a LatLngLiteral
  - `data-map-zoom` an integer value
  - `data-map-markers` a json string that represent an array of marker descriptor, each marker descriptor must have the following fields
      - `lat` latitude value (float)
      - `lng` longitude value (float)
      - `info` the google.maps.InfoWindow content (optional)
      - `icon` the icon file (optional)
      - `id` the id of the object (optional)
  - `data-map-cluster` a json string that represent the SuperCluster options (see https://github.com/mapbox/supercluster#options)

You can use the twig helpers:
  - {{ kw_map_data_center(-31.56391, 147.154312) }} or {{ kw_map_data_center(place) }}
  - {{ kw_map_data_zoom(3) }}
  - {{ kw_map_data_markers(places) }}
  - {{ kw_map_data_cluster({ maxZoom: 10, minPoints: 5 }) }}


some examples:

```twig
Empty map centered in australia:
<div class="ratio ratio-1x1">
    <div class="kw-map" {{ kw_map_data_center(-31.56391, 147.154312) }}"></div>
</div>

Empty map centered in place (an GeocodableEntityInterface object):
<div class="ratio ratio-1x1">
    <div class="kw-map" {{ kw_map_data_center(place) }}></div>
</div>

Map with marker in all places (an array of GeocodableEntityInterface object):
<div class="ratio ratio-1x1">
    <div class="kw-map" {{ kw_map_data_markers(places) }}></div>
</div>

Map with marker in all places (an array of GeocodableEntityInterface object) centered in the first one with zoom=3:
<div class="ratio ratio-1x1">
    <div class="kw-map" {{ kw_map_data_center(places[0]) }} {{ kw_map_data_zoom(3) }} {{ kw_map_data_markers(places) }}></div>
</div>
```


- Call the `kw_gmap_script` twig function inside the javascripts block to initialize the GMap library and call the `kwMap` function passing the DOM element

```twig
{% block javascripts %}
    {{ parent() }}
 
    {{ kw_gmap_script_tags() }}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let mapElements = document.querySelectorAll('.kw-map');
            mapElements.forEach(function (mapElement){
                kwMap(mapElement);
            })
        });
    </script>
{% endblock %}
```

