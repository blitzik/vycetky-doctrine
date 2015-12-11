<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\User;
use Exceptions\Runtime\ListingNotFoundException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Nette\Object;

class ListingsReader extends Object
{
    /** @var EntityManager  */
    private $em;

    /** @var EntityRepository  */
    private $listingsRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->listingsRepository = $this->em->getRepository(Listing::class);
    }

    /**
     * @param QueryObject $listingsQuery
     * @return Listing|null
     * @throws ListingNotFoundException
     */
    public function fetchListing(QueryObject $listingsQuery)
    {
        $listing = $this->listingsRepository->fetchOne($listingsQuery);
        if ($listing === null) {
            throw new ListingNotFoundException;
        }

        return $listing;
    }

    /**
     * @param QueryObject $listingsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchListings(QueryObject $listingsQuery)
    {
        return $this->listingsRepository->fetch($listingsQuery);
    }

    /**
     * @param User $owner
     * @param int $year
     * @param int $month
     * @return array
     */
    public function findListingsToMerge(User $owner, $year, $month)
    {
        return $this->em->createQuery(
            'SELECT l.id, l.description FROM ' .Listing::class. ' l
             WHERE l.user = :user AND l.year = :year AND l.month = :month'
        )->setParameters(['user' => $owner, 'year' => $year, 'month' => $month])
         ->getArrayResult();
    }

    /**
     * @param $listingID
     * @return array|null
     */
    public function getWorkedDaysAndTime($listingID)
    {
        $qb = $this->getBasicDQL($listingID);
        $qb->resetDQLPart('select');

        $this->addWorkedDays($qb);
        $this->addTotalWorkedHours($qb);

        $qb->leftJoin(ListingItem::class, 'li WITH li.listing = l')
            ->leftJoin('li.workedHours', 'wh');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $listingID
     * @param bool $withWorkedTime
     * @return Listing|array|null
     */
    public function getByID($listingID, $withWorkedTime = false)
    {
        $qb = $this->getBasicDQL($listingID);

        if ($withWorkedTime === true) {
            $qb->leftJoin(ListingItem::class, 'li WITH li.listing = l')
                ->leftJoin('li.workedHours', 'wh');

            $this->addWorkedDays($qb);
            $this->addTotalWorkedHours($qb);
            $this->addWorkedHours($qb);
            $this->addLunchHours($qb);
            $this->addOtherHours($qb);

            $qb->groupBy('l.id');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * @param $year
     * @param User|null $user
     * @return array
     */
    public function getAnnualListingsForPDFGeneration($year, User $user = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l, partial u.{id, username, name}')
           ->from(Listing::class, 'l')
           ->leftJoin(ListingItem::class, 'li WITH li.listing = l')
           ->leftJoin('li.workedHours', 'wh')
           ->innerJoin('l.user', 'u');

        $this->addTotalWorkedHours($qb);
        $this->addWorkedHours($qb);
        $this->addLunchHours($qb);
        $this->addOtherHours($qb);

        if (isset($user)) {
            $qb->where('l.user = :user')
               ->setParameter('user', $user);
        }

        $qb->andWhere('l.year = :year')
           ->setParameter('year', $year)
           ->groupBy('l.id');

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * @param User|null $user
     * @return array
     */
    public function getListingsYears(User $user = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l.year, COUNT(l.id) AS numberOfListings')
           ->from(Listing::class, 'l');

        if (isset($user)) {
            $qb->where('l.user = :user')
               ->setParameter('user', $user);
        }

        $qb->groupBy('l.year');

        return $qb->getQuery()->getArrayResult();
    }

    private function addWorkedDays(QueryBuilder $qb)
    {
        $qb->addSelect('COUNT(li.id) AS worked_days');
    }

    private function addTotalWorkedHours(QueryBuilder $qb)
    {
        $qb->addSelect('SUM(time_to_sec(ADDTIME(SUBTIME(SUBTIME(wh.workEnd, wh.workStart), wh.lunch), wh.otherHours))) AS total_worked_hours_in_sec');
    }

    private function addWorkedHours(QueryBuilder $qb)
    {
        $qb->addSelect('SEC_TO_TIME(SUM(TIME_TO_SEC(SUBTIME(wh.workEnd, wh.workStart)))) AS worked_hours');
    }

    private function addLunchHours(QueryBuilder $qb)
    {
        $qb->addSelect('SEC_TO_TIME(SUM(TIME_TO_SEC(wh.lunch))) AS lunch_hours');
    }

    private function addOtherHours(QueryBuilder $qb)
    {
        $qb->addSelect('SEC_TO_TIME(SUM(TIME_TO_SEC(wh.otherHours))) AS other_hours');
    }

    private function getBasicDQL($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
           ->from(Listing::class, 'l')
           ->where('l.id = :id')->setParameter('id', $id);

        return $qb;
    }
}