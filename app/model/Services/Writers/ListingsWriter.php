<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\Listing;
use App\Model\Services\Readers\ListingsReader;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;

class ListingsWriter extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ListingsReader
     */
    private $listingsReader;

    public function __construct(
        EntityManager $entityManager,
        ListingsReader $listingsReader
    ) {
        $this->em = $entityManager;
        $this->listingsReader = $listingsReader;
    }

    /**
     * @param Listing $listing
     * @return Listing
     * @throws \Exception
     */
    public function saveListing(Listing $listing)
    {
        try {
            $this->em->persist($listing)->flush();

            return $listing;
        } catch (\Exception $e) {
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }
}