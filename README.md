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

Add your Api keys to .env file: 
- `GMAP_API_KEY` for server geocoding 
- `GMAP_API_KEY_JS` for javascript maps:

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

The bundle will configure an autowired provider with signature `Provider $googleMapsGeocoder` that you can use as follow:

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

Geocodable Entity
-----------------

Make your entities (and repository) geocodable by implementing `GeocodableEntityInterface` (and `GeocodableRepositoryInterface`)

Use the provided traits to be quick:

```php
namespace App\Entity;

use App\Repository\PlaceRepository;
use Doctrine\ORM\Mapping as ORM;
use Kikwik\GmapBundle\Interfaces\GeocodableEntityInterface;
use Kikwik\GmapBundle\Traits\GeocodableEntityTrait;

/**
 * @ORM\Entity(repositoryClass=PlaceRepository::class)
 */
class Place implements GeocodableEntityInterface
{
    use GeocodableEntityTrait;
    
    // ...
}
```

```php
namespace App\Repository;

use App\Entity\Place;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Kikwik\GmapBundle\Interfaces\GeocodableRepositoryInterface;
use Kikwik\GmapBundle\Traits\GeocodableRepositoryTrait;

/**
 * @extends ServiceEntityRepository<Place>
 *
 * @method Place|null find($id, $lockMode = null, $lockVersion = null)
 * @method Place|null findOneBy(array $criteria, array $orderBy = null)
 * @method Place[]    findAll()
 * @method Place[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaceRepository extends ServiceEntityRepository implements GeocodableRepositoryInterface
{
    use GeocodableRepositoryTrait;
    
    // ...
}
```

Then you can set address to the entity and ask to geocode her self (passing the provider):

```php
public function index(PlaceRepository $placeRepository, Provider $googleMapsGeocoder)
{
    // create an object that implement GeocodableEntityInterface
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
}

```