<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IListingDescriptionControlFactory;
use App\Model\Query\ListingsQuery;
use Exceptions\Runtime\ListingNotFoundException;
use App\Model\Facades\ListingsFacade;
use Nette\InvalidArgumentException;

trait TListing
{
    /**
     * @var ListingsFacade
     * @inject
     */
    public $listingsFacade;

    /**
     * @var IListingDescriptionControlFactory
     * @inject
     */
    public $listingDescriptionFactory;

    private function getListingByID($listingID)
    {
        try {
            return $this->listingsFacade->fetchListing(
                (new ListingsQuery())
                ->byId($listingID)
                ->byUser($this->user->getIdentity())
            )['listing'];

        } catch (ListingNotFoundException $e) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'error');
            $this->redirect('Listing:overview');
        }

    }

    protected function createComponentListingDescription()
    {
        $desc = $this->listingDescriptionFactory
                     ->create($this->listing);

        $desc->setAsClickable(
            'Front:Listing:detail',
            ['id' => $this->listing->id]
        );

        return $desc;
    }

    public function setPeriodParametersForFilter($year, $month)
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