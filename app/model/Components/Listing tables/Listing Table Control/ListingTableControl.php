<?php

namespace App\Model\Components\ListingTable;

use App\Model\Components\BaseComponent;
use App\Model\Components\ItemsTable\IItemsTableControlFactory;
use App\Model\Domain\Entities\Listing;
use App\Model\ResultObjects\ListingResult;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\ListingNotFoundException;
use Exceptions\Runtime\ShiftItemDownException;
use App\Model\Facades\ListingsFacade;
use App\Model\Facades\ItemsFacade;
use App\Model\Domain\FillingItem;
use Nette\Utils\DateTime;

class ListingTableControl extends BaseComponent
{
    /** @var IItemsTableControlFactory  */
    private $itemsTableControlFactory;

    /** @var ListingsFacade  */
    private $listingFacade;

    /** @var ItemsFacade  */
    private $itemFacade;

    /** @var ListingResult */
    private $listingResult;

    /** @var Listing */
    private $listing;


    public function __construct(
        ListingResult $listingResult,
        IItemsTableControlFactory $itemsTableControlFactory,
        ListingsFacade $listingFacade,
        ItemsFacade $itemFacade
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

        $this->itemsTableControlFactory = $itemsTableControlFactory;
        $this->listingFacade = $listingFacade;
        $this->itemFacade = $itemFacade;

    }

    protected function createComponentItemsTable()
    {
        $comp = $this->itemsTableControlFactory->create($this->listingResult);

        $comp->showActions(
            __DIR__ . '/templates/actions.latte',
            ['listingID' => $this->listing->getId()]
        );

        $comp->showTableCaption();

        return $comp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $template->render();
    }

    private function checkDayValue($day)
    {
        $noDays = $this->listing->getNumberOfDaysInMonth();
        if (!is_numeric($day) or !($day >= 1 and $day <= $noDays)) {
            $this->redirect('this');
        }
    }

    /**
     * @secured
     */
    public function handleRemoveItem($day)
    {
        $this->checkDayValue($day);

        try {
            $this->itemFacade->removeListingItem($day, $this->listing);

            if ($this->presenter->isAjax()) {
                $item = new FillingItem(
                    DateTime::createFromFormat(
                        'd.m.Y',
                        $day.'.'.$this->listing->getMonth().'.'.$this->listing->getYear()
                        )
                );

                $this['itemsTable']->refreshTable([$item]);
            } else {
                $this->flashMessage('Řádek byl vymazán.', 'success');
                $this->redirect('this');
            }

        } catch (ListingNotFoundException $lnf) {
            $this->flashMessage('Výčetka, kterou se snažíte upravit, nebyla nalezena.');
            $this->redirect('Listing:overview');
        }
    }

    /**
     * @secured
     */
    public function handleCopyItem($day)
    {
        $this->checkDayValue($day);

        $err = 0;
        try {
            $newListingItem = $this->itemFacade
                                   ->copyListingItemDown(
                                       $day,
                                       $this->listing
                                   );

        }  catch (ShiftItemDownException $sd) {
            $this->presenter->flashMessage(
                'Nelze vytvořit kopii poslední položky ve výčetce.',
                'error'
            );
            $err++;

        } catch (DBALException $e) {
            $this->presenter->flashMessage(
                'Kopie položky nemohla být založena.
                 Zkuste akci opakovat později.',
                'error'
            );
            $err++;
        }

        if ($err !== 0) {
            if ($this->presenter->isAjax()) {
                $this->presenter->redrawControl('flashMessages');
            } else {
                $this->redirect('this');
            }
        }

        if ($this->presenter->isAjax()) {
            $this['itemsTable']->refreshTable([$newListingItem]);
        } else {
            $this->flashMessage('Řádek byl zkopírován.', 'success');
            $this->redirect('this#' . $newListingItem->getDay());
        }
    }

}