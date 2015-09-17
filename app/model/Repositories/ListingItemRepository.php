<?php

namespace App\Model\Repositories;

use Exceptions\Runtime\ListingItemNotFoundException;
use Exceptions\Logic\InvalidArgumentException;
use \App\Model\Entities;
use Nette\Utils\Validators;

class ListingItemRepository extends BaseRepository
{
    /**
     *
     * @param int $listingItemID
     * @return Entities\ListingItem
     * @throws ListingItemNotFoundException
     */
    public function getListingItem($listingItemID, $listingID)
    {
        Validators::assert($listingItemID, 'numericint');
        Validators::assert($listingID, 'numericint');

        $result = $this->connection->select('*')
            ->from($this->getTable())
            ->where('listingItemID = ? AND listingID = ?',
                    $listingItemID, $listingID)
            ->fetch();

        if ($result == FALSE)
            throw new ListingItemNotFoundException;

        return $this->createEntity($result);
    }

    /**
     *
     * @param int $day
     * @param int $listingID
     * @return Entities\ListingItem
     */
    public function getByDayInListing($day, $listingID)
    {
        Validators::assert($day, 'numericint');
        Validators::assert($listingID, 'numericint');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where(
                                       'listingID = ? AND day = ?',
                                       $listingID,
                                       $day)
                                   ->fetch();

        if ($result == FALSE)
            throw new ListingItemNotFoundException;

        return $this->createEntity($result);
    }

    /**
     * @param int $itemID
     * @return Entities\ListingItem
     */
    public function findByItemID($itemID)
    {
        Validators::assert($itemID, 'numericint');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('listingItemID = ?', $itemID)
                                   ->fetch();

        if ($result == FALSE)
            throw new ListingItemNotFoundException;

        return $this->createEntity($result);
    }

    /**
     * @param int $listingID
     * @return Entities\ListingItem[]
     */
    public function findAllByListing($listingID)
    {
        Validators::assert($listingID, 'numericint');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('listingID = ?', $listingID)
                                   ->fetchAll();

        return $this->createEntities($result);
    }

    /**
     * @param int $day
     * @param $listingID
     */
    public function removeListingItem(
        $day,
        $listingID
    ) {
        Validators::assert($day, 'numericint');
        Validators::assert($listingID, 'numericint');

        $this->connection->delete($this->getTable())
                         ->where('
                            listingID = ? AND
                            day = ?',
                            $listingID,
                            $day
                         )->execute();
    }

    /**
     * @param int $day
     * @param $listingID
     * @return Entities\ListingItem
     * @throws ListingItemNotFoundException Item that supposed to be the copy was not found
     */
    public function shiftCopyOfListingItemDown(
        $day,
        $listingID
    ) {
        Validators::assert($day, 'numericint');
        Validators::assert($listingID, 'numericint');

        $this->connection->query(
            'INSERT INTO listing_item
             (listingID, day, localityID, workedHoursID,
              description, descOtherHours)
             SELECT listingID, (day + 1), localityID,
                    workedHoursID, description, descOtherHours
             FROM listing_item li
             WHERE listingID = ?', $listingID, ' AND
                   day = ?', $day,'
             ON DUPLICATE KEY
             UPDATE localityID = li.localityID,
                    workedHoursID = li.workedHoursID,
                    description = li.description,
                    descOtherHours = li.descOtherHours'
        );

        $entity = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('listingID = ? AND
                                            day = ?',
                                            $listingID,
                                            ($day + 1))
                                   ->execute()
                                   ->fetch();

        if ($entity === false) {
            throw new ListingItemNotFoundException;
        }

        return $this->createEntity($entity);
    }

    /**
     * @param array $listingItems
     * @throws InvalidArgumentException
     */
    public function saveListingItems(array $listingItems)
    {
        $values = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof Entities\ListingItem or
                !$listingItem->isDetached()) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }
            $values[] = $listingItem->getModifiedRowData();
        }

        $this->connection->query('INSERT INTO %n %ex', $this->getTable(), $values);
    }

    /**
     * @param array $listingItems
     * @param Entities\WorkedHours $workedHours
     */
    public function updateListingItemsWorkedHours(
        array $listingItems,
        Entities\WorkedHours $workedHours
    ) {
        $IDs = [];
        foreach ($listingItems as $item) {
            if (!$item instanceof Entities\ListingItem or
                $item->isDetached()) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }

            $IDs[] = $item->listingItemID;
        }

        $this->connection
             ->query('UPDATE %n', $this->getTable(),
                     'SET workedHoursID = ?', $workedHours->workedHoursID,
                     '%if', ($workedHours->otherHours->compare(0) === 0),', descOtherHours = null %end
                      WHERE listingItemID IN %in', $IDs);
    }

}