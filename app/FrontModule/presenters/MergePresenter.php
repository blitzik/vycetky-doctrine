<?php

namespace App\FrontModule\Presenters;

use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use App\Model\Components\IListingActionsMenuControlFactory;
use Exceptions\Runtime\ListingNotFoundException;
use App\Model\Entities\Listing;
use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;

class MergePresenter extends SecurityPresenter
{
    use TListing;

    /**
     * @var IListingActionsMenuControlFactory
     * @inject
     */
    public $listingActionsMenuControlFactory;

    /**
     * @var Listing
     */
    private $listing;

    /**
     * @var Listing
     */
    private $listingToMerge;

    /**
     * @var array Listing
     */
    private $listings;

    /**
     * @var array ListingItem
     */
    private $mergedListingsItems;


    protected function createComponentListingActionsMenu()
    {
        $comp = $this->listingActionsMenuControlFactory->create($this->listing);
        return $comp;
    }

    /*
     * ---------------------------
     * ----- SEARCH Listings -----
     * ---------------------------
     */

    public function actionSearch($id)
    {
        $this->listing  = $this->getEntireListingByID($id);

        $listings = $this->listingFacade
                         ->findPartialListingsDataForSelect(
                             $this->listing->year,
                             $this->listing->month
                         );
        unset($listings[$this->listing->listingID]);

        if (empty($listings)) {
            $this->flashMessage(
                'Váš účet neobsahuje další výčetky za ' .
                TimeUtils::getMonthName($this->listing->month) .
                ' ' . $this->listing->year . ' a proto není možné
                využít Vámi požadovanou funkcionalitu.', 'warning'
            );
            $this->redirect('Listing:detail', ['id' => $this->listing->listingID]);
        }

        $this->listings = $listings;
    }

    public function renderSearch($id)
    {

    }

    /**
     * @Actions search
     */
    protected function createComponentListingsSelector()
    {
        $form = new Form();

        $form->addSelect('listingsList', null, $this->listings)
                ->setPrompt('Vyberte výčetku')
                ->setRequired('Vyberte výčetku pro spojení.');

        $form->addSubmit('send', 'Vybrat výčetku');

        $form->onSuccess[] = [$this, 'proccessListingSelection'];

        return $form;
    }

    public function proccessListingSelection(Form $form, $values)
    {
        $this->redirect(
            'Merge:listing',
            ['id' => $this->listing->listingID,
             'with' => $values['listingsList']]
        );
    }

    /*
     * ----------------------------
     * ----- Listings merging -----
     * ----------------------------
     */

    public function actionListing($id, $with)
    {
        $this->listing = $this->getEntireListingByID($id);
        if (!isset($with)) {
            $this->redirect(
                'Merge:search',
                ['id' => $this->listing->listingID]
            );
        }

        $listingToMergeID = intval($with);

        if ($listingToMergeID == $this->listing->listingID) {
            $this->flashMessage('Nelze spojit výčetku se sebou samou.', 'warning');
            $this->redirect('this', ['with' => null]);
        }

        try {
            $this->listingToMerge = $this->listingFacade->getListingByID($listingToMergeID);
            if (!$this->listingFacade->haveListingsSamePeriod($this->listing, $this->listingToMerge)) {
                $this->flashMessage(
                    'Lze spojit pouze výčetky se stejným obdobím.',
                    'warning'
                );
                $this->redirect('Merge:search', ['id' => $this->listing->listingID]);
            }

        } catch (ListingNotFoundException $e) {
            $this->flashMessage(
                'Výčetka, kterou se snažíte vybrat nebyla nalezena.',
                'warning'
            );
            $this->redirect('Merge:search', ['id' => $this->listing->listingID]);

        }

        $this->mergedListingsItems = $this->listingFacade
                                          ->getMergedListingsItemsForEntireTable(
                                              $this->listing,
                                              $this->listingToMerge
                                          );
    }

    public function renderListing($id, $with)
    {
        $this->template->baseListing = $this->listing;
        if (isset($this->listingToMerge)) {
            $this->template->listingToMerge = $this->listingToMerge;

            $this->template
                 ->mergedListingsItems = $this->mergedListingsItems;
        }
    }

    /**
     * @Actions listing
     */
    protected function createComponentListingsMergeForm()
    {
        $form = new Form();

        $form->addSubmit('merge', 'Spojit výčetky');

        $form->onSuccess[] = [$this, 'processMergeListings'];

        $form->getElementPrototype()->class = 'clear-element';

        return $form;
    }

    public function processMergeListings(Form $form, $values)
    {
        $selectedCollisionItems = $form->getHttpData(Form::DATA_TEXT, 'itm[]');

        try {
            $this->listingFacade->mergeListings(
                $this->listing,
                $this->listingToMerge,
                $selectedCollisionItems
            );

            $this->flashMessage('Výčetky byli úspěšně spojeny.', 'success');
            $this->redirect(
                'Listing:overview',
                ['year'  => $this->listing->year,
                 'month' => $this->listing->month]
            );

        } catch (NoCollisionListingItemSelectedException $ncis) {
            $form->addError('Ve výčetce se stále nachází kolizní řádek/řádky.');
            return;

        } catch (\DibiException $e) {
            $this->flashMessage(
                'Při spojování výčetek došlo k chybě. Zkuste akci opakovat později.',
                'error'
            );
            $this->redirect('this', ['with' => null]);
        }
    }
}