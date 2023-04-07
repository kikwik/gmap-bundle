<?php

namespace Kikwik\GmapBundle\Interfaces;

use Geocoder\Provider\Provider;

interface GeocodableEntityInterface
{
    /**
     * @return bool    true if the object has latitude and longitude
     */
    public function isGeocoded():bool;

    /**
     * @return bool     true if the object need a first or a new geocoding
     */
    public function getNeedGeocode(): bool;

    /**
     * Ask the geocoding to the provider and write the result in:
     * latitude, longitude, formattedAddress, geocodedAt, geocodeStatus
     *
     * @param Provider $provider
     * @return mixed
     */
    public function doGeocode(Provider $provider);

    /**
     * Set the coordinates withoud asking the geocoding
     *
     * @param float $latitude
     * @param float $longitude
     * @return mixed
     */
    public function setCoordinates(?float $latitude, ?float $longitude);

    /**
     * Compose the plain text address for geocoding
     *
     * @return string
     */
    public function getAddress(): string;

    /**
     * Compose the HTML address for display
     *
     * @return string
     */
    public function getAddressHtml(): string;

    /**
     * Compose the GMaps url (if geocoded data is present)
     *
     * @return string|null
     */
    public function getGmapsUrl(): ?string;

    /**
     * Getters for saved geocoded data
     */
    public function getGeocodedAt();
    public function getLatitude();
    public function getLongitude();
    public function getFormattedAddress();
    public function getGeocodeStatus();

    /**
     * Getters and Setters for address data
     */
    public function getStreet();
    public function setStreet($street);
    public function getStreetNumber();
    public function setStreetNumber($streetNumber);
    public function getZipCode();
    public function setZipCode($zipCode);
    public function getCity();
    public function setCity($city);
    public function getProvince();
    public function setProvince($province);

    /**
     * Getters and Setter for Gedmo Timestampable
     */
    public function getAddressUpdatedAt();
    public function setAddressUpdatedAt($addressUpdatedAt);


}