<?php

namespace App\Model\Services\Providers;

use App\Model\Domain\Entities\WorkedHours;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class WorkedHoursProvider extends Object
{
    /** @var EntityManager  */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param WorkedHours $workedHours
     * @return WorkedHours
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function setupWorkedHoursEntity(WorkedHours $workedHours)
    {
        $values = $workedHours->toArray(true);

        /* In order to NOT auto increment workedHours ID counter in DB by
           INSERTs that actually wont happen (e.g. safePersist()) and
           because Doctrine2 does NOT support locking of entire tables,
           we have to use native SQL(MySQL) query.
        */
        $this->em->getConnection()->executeQuery(
            'INSERT INTO worked_hours (work_start, work_end, lunch, other_hours)
             SELECT :workStart, :workEnd, :lunch, :otherHours FROM worked_hours
             WHERE NOT EXISTS(
                   SELECT work_start, work_end, lunch, other_hours
                   FROM worked_hours
                   WHERE work_start = :workStart AND work_end = :workEnd AND
                         lunch = :lunch AND other_hours = :otherHours)
             LIMIT 1'
            , $values);

        $result = $this->em->createQuery(
            'SELECT wh AS workedHours FROM '.WorkedHours::class.' wh
             WHERE wh.workStart = :workStart AND wh.workEnd = :workEnd AND
                   wh.lunch = :lunch AND wh.otherHours = :otherHours'
        )->setParameters($values)
         ->getSingleResult()['workedHours'];

        return $result;
    }
}