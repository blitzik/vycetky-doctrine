<?php

namespace App\Model\Components\ItemsTable;

use App\Model\Components\IListingDescriptionControlFactory;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\FillingItem;
use App\Model\Domain\IDisplayableItem;
use App\Model\Facades\ListingsFacade;
use App\Model\ResultObjects\ListingResult;
use Exceptions\Logic\InvalidArgumentException;
use Nette\Application\UI\Control;
use App\Model\Facades\ItemsFacade;
use App\Model\Domain\Entities\Listing;

class ItemsTableControl extends Control
{
    /**
     * @var IListingDescriptionControlFactory
     */
    private $listingDescriptionControlFactory;

    /**
     * @var ListingsFacade
     */
    private $listingsFacade;

    /**
     * @var ItemsFacade
     */
    private $itemFacade;

    /**
     * @var ListingResult
     */
    private $listingResult;

    /**
     * @var Listing
     */
    private $listing;

    /**
     * @var ListingItem[]
     */
    private $items = array();


    /* *** OPTIONS ** */

    private $showActions;
    private $parameters = array();

    private $showCheckBoxes = false;
    private $isTableCaptionVisible = false;

    private $workedDays;
    private $totalWorkedHours;

    public function __construct(
        ListingResult $listingResult,
        IListingDescriptionControlFactory $listingDescriptionControlFactory,
        ListingsFacade $listingsFacade,
        ItemsFacade $itemFacade
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

        $this->listingDescriptionControlFactory = $listingDescriptionControlFactory;
        $this->listingsFacade = $listingsFacade;
        $this->itemFacade = $itemFacade;
    }

    /**
     * @param ListingItem[]|IDisplayableItem[] $listingItems
     */
    public function refreshTable(array $listingItems)
    {
        foreach ($listingItems as $listingItem) {
            // Items must be from same Listing for which has been defined this component
            if (!$listingItem instanceof FillingItem and
                $listingItem->getListing()->getId() !== $this->listing->getId()) {
                throw new InvalidArgumentException(
                    'Some members of collection does NOT belong to Listing'
                );
            }
        }

        $this->items = $listingItems;
        $this->redrawControl();
    }

    protected function createComponentDescription()
    {
        $comp = $this->listingDescriptionControlFactory
                     ->create($this->listing);

        return $comp;
    }

    public function showTableCaption(
        $destination = null,
        array $params = []
    ) {
        if ($destination !== null) {
            $this['description']->setAsClickable($destination, $params);
        }

        $listingData = $this->listingsFacade
                            ->getWorkedDaysAndTime($this->listing->getId());

        $this->workedDays = $listingData['worked_days'];
        $this->totalWorkedHours = new \InvoiceTime((int)$listingData['total_worked_hours_in_sec']);

        $this->isTableCaptionVisible = true;
    }

    public function showActions($path, array $parameters = null)
    {
        $this->parameters = $parameters;
        $this->showActions = $path;
    }

    public function showCheckBoxes()
    {
        $this->showCheckBoxes = true;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/table.latte');

        if (!$this->presenter->isAjax()) {
            $this->items = $this->itemFacade
                                ->generateEntireTable($this->listing);
        } else {
            $this->items = $this->itemFacade->convert2DisplayableItems(
                $this->items
            );
        }

        $template->itemsCollection = $this->items;

        $template->workedDays = $this->workedDays;
        $template->totalWorkedHours = $this->totalWorkedHours;

        $template->isTableCaptionVisible = $this->isTableCaptionVisible;
        $template->showCheckBoxes = $this->showCheckBoxes;
        $template->showActions = $this->showActions;
        $template->parameters = $this->parameters;
        $template->listing = $this->listing;

        $template->numberOfDaysInMonth = $this->listing->getNumberOfDaysInMonth();

        $template->render();
    }

}