<?php

namespace App\Model\Repositories;

use Exceptions\Runtime\ListingNotFoundException;
use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use \App\Model\Entities;
use LeanMapper\Fluent;

class ListingRepository extends BaseRepository
{
    use TRepositoryModifiers;

    private function getListing(Callable $statement = null, array $args = null)
    {
        $query = $this->connection
            ->select('sec_to_time(sum(time_to_sec(wh.lunch)))')
                ->as('lunchHours')
            ->select('sec_to_time(sum(time_to_sec(wh.otherHours)))')
                ->as('otherHours')
            ->select('sec_to_time(sum(time_to_sec(subtime(wh.workEnd, wh.workStart))))')
                ->as('workedHours')
            ->select('SUM(time_to_sec(ADDTIME(
                        SUBTIME(SUBTIME(wh.workEnd, wh.workStart), wh.lunch),
                        wh.otherHours
                      )))')->as('totalWorkedHours');
        $query->select('COUNT(li.listingItemID)')->as('workedDays');

        $query->from($this->getTable())->as('l');
        $query->leftJoin('listing_item li ON (li.listingID = l.listingID)');
        $query->leftJoin('worked_hours wh ON (wh.workedHoursID = li.workedHoursID)');

        if ($statement !== null) {
            array_unshift($args, $query);
            call_user_func_array($statement, $args);
        }


        $data = $query->execute();
        $data->setType('lunchHours', \Dibi::TEXT);
        $data->setType('otherHours', \Dibi::TEXT);
        $data->setType('workedHours', \Dibi::TEXT);
        $data->setType('totalWorkedHours', \Dibi::INTEGER);

        return $data;
    }

    /**
     * @param int $userID
     * @param int $year
     * @param int $month
     * @return array
     */
    public function findUserListingsByPeriod($userID, $year, $month = null)
    {
        Validators::assert($userID, 'numericint');
        Validators::assert($year, 'numericint');
        Validators::assert($month, 'numericint|null');

        $result = $this->getListing(
            function (Fluent $st, $userID, $year, $month) {
                $st->select('l.*');
                $st->where('l.userID = ?', $userID, ' AND l.year = ?', $year,
                           '%if', isset($month), ' AND l.month = ?', $month, '%end');
                $st->groupBy('%if', !isset($month), 'l.month DESC, %end', 'l.listingID DESC');
            }, [$userID, $year, $month]
        );

        $data = $result->fetchAssoc('month|listingID');

        $entitiesCollectionByMonth = [];
        foreach ($data as $month => $entities) {
            foreach ($entities as $id => $entity) {
                $entitiesCollectionByMonth[$month][$id] = $this->createEntity($entity);
            }
        }
        krsort($entitiesCollectionByMonth);

        return $entitiesCollectionByMonth;
    }

    /**
     * @param int $userID
     * @param int $year
     * @param int $month
     * @return Entities\Listing[]
     */
    public function findPartialListings($userID, $year, $month)
    {
        Validators::assert($userID, 'numericint');
        Validators::assert($year, 'numericint');
        Validators::assert($month, 'numericint');

        $result = $this->connection
                       ->select('listingID, year, month, description')
                       ->from($this->getTable())
                       ->where('userID = ? AND year = ? AND month = ?', $userID, $year, $month)
                       ->groupBy('listingID')
                       ->orderBy('listingID DESC')
                       ->fetchAll();

        $entities = $this->createEntities($result);

        return $entities;
    }

    /**
     * @param $listingID
     * @param $userID
     * @return Entities\Listing
     * @throws \Exceptions\Runtime\ListingNotFoundException
     */
    public function getEntireListingByID($listingID, $userID)
    {
        Validators::assert($listingID, 'numericint');
        Validators::assert($userID, 'numericint');

        $result = $this->getListing(
            function (Fluent $st, $listingID, $userID) {
                $st->select('l.*');
                $st->where('l.listingID = ? AND l.userID = ?', $listingID, $userID);
                $st->groupBy('l.listingID');
            }, [$listingID, $userID]
        );

        $entity = $result->fetch();

        if ($entity == false) {
            throw new ListingNotFoundException;
        }

        return $this->createEntity($entity);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getListingByID($id)
    {
        Validators::assert($id, 'numericint');

        $entity = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('listingID = ?', $id)
                                   ->fetch();

        if ($entity === false) {
            throw new ListingNotFoundException;
        }

        return $this->createEntity($entity);
    }

    /**
     * @param int $userID
     * @param int $year
     * @param int $month
     * @return int
     */
    public function getNumberOfUserListingsByPeriod($userID, $year, $month)
    {
        Validators::assert($userID, 'numericint');
        Validators::assert($year, 'numericint');
        Validators::assert($month, 'numericint');

        $query = $this->connection->select('COUNT(listingID) as numberOfListings')
                                  ->from($this->getTable())
                                  ->where('userID = ?', $userID);

        $this->queryPeriodModifier($query, $year, $month);

        $result = $query->fetch();

        return $result['numberOfListings'];
    }

    /**
     * @param array $listings
     * @throws \DibiException
     */
    public function saveListings(array $listings)
    {
        $values = [];
        foreach ($listings as $listing) {
            if (!$listing instanceof Entities\Listing or
                !$listing->isDetached()) {
                throw new InvalidArgumentException(
                    'Only detached Instances of Listing entity can pass'
                );
            }
            $listing->excludeTemporaryFields();

            $values[] = $listing->getModifiedRowData();
        }

        $this->connection->query('INSERT INTO %n %ex', $this->getTable(), $values);

        $insertedID = $this->connection->getInsertId(); // first inserted ID
        foreach ($listings as $listing) {
            $listing->makeAlive($this->entityFactory, $this->connection, $this->mapper);
            $listing->attach($insertedID);

            $insertedID++;
        }
    }

}