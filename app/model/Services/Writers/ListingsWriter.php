<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\Listing;
use App\Model\Services\Readers\ListingsReader;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ListingsWriter extends Object
{
    /** @var array */
    public $onCritical = [];

    /** @var EntityManager  */
    private $em;

    /** @var ListingsReader  */
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
            $this->onCritical('Saving of Listing failed. [saveListing]', $e, self::class);

            throw $e;
        }
    }


    /**
     * @param Listing $listing
     * @throws \Exception
     */
    public function removeListing(Listing $listing)
    {
        try {
            $this->em->remove($listing)->flush();
        } catch (\Exception $e) {
            $this->onCritical(sprintf('Removal of Listing #id(%s) failed. [%s]', $listing->getId(), 'removeListing'), $e, self::class);
            throw $e;
        }
    }


}