<?php

namespace App\Model\Queries\Listings;

use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby;

class ListingsForOverviewQuery extends QueryObject
{
    /** @var array|\Closure[] */
    private $filter = [];

    /** @var array|\Closure[] */
    private $select = [];

    /** @var int */
    private $month;

    /** @var int */
    private $year;

    /** @var User */
    private $user;

    public function withNumberOfWorkedDays()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('COUNT(li.id) AS worked_days');
        };

        return $this;
    }

    public function withTotalWorkedHours()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('SUM(time_to_sec(ADDTIME(SUBTIME(SUBTIME(wh.workEnd, wh.workStart), wh.lunch), wh.otherHours))) AS total_worked_hours');
        };

        return $this;
    }

    public function byUser(User $user)
    {
        $this->user = $user;

        $this->filter[] = function (QueryBuilder $qb) use ($user) {
            $qb->andWhere('l.user = :user')->setParameter('user', $user);
        };

        return $this;
    }

    public function byPeriod($year, $month = null)
    {
        $this->year = $year;
        $this->month = $month;

        $this->filter[] = function (QueryBuilder $qb) use ($year, $month) {
            $qb->andWhere('l.year = :year')->setParameter('year', $year);

            if ($month !== null) {
                $qb->andWhere('l.month = :month')->setParameter('month', $month);
            } else {
                $qb->addGroupBy('l.month');
            }

            $qb->addGroupBy('l.id');
            if ($month === null) {
                $qb->addOrderBy('l.month', 'DESC');
            }

            $qb->addOrderBy('l.id', 'DESC');
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
              ->select('partial l.{id, description, year, month}')
              ->from(Listing::class, 'l')
              ->leftJoin(ListingItem::class, 'li WITH li.listing = l')
              ->leftJoin('li.workedHours', 'wh');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int|null
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @return int|null
     */
    public function getYear()
    {
        return $this->year;
    }
}