<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IAnnualPDFGenerationControlFactory;
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
use App\Model\Database\Backup\DatabaseBackupFile;
use App\Model\Domain\Entities\Listing;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\ItemsFacade;
use App\Model\Queries\Listings\ListingsForOverviewQuery;
use App\Model\Query\ReceivedMessagesQuery;
use App\Model\ResultObjects\ListingResult;
use App\Model\Services\ListingPDFGenerator;
use App\Model\Services\Readers\ListingsReader;
use Exceptions\Runtime;
use App\Model\Entities;
use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\InvalidArgumentException;
use Nette\Utils\Arrays;
use Tracy\Debugger;

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
     * @var IAnnualPDFGenerationControlFactory
     * @inject
     */
    public $annualPDFGenerationControlFactory;

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
     * @var ItemsFacade
     * @inject
     */
    public $itemsFacade;

    /**
     * @Actions detail, pdfGeneration, edit, copy, massItemsChange, share, remove
     */
    protected function createComponentListingActionsMenu()
    {
        $comp = $this->listingActionsMenuControlFactory->create($this->listingResult->getListing());
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
    }

    public function renderOverview($month, $year)
    {
        $numberOfMessages = $this->messageFacade
                                       ->fetchReceivedMessages(
                                           (new ReceivedMessagesQuery())
                                           ->byRecipient($this->user->getIdentity())
                                           ->onlyActive()
                                           ->findUnreadMessages()
                                       )->getTotalCount();

        $this->template->numberOfMessages = $numberOfMessages;
    }

    /**
     * @Actions overview, massPdfGeneration
     */
    protected function createComponentListingsOverview()
    {
        $comp = $this->listingsOverviewFactory
                     ->create(
                         (new ListingsForOverviewQuery())
                         ->withNumberOfWorkedDays()
                         ->withTotalWorkedHours()
                         ->byUser($this->user->getIdentity())
                         ->byPeriod(
                             $this->getParameter('year'),
                             $this->getParameter('month')
                         )
                     );

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
        $this->listingResult = $this->getListingByID($id);
    }

    public function renderEdit($id)
    {
    }

    /**
     * @Actions add, edit
     */
    protected function createComponentListingForm()
    {
        $l = $this->listingResult !== null ?
             $this->listingResult->getListing() :
             null;

        return $this->listingFormControlFactory
                    ->create($l, $this->user->getIdentity());
    }

    /*
     * -----------------------------------
     * ----- REMOVE existing Listing -----
     * -----------------------------------
     */

    public function actionRemove($id)
    {
        $this->listingResult = $this->getListingById($id, true);
    }

    public function renderRemove($id)
    {

    }

    /**
     * @Actions remove
     */
    protected function createComponentListingRemovalForm()
    {
        $comp = $this->listingRemovalControlFactory
                     ->create($this->listingResult);

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
        $this->listingResult = $this->getListingById($id);
    }

    public function renderDetail($id)
    {
        $this->template->listing = $this->listingResult->getListing();
    }

    /**
     * @Actions detail
     */
    protected function createComponentListingItemsTable()
    {
        $comp = $this->listingTableControlFactory->create($this->listingResult);

        return $comp;
    }

    /*
     * ------------------------
     * ----- Copy Listing -----
     * ------------------------
     */

    public function actionCopy($id)
    {
        $this->listingResult = $this->getListingByID($id);
    }

    public function renderCopy($id)
    {
    }

    /**
     * @Actions copy
     */
    protected function createComponentSimpleCopyForm()
    {
        $comp = $this->listingCopyFormControlFactory
                     ->create($this->listingResult->getListing());

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
        $this->listingResult = $this->getListingByID($id, true);

        if ($this->listingResult->getWorkedDays() == 0) {
            $this->flashMessage('Nelze upravovat prázdnou výčetku.', 'warning');
            $this->redirect('Listing:detail', ['id' => $id]);
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
        $comp = $this->massItemChangeControlFactory
                     ->create($this->listingResult);

        return $comp;
    }

    /*
     * ----------------------------
     * ----- Sharing listings -----
     * ----------------------------
     */

    public function actionShare($id)
    {
        $this->listingResult = $this->getListingByID($id, true);

        if ($this->listingResult->getWorkedDays() == 0) {
            $this->flashMessage('Nelze upravovat prázdnou výčetku.', 'warning');
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
        return $this->sharingListingTableControlFactory
                    ->create($this->listingResult);
    }

    /*
     * --------------------------
     * ----- Generating PDF -----
     * --------------------------
     */

    public function actionPdfGeneration($id)
    {
        $this->listingResult = $this->getListingById($id, true);

        if ($this->user->getIdentity()->name !== null) {
            $this['listingPDFGeneration']
                 ['resultPdf']
                 ['userSettings']
                 ['name']->setDefaultValue($this->user->getIdentity()->name);
        }

    }

    public function renderPdfGeneration($id)
    {

    }

    /**
     * @Actions pdfGeneration
     */
    protected function createComponentListingPDFGeneration()
    {
        return $this->listingPDFGenerationControlFactory
                    ->create($this->listingResult);
    }

    /*
     * -------------------------------
     * ----- MASS PDF GENERATION -----
     * -------------------------------
     */

    public function actionAnnualPdfGeneration()
    {

    }
    
    public function renderAnnualPdfGeneration()
    {

    }

    /**
     * @Actions annualPdfGeneration
     */
    protected function createComponentAnnualPDFGeneration()
    {
        $comp = $this->annualPDFGenerationControlFactory
                     ->create($this->user->getIdentity());

        return $comp;
    }

    private function setPeriodParametersForFilter($year, $month)
    {
        if ($year === null) {
            $this->redirect(
                'Listing:overview',
                ['year'  => $this->currentDate->format('Y'),
                 'month' => $this->currentDate->format('n')]
            );
        } else {
            try {
                $this['filter']['form']['year']->setDefaultValue($year);
                $this['filter']['form']['month']->setDefaultValue($month);

            } catch (InvalidArgumentException $e) {
                $this->flashMessage(
                    'Lze vybírat pouze z hodnot, které nabízí formulář.',
                    'warning'
                );
                $this->redirect(
                    'Listing:overview',
                    ['year'=>$this->currentDate->format('Y'),
                     'month'=>$this->currentDate->format('n')]
                );
            }
        }
    }

}