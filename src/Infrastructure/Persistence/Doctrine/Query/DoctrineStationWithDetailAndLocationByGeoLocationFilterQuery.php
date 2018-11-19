<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query;

use App\Application\UseCase\Filter\ByGeoLocationFilter;
use App\Application\UseCase\Query\StationWithDetailAndLocationByGeoLocationFilterQueryInterface;
use App\Domain\Model\Station\Station;
use App\Infrastructure\Persistence\Doctrine\Query\Selector\DoctrineStationWithDetailAndLocationSelector;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineStationWithDetailAndLocationByGeoLocationFilterQuery implements StationWithDetailAndLocationByGeoLocationFilterQueryInterface // phpcs:ignore
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param ByGeoLocationFilter $filter
     *
     * @return array
     */
    public function findAll(ByGeoLocationFilter $filter): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select(DoctrineStationWithDetailAndLocationSelector::FIELD_SELECTOR)
            ->from(Station::class, 's')
            ->where(
                'ST_Distance(s.location.geometry, ST_Transform(ST_SetSRID(ST_MakePoint(:lg, :la),4326),2163)) < :lt'
            )
            ->setParameters([
                'la' => $filter->latitude(),
                'lg' => $filter->longitude(),
                'lt' => $filter->limit(),
            ])
            ->getQuery()
            ->getResult();
    }
}