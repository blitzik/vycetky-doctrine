<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Facades\ItemsFacade;
use App\Model\Facades\LocalitiesFacade;
use App\Model\Time\TimeUtils;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;

class ItemFormControl extends BaseComponent
{
    /** @var array */
    public $onSuccessItemPersist = [];

    /** @var IListingDescriptionControlFactory  */
    private $listingDescriptionControlFactory;

    /** @var ItemUpdateFormFactory  */
    private $itemUpdateFormFactory;

    /** @var LocalitiesFacade  */
    private $localitiesFacade;

    /** @var EntityManager */
    private $entityManager;

    /** @var ItemsFacade  */
    private $itemsFacade;

    /** @var ListingItem */
    private $listingItem;

    /** @var Listing */
    private $listing;

    /** @var int */
    private $day;

    public function __construct(
        Listing $listing,
        $day,
        ItemsFacade $itemsFacade,
        LocalitiesFacade $localitiesFacade,
        ItemUpdateFormFactory $itemUpdateFormFactory,
        IListingDescriptionControlFactory $listingDescriptionControlFactorySelect,
        EntityManager $entityManager
    ) {
        $this->listing = $listing;
        $this->day = $day;

        $this->itemsFacade = $itemsFacade;
        $this->localitiesFacade = $localitiesFacade;
        $this->itemUpdateFormFactory = $itemUpdateFormFactory;
        $this->listingDescriptionControlFactory = $listingDescriptionControlFactorySelect;
        $this->entityManager = $entityManager;
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

    protected function createComponentItemForm()
    {
        $form = $this->itemUpdateFormFactory->create($this->listingItem);

        $form->onSuccess[] = [$this, 'processSaveItem'];

        return $form;
    }

    /**
     * @param Listing $listing
     * @param int $day
     * @return bool|\DateTime
     */
    private function prepareDateTime(Listing $listing, $day)
    {
        return TimeUtils::getDateTimeFromParameters(
            $listing->year,
            $listing->month,
            $day
        );
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->listing = $this->listing;

        $date = $this->prepareDateTime($this->listing, $this->day);
        if ($this->day === null or $date === false) {
            $template->setFile(__DIR__ . '/wrongDay.latte');
            $template->render();
            return;
        }
        $template->itemDate = $date;

        if ($this->listingItem === null) {
            $this->listingItem = $this->itemsFacade->getByDay($this->day, $this->listing);
        }

        $template->_form = $this['itemForm'];

        $workedHours = null;
        if ($this->listingItem instanceof ListingItem) {
            $workedHours = $this->listingItem->workedHours->getHours();
        }

        $template->workedHours = new \InvoiceTime(
                                    isset($workedHours)
                                    ? $workedHours
                                    : $this->itemUpdateFormFactory
                                           ->getDefaultTimeValue('workedHours')
                                 );

        $template->render();
    }

    public function handleSearchLocality()
    {
        $term = $this->presenter->getParameter('term'); // todo

        if ($term and mb_strlen($term) >= 3) {
            $this->presenter->sendResponse(
                new JsonResponse(
                    $this->localitiesFacade
                        ->findLocalitiesForAutocomplete(
                            $term,
                            $this->listing,
                            10
                        )
                )
            );
        }
    }

    public function processSaveItem(Form $form, $values)
    {
        $values['day'] = $this->day;
        $values['listing'] = $this->listing;
        $values['user'] = $this->listing->getUser();

        $this->listingItem = $this->itemsFacade->getByDay($this->day, $this->listing);

        try{
            $this->listingItem = $this->itemsFacade
                                      ->saveListingItem(
                                          (array)$values,
                                          $this->listingItem
                                      );

        } catch (OtherHoursZeroTimeException $zt) {
            $form->addError(ItemUpdateFormFactory::OTHER_HOURS_ZERO_TIME_ERROR_MSG);
            return;


        } catch (NegativeResultOfTimeCalcException $b) {
            $form->addError(
                'Položku nelze uložit. Musíte mít odpracováno více hodin,
                 než kolik strávíte obědem.'
            );
            return;

        } catch (ShiftEndBeforeStartException $c) {
            $form->addError(
                'Nelze skončit směnu dřív než začne. Zkontrolujte si začátek
                 a konec směny.'
            );
            return;

        } catch (ListingItemDayAlreadyExistsException $d) {
            $form->addError(
                'Položku nelze uložit, protože výčetka již obsahuje záznam
                 z tohoto dne.'
            );
            return;

        } catch (DBALException $e) {
            $form->addError('Položka nebyla uložena. Zkuste akci opakovat později.');
            return;
        }

        $this->onSuccessItemPersist($this->listingItem);
    }
}