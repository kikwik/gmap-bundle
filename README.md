KikwikGmapBundle
================

Google Map and Geocoder support for Symfony 5.4



Installation
------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kikwik/gmap-bundle
```

Configuration
-------------

Add your Api keys to .env file, one for server geocoding and one for javascript maps:

```dotenv
###> geocoder ###
# https://console.cloud.google.com/apis/credentials?hl=it&project=tua-assicurazioni
# account: xxxxxx
# credential: "My server api key"
# indirizzi IP abilitati: xxx.xxx.xxx.xxx | yyy.yyy.yyy.yyy
GMAP_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# account: xxxxxx
# credential: "My javascript api key"
# siti abilitati: https://*.my-domain.ltd/*  |  https://my-domain.ltd/*
GMAP_API_KEY_JS=yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy
###< geocoder ###
```

Autowiring
----------

The bundle will configure an autowired provider called `googleMaps` that you can use as follow:

```php
namespace App\Service;

use Geocoder\Provider\Provider;

class MyService
{
    private $googleMapsGeocoder;

    public function __construct(Provider $googleMapsGeocoder)
    {
        $this->googleMapsGeocoder = $googleMapsGeocoder;
    }

    public function doGeocode(string $address)
    {
        $geocodeQuery = GeocodeQuery::create('via Tucidide 56, Milano')
            ->withLocale('it');
        /** @var GoogleAddress $geocodeResults[] */
        $geocodeResults = $googleMapsGeocoder->geocodeQuery($geocodeQuery);
        return $geocodeResults[0] ?? null;
    }
}

```

