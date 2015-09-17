<?php

namespace App\Model\Components;

use App\Model\Components\ItemsTable\IItemsTableControlFactory;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use App\Model\Facades\ListingFacade;
use App\Model\Entities\WorkedHours;
use Nette\Application\UI\Control;
use App\Model\Entities\Listing;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class MassItemsChangeControl extends Control
{
    /**
     * @var IListingDescriptionControlFactory
     */
    private $listingDescriptionControlFactory;

    /**
     * @var IItemsTableControlFactory
     */
    private $itemsTableControlFactory;

    /**
     * @var ItemUpdateFormFactory
     */
    private $itemUpdateFormFactory;

    /**
     * @var ListingFacade
     */
    private $listingFacade;


    /**
     * @var Listing
     */
    private $listing;

    /**
     * @var ListingItem[]
     */
    private $itemsCollection;


    public function __construct(
        Listing $listing,
        IListingDescriptionControlFactory $listingDescriptionControlFactory,
        IItemsTableControlFactory $itemsTableControlFactory,
        ItemUpdateFormFactory $itemUpdateFormFactory,
        ListingFacade $listingFacade
    ) {
        $this->listing = $listing;

        $this->listingDescriptionControlFactory = $listingDescriptionControlFactory;
        $this->itemsTableControlFactory = $itemsTableControlFactory;
        $this->itemUpdateFormFactory = $itemUpdateFormFactory;
        $this->listingFacade = $listingFacade;
    }

    protected function createComponentListingDescription()
    {
        $comp = $this->listingDescriptionControlFactory->create(
            $this->listing->period,
            $this->listing->description
        );

        $comp->setAsClickable(
            'Front:Listing:detail',
            ['id' => $this->listing->listingID]
        );

        return $comp;
    }

    protected function createComponentItemsTable()
    {
        $comp = $this->itemsTableControlFactory->create($this->listing);
        $comp->showCheckBoxes();

        $comp->showTableCaption(
            $this->listing->description,
            $this->listing->workedDays,
            $this->listing->totalWorkedHours
        );

        return $comp;
    }

    protected function createComponentListingMassItemsChangeForm()
    {
        $form = $this->itemUpdateFormFactory->create();
        $form['otherHours']->setDefaultValue(0);

        unset($form['day'], $form['locality'],
              $form['description'], $form['descOtherHours']);

        $form->addCheckbox('newListing', 'Založit novou výčetku')
                ->setDefaultValue(true);

        $form['save']->caption = 'Změnit položky';
        $form['save']->setAttribute('class', 'ajax');

        $form->onSuccess[] = $this->processMassItemsChange;

        $form->addProtection();

        return $form;
    }

    public function processMassItemsChange(Form $form, $values)
    {
        $selectedItems = $form->getHttpData(Form::DATA_TEXT, 'items[]');

        if (empty($selectedItems)) {
            $this->flashMessage('Označte řádky, které chcete změnit.', 'warning');
            if ($this->presenter->isAjax()) {
                $this->redrawControl('flashMessage');
                return;
            } else {
                $this->redirect('this');
            }
        }

        try {
            $workedHours = new WorkedHours(
                $values['workStart'],
                $values['workEnd'],
                $values['lunch'],
                $values['otherHours']
            );

            $data = $this->listingFacade
                         ->changeItemsInListing(
                             $this->listing,
                             $workedHours,
                             $values['newListing'],
                             $selectedItems
                         );

        } catch (ShiftEndBeforeStartException $s) {
            $form->addError(
                'Nelze skončit směnu dříve, než začala. Zkontrolujte hodnoty
                 v polích Začátek a Konec'
            );
            return;

        } catch (NegativeResultOfTimeCalcException $e) {
            $form->addError(
                'Položku nelze uložit. Musíte mít odpracováno více hodin,
                 než kolik strávíte obědem.'
            );
            return;
        }

        if ($values['newListing'] === true) {
            $this->presenter->redirect(
                'Listing:detail',
                ['id' => $data['listing']->listingID]
            );

        } else {
            if ($this->presenter->isAjax()) {
                $this->listing = $this->listingFacade
                    ->getEntireListingByID($this->listing->listingID);

                $this->itemsCollection = $data['changedItems'];

                $this->flashMessage('Hodnoty byly úspěšně hromadně změneny.', 'success');

                $this->redrawControl('flashMessage');
                $this->redrawControl('formErrors');
                $this['itemsTable']->redrawControl();
            } else {

                $this->redirect('this');
            }
        }
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        if (!isset($this->itemsCollection)) {
            $this->itemsCollection = $this->listing->listingItems;
        }

        $this['itemsTable']->setListingItems($this->itemsCollection);

        $template->defaultWorkedHours = $this->itemUpdateFormFactory
                                             ->getDefaultTimeValue('workedHours');
        $template->form = $this['listingMassItemsChangeForm'];

        $template->render();
    }

}