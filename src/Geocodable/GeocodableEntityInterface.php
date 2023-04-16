<?php

namespace Kikwik\GmapBundle\Geocodable;

use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;

interface GeocodableEntityInterface
{
    /**
     * @return bool    true if the object has latitude and longitude
     */
    public function isGeocoded(): bool;

    /**
     * @return bool     true if the object need a first or a new geocoding
     */
    public function needGeocode(): bool;

    /**
     * Ask the geocoding to the provider
     * updated fields: latitude, longitude, formattedAddress, geocodedAt, geocodeStatus, geocodeQuery, geocodeResult
     *
     * @param Provider $provider
     * @return mixed
     */
    public function doGeocode(Provider $provider): GeocodableEntityInterface;

    /**
     * Set the coordinates withoud asking the geocoding
     * updated fields: latitude, longitude, formattedAddress, geocodedAt, geocodeStatus
     * set null to fields: geocodeQuery, geocodeResult
     *
     * @param float $latitude
     * @param float $longitude
     * @return mixed
     */
    public function setCoordinates(?float $latitude, ?float $longitude): GeocodableEntityInterface;

    /**
     * Compose the plain text address for geocoding
     *
     * @return string
     */
    public function createGeocodeQueryString(): string;

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
    public function getGeocodedAt(): ?\DateTimeInterface;
    public function getLatitude(): ?float;
    public function getLongitude(): ?float;
    public function getFormattedAddress(): ?string;
    public function getGeocodeStatus(): ?string;
    public function getGeocodeQuery(): ?string;
    public function getGeocodeResult(): ?AddressCollection;

    /**
     * Getters for map display
     */
    public function getInfoWindowContent(): ?string;
    public function getMarkerIcon(): ?string;

    /**
     * Getters and Setters for address data
     */
    public function getStreet(): ?string;
    public function setStreet(?string $street);
    public function getStreetNumber(): ?string;
    public function setStreetNumber(?string $streetNumber);
    public function getZipCode(): ?string;
    public function setZipCode(?string $zipCode);
    public function getCity(): ?string;
    public function setCity(?string $city);
    public function getProvince(): ?string;
    public function setProvince(?string $province);
    public function getCountry(): ?string;
    public function setCountry(?string $country);
    public function getGeocodeQueryLocale(): string;
    public function setGeocodeQueryLocale(string $geocodeQueryLocale);

    /**
     * Getters and Setter for Gedmo Timestampable
     */
    public function getAddressUpdatedAt(): ?\DateTimeInterface;
    public function setAddressUpdatedAt($addressUpdatedAt);


}