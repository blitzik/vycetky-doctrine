<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IListingCopyFormControlFactory;
use App\Model\Components\IListingPDFGenerationControlFactory;
use App\Model\Components\IListingRemovalControlFactory;
use App\Model\Components\ListingTable\IListingTableControlFactory;
use App\Model\Components\IListingActionsMenuControlFactory;
use App\Model\Components\IListingsOverviewControlFactory;
use App\Model\Components\IMassItemsChangeControlFactory;
use App\Model\Components\ISharingListingControlFactory;
use App\Model\Components\IListingFormControlFactory;
use App\Model\Components\IFilterControlFactory;
use App\Model\Components\ListingFormFactory;
use App\Model\Domain\Entities\Listing;
use App\Model\Query\ListingsQuery;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\UserManager;
use App\Model\Facades\ItemsFacade;
use Exceptions\Runtime;
use App\Model\Entities;

class ListingPresenter extends SecurityPresenter
{
    use TListing;

    /** @persistent */
    public $backlink = null;

    /**
     * @var IListingPDFGenerationControlFactory
     * @inject
     */
    public $listingPDFGenerationControlFactory;

    /**
     * @var ISharingListingControlFactory
     * @inject
     */
    public $sharingListingTableControlFactory;

    /**
     * @var IListingActionsMenuControlFactory
     * @inject
     */
    public $listingActionsMenuControlFactory;

    /**
     * @var IMassItemsChangeControlFactory
     * @inject
     */
    public $massItemChangeControlFactory;

    /**
     * @var IListingCopyFormControlFactory
     * @inject
     */
    public $listingCopyFormControlFactory;

    /**
     * @var IListingRemovalControlFactory
     * @inject
     */
    public $listingRemovalControlFactory;

    /**
     * @var IListingTableControlFactory
     * @inject
     */
    public $listingTableControlFactory;

    /**
     * @var IListingFormControlFactory
     * @inject
     */
    public $listingFormControlFactory;

    /**
     * @var IListingsOverviewControlFactory
     * @inject
     */
    public $listingsOverviewFactory;

    /**
     * @var IFilterControlFactory
     * @inject
     */
    public $filterControlFactory;

    /**
     * @var ListingFormFactory
     * @inject
     */
    public $listingFormFactory;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messageFacade;

    /**
     * @var UserManager
     * @inject
     */
    public $userManager;

    /**
     * @var ItemsFacade
     * @inject
     */
    public $itemsFacade;

    /**
     * @var Listing
     */
    private $listing;

    private $numberOfMessages;

    /**
     * @Actions detail, pdfGeneration, edit, copy, massItemsChange, share, remove
     */
    protected function createComponentListingActionsMenu()
    {
        $comp = $this->listingActionsMenuControlFactory->create($this->listing);
        return $comp;
    }

    /*
     * --------------------
     * ----- OVERVIEW -----
     * --------------------
     */

    public function actionOverview($month, $year)
    {
        $this->setPeriodParametersForFilter($year, $month);

        $listingsData = $this->listingsFacade->fetchListings(
            (new ListingsQuery())
                ->forOverviewDatagrid()
                ->withNumberOfWorkedDays()
                ->withTotalWorkedHours()
                ->byUser($this->user->getIdentity())
                ->byPeriod($year, $month)
        )->toArray();

        $this['listingsOverview']->setListings($listingsData);
    }

    public function renderOverview($month, $year)
    {
        /*$this->numberOfMessages = $this->messageFacade
             ->getNumberOfReceivedMessages(Entities\Message::UNREAD);*/

        //$this->template->numberOfMessages = $this->numberOfMessages;
    }

    /**
     * @Actions overview
     */
    protected function createComponentListingsOverview()
    {
        $comp = $this->listingsOverviewFactory->create();

        $comp->setHeading('Mé výčetky');

        return $comp;
    }

    /**
     * @Actions overview
     */
    protected function createComponentFilter()
    {
        return $this->filterControlFactory->create();
    }

    /*
     * ------------------------------
     * ----- ADD / EDIT Listing -----
     * ------------------------------
     */

    public function actionAdd()
    {
    }

    public function renderAdd()
    {
    }

    public function actionEdit($id)
    {
        $this->listing = $this->getListingByID($id);
    }

    public function renderEdit($id)
    {
    }

    /**
     * @Actions add, edit
     */
    protected function createComponentListingForm()
    {
        return $this->listingFormControlFactory->create($this->listing);
    }

    /*
     * -----------------------------------
     * ----- REMOVE existing Listing -----
     * -----------------------------------
     */

    public function actionRemove($id)
    {
        $this->listing = $this->getListingById($id);
    }

    public function renderRemove($id)
    {

    }

    /**
     * @Actions remove
     */
    protected function createComponentListingRemovalForm()
    {
        $comp = $this->listingRemovalControlFactory->create($this->listing);

        $comp->onRemoveSuccess[] = [$this, 'onListingRemoveSuccess'];
        $comp->onCancelClick[]   = [$this, 'onListingRemovalCancelClick'];

        return $comp;
    }

    public function onListingRemoveSuccess($year, $month)
    {
        $this->flashMessage('Výčetka byla odstraněna.', 'success');

        $this->backlink = null;
        $this->redirect(
            'Listing:overview',
            ['month' => $month, 'year'  => $year]
        );
    }

    public function onListingRemovalCancelClick($year, $month)
    {
        if (isset($this->backlink))
            $this->restoreRequest($this->backlink);

        $this->redirect(
            'Listing:overview',
            ['month' => $month, 'year'  => $year]
        );
    }

    /*
     * ------------------
     * ----- DETAIL -----
     * ------------------
     */

    public function actionDetail($id)
    {
        $this->listing = $this->getListingById($id);
    }

    public function renderDetail($id)
    {
        $this->template->listing = $this->listing;
    }

    /**
     * @Actions detail
     */
    protected function createComponentListingItemsTable()
    {
        $comp = $this->listingTableControlFactory->create($this->listing);

        return $comp;
    }

    /*
     * ------------------------
     * ----- Copy Listing -----
     * ------------------------
     */

    public function actionCopy($id)
    {
        $this->listing = $this->getListingByID($id);
    }

    public function renderCopy($id)
    {
    }

    /**
     * @Actions copy
     */
    protected function createComponentSimpleCopyForm()
    {
        $comp = $this->listingCopyFormControlFactory->create($this->listing);

        $comp->onListingCopySuccess[] = [$this, 'onListingCopySuccess'];

        return $comp;
    }

    public function onListingCopySuccess(Listing $listing)
    {
        $this->flashMessage('Byla založena kopie výčetky.', 'success');
        $this->redirect(
            'Listing:overview',
            array('year'  => $listing->getYear(),
                  'month' => $listing->getMonth())
        );
    }

    /*
     * ----------------------------
     * ----- Mass item change -----
     * ----------------------------
     */

    public function actionMassItemsChange($id)
    {
        try {
            $listingData = $this->listingsFacade
                                ->fetchListing((new ListingsQuery())
                                                ->withNumberOfWorkedDays());

            if ($listingData['worked_days'] == 0) {
                $this->flashMessage('Nelze upravovat prázdnou výčetku.', 'warning');
                $this->redirect('Listing:detail', ['id' => $id]);
            }

            $this->listing = $listingData['listing'];

        } catch (Runtime\ListingNotFoundException $e) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'warning');
            $this->redirect('Listing:overview');
        }
    }

    public function renderMassItemsChange($id)
    {

    }

    /**
     * @Actions massItemsChange
     */
    protected function createComponentMassItemChangeTable()
    {
        $comp = $this->massItemChangeControlFactory->create($this->listing);

        return $comp;
    }

    /*
     * ----------------------------
     * ----- Sharing listings -----
     * ----------------------------
     */

    public function actionShare($id)
    {
        $this->listing = $this->getEntireListingByID($id);
        if ($this->listing->workedDays == 0) {
            $this->flashMessage('Nelze sdílet prázdnou výčetku.', 'warning');
            $this->redirect('Listing:detail', ['id' => $id]);
        }
    }

    public function renderShare($id)
    {

    }

    /**
     * @Actions share
     */
    protected function createComponentListingTableForSharing()
    {
        return $this->sharingListingTableControlFactory->create($this->listing);
    }

    /*
     * --------------------------
     * ----- Generating PDF -----
     * --------------------------
     */

    public function actionPdfGeneration($id)
    {
        $this->listing = $this->getListingById($id);
    }

    public function renderPdfGeneration($id)
    {

    }

    /**
     * @Actions pdfGeneration
     */
    protected function createComponentListingPDFGeneration()
    {
        return $this->listingPDFGenerationControlFactory->create($this->listing);
    }

}