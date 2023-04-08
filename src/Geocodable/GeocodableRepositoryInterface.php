<?php

namespace Kikwik\GmapBundle\Geocodable;

use Doctrine\ORM\QueryBuilder;

interface GeocodableRepositoryInterface
{
    public function createNeedGeocodeQueryBuilder(string $alias): QueryBuilder;
    public function createFailedGeocodeQueryBuilder(string $alias): QueryBuilder;
}