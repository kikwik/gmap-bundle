<?php

namespace Kikwik\GmapBundle\Service;

use Geocoder\Provider\Provider;

class Geocoder
{
    /**
     * @var Provider
     */
    private $googleMapsGeocoder;

    public function __construct(Provider $googleMapsGeocoder)
    {
        $this->googleMapsGeocoder = $googleMapsGeocoder;
    }

    public function getProvider()
    {
        return $this->googleMapsGeocoder;
    }
}