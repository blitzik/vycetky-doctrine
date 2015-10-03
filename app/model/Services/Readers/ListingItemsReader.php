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

class ListingItemsReader extends Object
{
    const ITEM_UPPER = -1;
    const ITEM_LOWER = 1;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
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
        $itemQb->innerJoin('li.listing', 'l')
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
     * @param Listing $listing
     * @param array $days
     * @return array
     */
    public function findListingItems(Listing $listing, array $days = null)
    {
        $itemsQb = $this->getBasicDQL($listing);

        if (isset($days) and !empty($days)) {
            $itemsQb->andWhere('li.day IN(:days)')
                    ->setParameter('days', $days);
        }

        return $itemsQb->getQuery()->getResult();
    }

    /**
     * @param Listing $listing
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    private function getBasicDQL(Listing $listing)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('li, lo, wh')
           ->from(ListingItem::class, 'li')
           ->innerJoin('li.locality', 'lo')
           ->innerJoin('li.workedHours', 'wh')
           ->where('li.listing = :listing')
           ->setParameter('listing', $listing);

        return $qb;
    }
}