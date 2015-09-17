<?php

namespace App\Model\Repositories;

use App\Model\Entities\WorkedHours;
use Nette\Utils\Validators;
use Tracy\Debugger;

class WorkedHoursRepository extends BaseRepository
{
    /**
     * @param array $conditions
     * @return WorkedHours
     */
    public function getByValues(array $conditions)
    {
        $result = $this->connection->select('*')
                       ->from($this->getTable())
                       ->where('%and', $conditions)
                       ->fetch();

        if ($result == FALSE)
            throw new \Exceptions\Runtime\WorkedHoursNotFoundException;

        return $this->createEntity($result);
    }

    public function getTotalWorkedStatistics($userID)
    {
        Validators::assert($userID, 'numericint');

        $result = $this->connection->query(
            'SELECT SUM(time_to_sec(ADDTIME(
                        SUBTIME(SUBTIME(wh.workEnd, wh.workStart), wh.lunch),
                        wh.otherHours))
                    ) as workedHours,
                    COUNT(li.listingItemID) AS workedDays
             FROM listing l
             INNER JOIN listing_item li ON (l.listingID = li.listingID)
             INNER JOIN worked_hours wh ON (wh.workedHoursID = li.workedHoursID)
             WHERE l.userID = ?', $userID, 'GROUP BY l.userID'
        )->fetch();

        return $result;
    }

    /**
     * @param WorkedHours $workedHours
     * @return WorkedHours
     * @throws \DibiException
     */
    public function setupWorkedHours(WorkedHours $workedHours)
    {
        $values = ['workStart' => $workedHours->workStart->getTime(),
                   'workEnd' => $workedHours->workEnd->getTime(),
                   'lunch' => $workedHours->lunch->getTime(),
                   'otherHours' => $workedHours->otherHours->getTime()];

        try {
            $this->connection
                 ->query('INSERT INTO [worked_hours]', $values, '
                          ON DUPLICATE KEY UPDATE
                          workedHoursID = LAST_INSERT_ID(workedHoursID)');

            $id = $this->connection->getInsertId();
            if (!$workedHours->isDetached()) {
                $workedHours->detach();
            }

            $workedHours->makeAlive($this->entityFactory, $this->connection, $this->mapper);
            $workedHours->attach($id);

            return $workedHours;

        } catch (\DibiException $e) {

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

}