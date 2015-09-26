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
use App\Model\Facades\UsersFacade;
use App\Model\Query\ListingsQuery;
use App\Model\Query\UsersQuery;
use App\Model\Services\Readers\ListingItemsReader;
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\EntityManager;
use Nette\Utils\DateTime;

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

    public function actionDefault()
    {
        dump($this->usersFacade->fetchUsers(
            (new UsersQuery())->findUsersBlockedByMe($this->user->getIdentity())
        )->toArray());

        //dump($this->user->getIdentity()->getAllUsersBlockedByMe());
        //dump($this->user->getIdentity()->getAllUsersBlockingMe());
    }

    public function renderDefault()
    {

    }
}