<?php

namespace App\Model\Services\Readers;

use Doctrine\ORM\NoResultException;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\ListingItemNotFoundException;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Listing;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;

class ListingItemsReader extends Object
{
    const ITEM_UPPER = -1;
    const ITEM_LOWER = 1;

    /** @var EntityManager  */
    private $em;

    /** @var EntityRepository  */
    private $listingItemRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->listingItemRepository = $this->em->getRepository(ListingItem::class);
    }

    /**
     * @param $day
     * @param Listing $listing
     * @return ListingItem|null
     */
    public function getByDay($day, Listing $listing)
    {
        $itemQb = $this->getBasicDQL($listing);
        $itemQb->addSelect('l, partial u.{id, username, role}');
        $itemQb//->innerJoin('li.listing', 'l')
               ->innerJoin('l.user', 'u');
        $itemQb->andWhere('li.day = :day')->setParameter('day', $day);

        return $itemQb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param ListingItem $listingItem
     * @param int $position
     * @return mixed
     * @throws ListingItemNotFoundException
     */
    public function getAdjacentItem(ListingItem $listingItem, $position)
    {
        $day = $listingItem->day;
        switch ($position) {
            case self::ITEM_LOWER: $day += self::ITEM_LOWER; break;
            case self::ITEM_UPPER: $day += self::ITEM_UPPER; break;
            default: throw new InvalidArgumentException('Invalid argument $position.');
        }

        $itemQuery = $this->getBasicDQL($listingItem->getListing());
        $itemQuery->andWhere('li.day = :day')
                  ->setParameter('day', $day);

        try {
            return $itemQuery->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new ListingItemNotFoundException;
        }
    }

    /**
     * @param int $listingID
     * @param array $days
     * @param bool $ignored
     * @return array
     */
    public function findListingItems(
        $listingID,
        array $days = null,
        $ignored = false
    ) {
        $itemsQb = $this->getBasicDQL($listingID);

        if (isset($days) and !empty($days)) {
            if ($ignored === false) {
                $itemsQb->andWhere('li.day IN(:days)');
            } else {
                $itemsQb->andWhere('li.day NOT IN(:days)');
            }
            $itemsQb->setParameter('days', $days);
        }

        return $itemsQb->getQuery()->getResult();
    }

    /**
     * @param array $listingsIDs
     * @return array
     */
    public function findListingsItems(
        array $listingsIDs
    ) {
        $qb = $this->getBasicDQL();
        $qb->addSelect('l');

        $qb->where('li.listing IN (:listings)')
           ->setParameter('listings', $listingsIDs);

        $qb->orderBy('li.listing');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $listingID
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    private function getBasicDQL($listingID = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('li, lo, wh')
           ->from(ListingItem::class, 'li')
           ->innerJoin('li.listing', 'l')
           ->innerJoin('li.locality', 'lo')
           ->innerJoin('li.workedHours', 'wh');

        if (isset($listingID)) {
            $qb->where('li.listing = :listingID')
               ->setParameter('listingID', $listingID);
        }

        return $qb;
    }
}