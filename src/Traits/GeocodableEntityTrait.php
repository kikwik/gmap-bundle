<?php

namespace Kikwik\GmapBundle\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;


trait GeocodableEntityTrait
{
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

    public function isGeocoded(): bool
    {
        return $this->latitude && $this->longitude;
    }

    public function getNeedGeocode(): bool
    {
        return !$this->isGeocoded() || $this->addressUpdatedAt > $this->geocodedAt;
    }

    public function doGeocode(Provider $provider)
    {
        try {
            $results = $provider->geocodeQuery(GeocodeQuery::create($this->getAddress()));
            foreach($results as $googleAddress)
            {
                $this->latitude = $googleAddress->getCoordinates()->getLatitude();
                $this->longitude = $googleAddress->getCoordinates()->getLongitude();
                $this->formattedAddress = $googleAddress->getFormattedAddress();
                $this->geocodedAt = new \DateTimeImmutable();
                $this->geocodeStatus = 'OK';
                break;
            }
        }
        catch(\Throwable $e)
        {
            $this->geocodeStatus = $e->getMessage();
        }
    }

    public function setCoordinates(?float $latitude, ?float $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        if($this->isGeocoded())
        {
            $this->formattedAddress = $this->getAddress();
            $this->geocodedAt = new \DateTimeImmutable();
            $this->geocodeStatus = 'OK';
        }
        else
        {
            $this->formattedAddress = null;
            $this->geocodedAt = null;
            $this->geocodeStatus = null;
        }
    }

    public function getAddress(): string
    {
        return $this->street.', '.$this->streetNumber.', '.$this->zipCode.' '.$this->city.' '.$this->province.', '.$this->country;
    }

    public function getAddressHtml(): string
    {
        $address = $this->street.' '.$this->streetNumber.'<br/>';
        $address .= $this->zipCode.' '.$this->city;
        if($this->province) $address .= ' ('.$this->province.')';
        return $address;
    }

    public function getGmapsUrl(): ?string
    {
        return $this->isGeocoded()
            ? 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude
            : null;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param mixed $street
     * @return GeocodableEntityTrait
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @param mixed $streetNumber
     * @return GeocodableEntityTrait
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param mixed $zipCode
     * @return GeocodableEntityTrait
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     * @return GeocodableEntityTrait
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @param mixed $province
     * @return GeocodableEntityTrait
     */
    public function setProvince($province)
    {
        $this->province = $province;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return GeocodableEntityTrait
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddressUpdatedAt()
    {
        return $this->addressUpdatedAt;
    }

    /**
     * @param mixed $addressUpdatedAt
     * @return GeocodableEntityTrait
     */
    public function setAddressUpdatedAt($addressUpdatedAt)
    {
        $this->addressUpdatedAt = $addressUpdatedAt;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGeocodedAt()
    {
        return $this->geocodedAt;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @return mixed
     */
    public function getFormattedAddress()
    {
        return $this->formattedAddress;
    }

    /**
     * @return mixed
     */
    public function getGeocodeStatus()
    {
        return $this->geocodeStatus;
    }

}