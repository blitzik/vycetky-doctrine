<?php

namespace App\FrontModule\Presenters;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Domain\Entities\User;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Facades\ListingFacade;
use App\Model\Facades\LocalityFacade;
use App\Model\Query\ListingsQuery;
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
     * @var LocalityFacade
     * @inject
     */
    public $localityFacade;

    /**
     * @var ListingFacade
     * @inject
     */
    public $listingFacade;

    public function actionDefault()
    {
        $wh = new WorkedHours('06:00', '16:00', '01:00');

        dump($wh->toArray());
    }

    public function renderDefault()
    {

    }
}