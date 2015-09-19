<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\ItemUpdateFormFactory;
use App\Model\Query\ListingItemsQuery;
use App\Model\Query\ListingsQuery;
use Nette\Application\Responses\JsonResponse;
use App\Model\Facades\LocalitiesFacade;
use App\Model\Facades\ItemsFacade;
use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;
use \App\Model\Domain\Entities;
use \Exceptions\Runtime;

class ItemPresenter extends SecurityPresenter
{
    use TListing;

    /**
     * @var ItemUpdateFormFactory
     * @inject
     */
    public $itemUpdateFormFactory;

    /**
     * @var LocalitiesFacade
     * @inject
     */
    public $localitiesFacade;

    /**
     * @var ItemsFacade
     * @inject
     */
    public $itemsFacade;

    /**
     * @var Entities\ListingItem
     */
    private $listingItem;

    /**
     *
     * @var Entities\Listing
     */
    private $listing;


    /**
     * @var \DateTime
     */
    private $date;

    /*
     * ------------------
     * ----- UPDATE -----
     * ------------------
     */

    public function actionEdit($id, $day)
    {
        try {
            $this->listing = $this->listingsFacade->fetchListing(
                (new ListingsQuery())->byId($id)->byUser($this->user->getIdentity())
            )['listing'];

            $this->date = TimeUtils::getDateTimeFromParameters(
                $this->listing->year,
                $this->listing->month,
                $day
            );
            if ($this->date === false)
                $this->redirect('Listing:detail', ['id' => $this->listing->getId()]);

            $this->listingItem = $this->itemsFacade->fetchListingItem(
                (new ListingItemsQuery())->byListing($this->listing)->byDay($day)
            );

        } catch (Runtime\ListingNotFoundException $l) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'error');
            $this->redirect(
                'Listing:overview',
                ['year'  => $this->currentDate->format('Y'),
                 'month' => $this->currentDate->format('n')]
            );

        } catch (Runtime\ListingItemNotFoundException $li) {

            $this->listingItem = null;
        }

        if ($this->listingItem instanceof Entities\ListingItem) {

            $formData['lunch'] = $this->listingItem
                                      ->workedHours
                                      ->lunch->toTimeWithComma();

            $formData['workEnd'] = $this->listingItem
                                        ->workedHours
                                        ->workEnd->toHoursAndMinutes(true);

            $formData['workStart'] = $this->listingItem
                                          ->workedHours
                                          ->workStart->toHoursAndMinutes(true);

            $formData['otherHours'] = $this->listingItem
                                           ->workedHours
                                           ->otherHours->toTimeWithComma();

            $formData['locality'] = $this->listingItem->locality->name;

            $formData['description'] = $this->listingItem->description;

            $formData['descOtherHours'] = $this->listingItem->descOtherHours;

            $this['itemForm']->setDefaults($formData);
        }
    }

    public function renderEdit($id, $day)
    {
        $this->template->_form = $this['itemForm'];

        $workedHours = null;
        if ($this->listingItem instanceof Entities\ListingItem) {
            $workedHours = $this->listingItem->workedHours->getHours();
        }

        $this->template->itemDate = $this->date;
        $this->template->listing = $this->listing;
        $this->template->workedHours = $workedHours;
        $this->template->defaultWorkedHours = $this->itemUpdateFormFactory
                                                   ->getDefaultTimeValue('workedHours');
    }

    public function handleSearchLocality($term)
    {
        if ($term and mb_strlen($term) >= 3) {
            $this->sendResponse(
                new JsonResponse($this->localitiesFacade
                                      ->findLocalitiesForAutocomplete($term, 10, $this->user->getIdentity()))
            );
        }
    }

    /**
     * @Actions edit
     */
    protected function createComponentItemForm()
    {
        $form = $this->itemUpdateFormFactory->create();

        $form->onSuccess[] = [$this, 'processSaveItem'];

        return $form;
    }

    public function processSaveItem(Form $form, $values)
    {
        $values['day'] = $this->date->format('j');
        $values['listing'] = $this->listing;

        try{
            $listingItem = $this->itemsFacade
                                ->saveListingItem((array)$values, $this->listingItem);

        } catch (Runtime\OtherHoursZeroTimeException $zt) {
            $form->addError(ItemUpdateFormFactory::OTHER_HOURS_ZERO_TIME_ERROR_MSG);
            return;


        } catch (Runtime\NegativeResultOfTimeCalcException $b) {
            $form->addError(
                'Položku nelze uložit. Musíte mít odpracováno více hodin,
                 než kolik strávíte obědem.'
            );
            return;

        } catch (Runtime\ShiftEndBeforeStartException $c) {
            $form->addError(
                'Nelze skončit směnu dřív než začne. Zkontrolujte si začátek
                 a konec směny.'
            );
            return;

        } catch (Runtime\ListingItemDayAlreadyExistsException $d) {
            $form->addError(
                'Položku nelze uložit, protože výčetka již obsahuje záznam
                 z tohoto dne.'
            );
            return;

        } catch (\Exception $e) {
            $form->addError('Položka nebyla uložena. Zkuste akci opakovat později.');
            return;
        }

        $this->flashMessage('Položka byla uložena.', 'success');
        $this->redirect(
            'Listing:detail#' . $listingItem->day,
            ['id' => $this->listing->getId()]
        );
    }

}