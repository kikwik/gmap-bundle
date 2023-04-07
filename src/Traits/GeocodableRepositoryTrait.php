<?php

namespace Kikwik\GmapBundle\Traits;

use Doctrine\ORM\QueryBuilder;

trait GeocodableRepositoryTrait
{
    public function createNeedGeocodeQueryBuilder(string $alias): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere($alias.'.geocodeStatus IS NULL OR '.$alias.'.addressUpdatedAt > '.$alias.'.geocodedAt');
    }

    public function createFailedGeocodeQueryBuilder(string $alias): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere($alias.'.geocodeStatus IS NOT NULL')
            ->andWhere($alias.'.geocodeStatus <> :geocodeStatus')
            ->setParameter('geocodeStatus','OK');
    }
}