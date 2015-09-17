<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Domain\Entities\Listing;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby;

class ListingItemsQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];


    public function byId($id)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('li.id = :id')->setParameter('id', $id);
        };

        return $this;
    }

    public function byListing(Listing $listing)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($listing) {
            $qb->andWhere('li.listing = :listing')->setParameter('listing', $listing);
        };

        return $this;
    }

    public function byDay($day)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($day) {
            $qb->andWhere('li.day = :day')->setParameter('day', $day);
        };

        return $this;
    }

    /**
     * @param \Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $this->createBasicDql($repository);

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

    /**
     * @param Kdyby\Persistence\Queryable|Kdyby\Doctrine\EntityDao $repository
     * @return Kdyby\Doctrine\NativeQueryBuilder
     */
    private function createBasicDql(Kdyby\Persistence\Queryable $repository)
    {
        $qb = (new QueryBuilder($repository->getEntityManager()))
            ->select('li, lo, wh')
            ->from(ListingItem::class, 'li')
            ->join('li.locality', 'lo')
            ->join('li.workedHours', 'wh');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }
}