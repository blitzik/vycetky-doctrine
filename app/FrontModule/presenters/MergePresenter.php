<?php

namespace App\FrontModule\Presenters;

use App\Model\ResultObjects\ListingResult;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use App\Model\Components\IListingActionsMenuControlFactory;
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

    /** @var ListingResult */
    private $listingToMergeResult;

    /** @var array Listing */
    private $listings;


    protected function createComponentListingActionsMenu()
    {
        $comp = $this->listingActionsMenuControlFactory
                     ->create($this->listingResult->getListing());
        return $comp;
    }


    /*
     * ---------------------------
     * ----- SEARCH Listings -----
     * ---------------------------
     */

    public function actionSearch($id)
    {
        $this->listingResult  = $this->getListingByID($id);
        $listing = $this->listingResult->getListing();

        $listings = $this->listingsFacade
                         ->findListingsToMerge(
                             $listing->getUser(),
                             $listing->getYear(),
                             $listing->getMonth()
                         );

        unset($listings[$listing->getId()]);

        if (empty($listings)) {
            $this->flashMessage(
                'Váš účet neobsahuje další výčetky za ' .
                TimeUtils::getMonthName($listing->getMonth()) .
                ' ' . $listing->getYear() . ' a proto není možné
                využít Vámi požadovanou funkcionalitu.', 'warning'
            );
            $this->redirect('Listing:detail', ['id' => $listing->getId()]);
        }

        $this->listings = $this->prepareListingsForSearchSelect($listings);
    }


    public function renderSearch($id)
    {

    }


    private function prepareListingsForSearchSelect(array $listings)
    {
        $result = [];
        foreach ($listings as $id => $description) {
            $desc = $description ?: 'Bez popisu';
            $result[$id] = '#'.$id. ' - ' .$desc;
        }

        return $result;
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

        $form->onSuccess[] = [$this, 'processListingSelection'];

        return $form;
    }


    public function processListingSelection(Form $form, $values)
    {
        $this->redirect(
            'Merge:listing',
            ['id' => $this->listingResult->getListingId(),
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
        $this->listingResult = $this->getListingByID($id);
        if (!isset($with)) {
            $this->redirect(
                'Merge:search',
                ['id' => $this->listingResult->getListingId()]
            );
        }

        $listingToMergeID = intval($with);

        if ($listingToMergeID == $this->listingResult->getListingId()) {
            $this->flashMessage('Nelze spojit výčetku se sebou samou.', 'warning');
            $this->redirect('Listing:detail', ['id' => $this->listingResult->getListingId()]);
        }

        $this->listingToMergeResult = $this->getListingByID($listingToMergeID);
        if (!$this->listingsFacade
                  ->haveListingsSamePeriod(
                      $this->listingResult->getListing(),
                      $this->listingToMergeResult->getListing())
        ) {
            $this->flashMessage(
                'Lze spojit pouze výčetky se stejným obdobím.',
                'warning'
            );
            $this->redirect('Merge:search', ['id' => $this->listingResult->getListingId()]);
        }
    }


    public function renderListing($id, $with)
    {
        $this->template->baseListing = $this->listingResult->getListing();
        if (isset($this->listingToMergeResult)) {
            $this->template->listingToMerge = $this->listingToMergeResult->getListing();

            $this->template
                 ->mergedListingsItems = $this->listingsFacade
                                              ->getMergedListingsItemsForEntireTable(
                                                  $this->listingResult->getListing(),
                                                  $this->listingToMergeResult->getListing()
                                              );
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
            $listing = $this->listingsFacade->mergeListings(
                $this->listingResult->getListing(),
                $this->listingToMergeResult->getListing(),
                $selectedCollisionItems,
                $this->listingResult->getListing()->getUser()
            );

            $this->flashMessage('Výčetky byli úspěšně spojeny.', 'success');
            $this->redirect(
                'Listing:overview',
                ['year'  => $listing->year,
                 'month' => $listing->month]
            );

        } catch (NoCollisionListingItemSelectedException $ncis) {
            $form->addError('Ve výčetce se stále nachází kolizní řádek/řádky.');
            return;

        } catch (DBALException $e) {
            $this->flashMessage(
                'Při spojování výčetek došlo k chybě. Zkuste akci opakovat později.',
                'error'
            );
            $this->redirect('Listing:detail', ['id' => $this->listingResult->getListingId()]);
        }
    }
}