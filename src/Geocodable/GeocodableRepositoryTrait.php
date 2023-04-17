<?php

namespace Kikwik\GmapBundle\Geocodable;

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
            ->andWhere($alias.'.geocodeStatus NOT IN (:geocodeStatuses)')
            ->setParameter('geocodeStatuses',[GeocodeStatus::OK,GeocodeStatus::ZERO_RESULTS]);
    }

    public function getGeocodedQueryBuilder(string $alias): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere($alias.'.geocodeStatus = :statusOk')
            ->setParameter('statusOk',GeocodeStatus::OK);
    }

    /**
     * @return GeocodableEntityInterface[]
     */
    public function findAllGeocoded()
    {
        return $this->getGeocodedQueryBuilder('g')
            ->getQuery()
            ->getResult();
    }

    public function findAllGeocodedtoMapArray(): array
    {
        $objects = $this->findAllGeocoded();
        $data = [];
        foreach($objects as $object)
        {
            $data[] = $object->toMapArray();
        }
        return $data;
    }
}