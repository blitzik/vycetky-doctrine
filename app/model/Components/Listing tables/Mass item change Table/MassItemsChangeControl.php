<?php

namespace App\Model\Components;

use App\Model\Components\ItemsTable\IItemsTableControlFactory;
use App\Model\Domain\Entities\ListingItem;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use App\Model\Facades\ListingsFacade;
use App\Model\Domain\Entities\WorkedHours;
use Nette\Application\UI\Control;
use App\Model\Domain\Entities\Listing;
use Nette\Application\UI\Form;

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
     * @var ListingsFacade
     */
    private $listingsFacade;


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
        ListingsFacade $listingsFacade
    ) {
        $this->listing = $listing;

        $this->listingDescriptionControlFactory = $listingDescriptionControlFactory;
        $this->itemsTableControlFactory = $itemsTableControlFactory;
        $this->itemUpdateFormFactory = $itemUpdateFormFactory;
        $this->listingsFacade = $listingsFacade;
    }

    protected function createComponentListingDescription()
    {
        $comp = $this->listingDescriptionControlFactory
                     ->create($this->listing);

        $comp->setAsClickable(
            'Front:Listing:detail',
            ['id' => $this->listing->getId()]
        );

        return $comp;
    }

    protected function createComponentItemsTable()
    {
        $comp = $this->itemsTableControlFactory->create($this->listing);

        $comp->showCheckBoxes();
        $comp->showTableCaption();

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

        $form->onSuccess[] = $this->processMassItemsChange;

        $form->addProtection();

        $form->getElementPrototype()->class = 'ajax';

        return $form;
    }

    public function processMassItemsChange(Form $form, $values)
    {
        $daysToChange = $form->getHttpData(Form::DATA_TEXT, 'items[]');

        if (empty($daysToChange)) {
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

            if ($values['newListing'] === true) {
                $newListing = $this->listingsFacade
                                   ->baseListingOn(
                                       $this->listing,
                                       $workedHours,
                                       $daysToChange
                                   );
            } else {
                $changedItems = $this->listingsFacade
                                    ->changeItems(
                                        $this->listing,
                                        $workedHours,
                                        $daysToChange
                                    );
            }

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
                'Listing:overview',
                ['year' => $newListing->getYear(), 'month' => $newListing->getMonth()]
            );

        } else {
            if ($this->presenter->isAjax()) {
                $this->flashMessage('Hodnoty byly úspěšně hromadně změneny.', 'success');

                $this->redrawControl('flashMessage');
                $this->redrawControl('formErrors');

                $this['itemsTable']->refreshTable($changedItems);
            } else {
                $this->redirect('this');
            }
        }
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $template->defaultWorkedHours = $this->itemUpdateFormFactory
                                             ->getDefaultTimeValue('workedHours');

        $template->form = $this['listingMassItemsChangeForm'];

        $template->render();
    }

}