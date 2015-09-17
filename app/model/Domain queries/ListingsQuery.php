<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Nette\Utils\Validators;
use Kdyby;

class ListingsQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];


    public function forOverviewDatagrid()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->resetDQLPart('select');

            $qb->select('l.id, l.description, l.year, l.month');
        };

        return $this;
    }

    public function resetSelect()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->resetDQLPart('select');
        };

        return $this;
    }

    public function withNumberOfWorkedDays()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('COUNT(li.id) AS worked_days');
        };

        return $this;
    }

    public function withLunchHours()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('SEC_TO_TIME(SUM(TIME_TO_SEC(wh.lunch))) AS lunch_hours');
        };

        return $this;
    }

    public function withOtherHours()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('SEC_TO_TIME(SUM(TIME_TO_SEC(wh.otherHours))) AS other_hours');
        };

        return $this;
    }

    public function withWorkedHours()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('SEC_TO_TIME(SUM(TIME_TO_SEC(SUBTIME(wh.workEnd, wh.workStart)))) AS worked_hours');
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

    public function withUser()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('u');
            $qb->innerJoin('l.user', 'u');
        };

        return $this;
    }

    public function byId($id)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('l.id = :id')->setParameter('id', $id);
        };

        return $this;
    }

    public function byUser(User $user)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($user) {
            $qb->andWhere('l.user = :user')->setParameter('user', $user);
        };

        return $this;
    }

    public function byPeriod($year, $month = null)
    {
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
            ->select('l AS listing')
            ->from(Listing::class, 'l')
            ->leftJoin(ListingItem::class, 'li WITH li.listing = l')
            ->leftJoin('li.workedHours', 'wh');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }
}