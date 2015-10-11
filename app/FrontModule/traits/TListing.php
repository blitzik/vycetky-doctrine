<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IListingDescriptionControlFactory;
use App\Model\Facades\ListingsFacade;
use App\Model\ResultObjects\ListingResult;

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

    /**
     * @var ListingResult
     */
    private $listingResult;

    /**
     * @param $listingID
     * @param $withTime
     * @return ListingResult
     */
    private function getListingByID($listingID, $withTime = false)
    {
        $result = $this->listingsFacade->getListingByID($listingID, $withTime);
        if ($result === null or
            !$this->authorizator->isAllowed($this->user->getIdentity(), $result->getListing())) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'warning');
            $this->redirect('Listing:overview');
        }

        return $result;
    }

    protected function createComponentListingDescription()
    {
        $desc = $this->listingDescriptionFactory
                     ->create($this->listingResult->getListing());

        $desc->setAsClickable(
            'Front:Listing:detail',
            ['id' => $this->listingResult->getListingId()]
        );

        return $desc;
    }
}