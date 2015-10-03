<?php

namespace App\FrontModule\Presenters;


use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ListingsFacade;
use App\Model\Facades\LocalitiesFacade;
use App\Model\Facades\UsersFacade;
use App\Model\Services\InvitationHandler;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Readers\ListingsReader;
use Kdyby\Doctrine\EntityManager;

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

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;

    /**
     * @var ListingsReader
     * @inject
     */
    public $listingsReader;

    /**
     * @var InvitationHandler
     * @inject
     */
    public $invHandler;

    public function actionDefault()
    {
        $i = $this->itemsReader->getByDay(2, $this->em->getReference(Listing::class, 3));
        dump();
    }

    public function renderDefault()
    {

    }
}