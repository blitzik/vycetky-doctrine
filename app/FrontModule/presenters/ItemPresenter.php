<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IItemFormControlFactory;
use App\Model\Time\TimeUtils;
use \App\Model\Domain\Entities;

class ItemPresenter extends SecurityPresenter
{
    use TListing;

    /**
     * @var IItemFormControlFactory
     * @inject
     */
    public $itemFormFactory;

    /** @var  Entities\Listing */
    private $listing;

    /*
     * ------------------
     * ----- UPDATE -----
     * ------------------
     */

    public function actionEdit($id, $day)
    {
        $this->listingResult = $this->getListingByID($id);
        $this->listing = $this->listingResult->getListing();

        $date = TimeUtils::getDateTimeFromParameters(
            $this->listing->year,
            $this->listing->month,
            $day
        );

        if ($date === false) {
            $this->redirect('Listing:detail', ['id' => $this->listing->getId()]);
        }
    }

    public function renderEdit($id, $day)
    {

    }

    /**
     * @Actions edit
     */
    protected function createComponentItemForm()
    {
        $form = $this->itemFormFactory
                     ->create(
                         $this->listingResult->getListing(),
                         $this->getParameter('day')
                     );

        $form->onSuccessItemPersist[] = [$this, 'onSuccessItemPersist'];

        return $form;
    }

    public function onSuccessItemPersist(Entities\ListingItem $listingItem)
    {
        $this->flashMessage('Položka byla uložena.', 'success');
        $this->redirect(
            'Listing:detail#' . $listingItem->day,
            ['id' => $listingItem->getListing()->getId()]
        );
    }
}