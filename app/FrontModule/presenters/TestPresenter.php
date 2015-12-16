<?php

namespace App\FrontModule\Presenters;

use App\Model\Facades\ListingsFacade;
use App\Model\Facades\LocalitiesFacade;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\UsersFacade;
use App\Model\Pdf\Listing\Generators\AnnualPdfGenerator;
use App\Model\Services\InvitationHandler;
use App\Model\Services\Providers\LocalityProvider;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Readers\ListingsReader;
use App\Model\Services\Readers\MessagesReader;
use App\Model\Services\Readers\UsersReader;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\ForbiddenRequestException;
use Nette\Caching\IStorage;

/**
 * @Role admin
 */
class TestPresenter extends SecurityPresenter
{
    /**
     * @var EntityManager
     * @inject
     */
    public $em;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messagesFacade;

    /**
     * @var MessagesReader
     * @inject
     */
    public $messagesReader;

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
    public $listingItemsReader;

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
     * @var UsersReader
     * @inject
     */
    public $usersReader;

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

    /**
     * @var LocalityProvider
     * @inject
     */
    public $localityProvider;

    /**
     * @var IStorage
     * @inject
     */
    public $cacheStorage;

    /**
     * @var AnnualPdfGenerator
     * @inject
     */
    public $annualPdfGenerator;

    protected function startup()
    {
        if (!$this->user->isInRole('admin')) {
            throw new ForbiddenRequestException;
        }

        parent::startup();
    }


    public function actionDefault()
    {
        $this->annualPdfGenerator->generate(2015, $this->user->getIdentity(), ['userSettings' => ['name' => 'alda :-)']]);
    }

    public function renderDefault()
    {
        //dump($this->name);
    }
}