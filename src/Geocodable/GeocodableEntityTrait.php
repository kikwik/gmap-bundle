<?php

namespace Kikwik\GmapBundle\Geocodable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;


trait GeocodableEntityTrait
{

    /**************************************/
    /* PUBLIC PROPERTIES                  */
    /**************************************/

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $streetNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $zipCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $province;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

    /**************************************/
    /* PRIVATE PROPERTIES                 */
    /**************************************/

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"street","streetNumber","zipCode","city","province","country"})
     */
    private $addressUpdatedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $geocodedAt;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=12, nullable=true)
     */
    private $latitude;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=12, nullable=true)
     */
    private $longitude;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $formattedAddress;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $geocodeStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $geocodeQuery;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $geocodeQueryLocale = 'it';

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $geocodeResult;

    /**************************************/
    /* GEOCODE METHODS                    */
    /**************************************/

    public function isGeocoded(): bool
    {
        return $this->latitude && $this->longitude;
    }

    public function needGeocode(): bool
    {
        return (!$this->isGeocoded() && !$this->geocodeStatus)
            || $this->addressUpdatedAt > $this->geocodedAt;
    }

    public function doGeocode(Provider $provider): GeocodableEntityInterface
    {
        try {
            $this->geocodeQuery = $this->createGeocodeQueryString();
            $this->geocodedAt = new \DateTimeImmutable();

            /** @var AddressCollection $results */
            $results = $provider->geocodeQuery(GeocodeQuery::create($this->geocodeQuery)->withLocale($this->getGeocodeQueryLocale()));
            $this->geocodeResult = serialize($results);

            if($results->isEmpty())
            {
                $this->latitude = null;
                $this->longitude = null;
                $this->formattedAddress = null;
                $this->geocodeStatus = GeocodeStatus::ZERO_RESULTS;
            }
            else
            {
                $googleAddress = $results->first();
                $this->latitude = $googleAddress->getCoordinates()->getLatitude();
                $this->longitude = $googleAddress->getCoordinates()->getLongitude();
                $this->formattedAddress = $googleAddress->getFormattedAddress();
                $this->geocodeStatus = GeocodeStatus::OK;
            }
        }
        catch(\Throwable $e)
        {
            $this->geocodeStatus = $e->getMessage();
        }
        return $this;
    }

    public function setCoordinates(?float $latitude, ?float $longitude): GeocodableEntityInterface
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        if($this->isGeocoded())
        {
            $this->formattedAddress = $this->createGeocodeQueryString();
            $this->geocodedAt = new \DateTimeImmutable();
            $this->geocodeStatus = GeocodeStatus::OK;
        }
        else
        {
            $this->formattedAddress = null;
            $this->geocodedAt = null;
            $this->geocodeStatus = null;
        }
        $this->geocodeQuery = null;
        $this->geocodeResult = null;

        return $this;
    }

    public function createGeocodeQueryString(): string
    {
        return $this->street.', '.$this->streetNumber.', '.$this->zipCode.' '.$this->city.' '.$this->province.', '.$this->country;
    }

    public function getAddressHtml(): string
    {
        $address = trim($this->street.' '.$this->streetNumber);
        if($address) $address .= '<br/>';
        $address .= $this->zipCode.' '.$this->city;
        if($this->province) $address .= ' ('.$this->province.')';
        if($this->country) $address .= '<br/>'.$this->country;
        return $address;
    }

    public function getGmapsUrl(): ?string
    {
        return $this->isGeocoded()
            ? 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude
            : null;
    }


    /**************************************/
    /* GETTERS FOR SAVED GEOCODED DATA    */
    /**************************************/

    public function getGeocodedAt(): ?\DateTimeInterface
    {
        return $this->geocodedAt;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getFormattedAddress(): ?string
    {
        return $this->formattedAddress;
    }

    public function getGeocodeStatus(): ?string
    {
        return $this->geocodeStatus;
    }

    public function getGeocodeQuery(): ?string
    {
        return $this->geocodeQuery;
    }

    public function getGeocodeResult(): ?AddressCollection
    {
        $addressCollection = unserialize($this->geocodeResult);
        return $addressCollection instanceof AddressCollection
            ? $addressCollection
            : null;
    }

    /****************************************/
    /* GETTERS FOR MAP DISPLAY              */
    /****************************************/

    public function getInfoWindowContent(): ?string
    {
        return null;
    }

    public function getMarkerIcon(): ?string
    {
        return null;
    }

    /****************************************/
    /* GETTERS AND SETTERS FOR ADDRESS DATA */
    /****************************************/

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): self
    {
        $this->province = $province;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getGeocodeQueryLocale(): string
    {
        return $this->geocodeQueryLocale ?? 'it';
    }

    public function setGeocodeQueryLocale(string $geocodeQueryLocale): self
    {
        $this->geocodeQueryLocale = $geocodeQueryLocale;
        return $this;
    }



    /**************************************/
    /* SETTER FOR GEDMO TIMESTAMPABLE     */
    /**************************************/

    public function getAddressUpdatedAt(): ?\DateTimeInterface
    {
        return $this->addressUpdatedAt;
    }

    public function setAddressUpdatedAt($addressUpdatedAt)
    {
        $this->addressUpdatedAt = $addressUpdatedAt;
        return $this;
    }



    /**************************************/
    /* ADMIN INFO                         */
    /**************************************/

    public function getGeocodeInfoHtml()
    {
        $result = '';

        if($this->isGeocoded())
        {
            $result .= '<a href="'.$this->getGmapsUrl().'" target="_blank" class="btn radius-1 btn-sm btn-brc-tp btn-outline-primary btn-h-outline-primary btn-a-outline-primary btn-text-primary" data-toggle="tooltip" title="'.$this->formattedAddress.'"><i class="fas fa-map-marked-alt"></i></a>';
        }

        if($this->needGeocode())
        {
            $result .= '<i class="fas fa-exclamation-triangle text-warning" data-toggle="tooltip" title="Outdated position"></i>&nbsp;';
        }

        switch($this->geocodeStatus)
        {
            case GeocodeStatus::OK:
                $result .= '<span class="badge badge-success">OK</span>';
                break;
            case GeocodeStatus::ZERO_RESULTS:
                $result .= '<span class="badge badge-warning">ZERO_RESULTS</span>';
                break;
            default:
                if($this->geocodeStatus)
                {
                    $result .= '<span class="badge badge-danger" data-toggle="tooltip" title="'.$this->geocodeStatus.'">ERROR</span>';
                }
                break;
        }



        return $result;
    }
}