<?php

namespace App\FrontModule\Presenters;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Domain\Entities\User;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Facades\ListingsFacade;
use App\Model\Facades\LocalitiesFacade;
use App\Model\Query\ListingsQuery;
use App\Model\Services\Readers\ListingItemsReader;
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\EntityManager;
use Rhumsaa\Uuid\Uuid;

class TestPresenter extends SecurityPresenter
{
    /**
     * @var EntityManager
     * @inject
     */
    public $em;

    /**
     * @var LocalitiesFacade
     * @inject
     */
    public $localityFacade;

    /**
     * @var ListingsFacade
     * @inject
     */
    public $listingsFacade;

    /**
     * @var ListingItemsReader
     * @inject
     */
    public $itemsReader;

    public function actionDefault()
    {
        $items = $this->itemsReader->findListingItems($this->em->getReference(Listing::class, 1));

        dump($items);
    }

    public function renderDefault()
    {

    }
}