<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query;

use App\Application\UseCase\Query\AvailabilitiesInTimeIntervalByStationQueryInterface;
use App\Domain\Model\StationState\DateTimeImmutableStringable;
use App\Domain\Model\StationState\StationState;
use App\Domain\Model\StationState\StationStateStatus;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * @todo Create a Date(Period|Interval) as input to find method to specify from when to when to look for
 */
class DoctrineAvailabilitiesInTimeIntervalByStationQuery implements AvailabilitiesInTimeIntervalByStationQueryInterface
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

    public function find(UuidInterface $stationId, DateTimeImmutableStringable $statedAt): array
    {
        $intervalUpAt = $statedAt->modify('+1 hour');
        $intervalDownAt = $statedAt->modify('-1 hour');

        return $this->entityManager->createQueryBuilder()
            ->select([
                'time_bucket(\'5 minute\', ss.statedAt) AS interval',
                'avg(ss.availableBikeNumber) as available_bike_avg',
                'min(ss.availableBikeNumber) as available_bike_min',
                'max(ss.availableBikeNumber) as available_bike_max',
                'avg(ss.availableSlotNumber) as available_slot_avg',
                'min(ss.availableSlotNumber) as available_slot_min',
                'max(ss.availableSlotNumber) as available_slot_max',
            ])
            ->from(StationState::class, 'ss')
            ->where('ss.status = :status')
            ->andWhere('ss.stationAssigned = :stationId')
            ->andWhere('ss.statedAt > :intervalDown')
            ->andWhere('ss.statedAt < :intervalUp')
            ->groupBy('interval')
            ->orderBy('interval')
            ->setParameters([
                'status' => StationStateStatus::STATUS_OPENED,
                'stationId' => $stationId->toString(),
                'intervalUp' => $intervalUpAt->format('Y-m-d H:i:s'),
                'intervalDown' => $intervalDownAt->format('Y-m-d H:i:s'),
            ])
            ->getQuery()
            ->getResult();
    }
}