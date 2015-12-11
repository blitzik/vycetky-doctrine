<?php

namespace App\Model\Subscribers;

use App\Model\Domain\Entities\Listing;
use App\Model\Services\Pdf\ListingPDFCacheFactory;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Object;

class ListingsPDFCacheInvalidationSubscriber extends Object implements Subscriber
{
    /** @var ListingPDFCacheFactory */
    private $cacheFactory;

    public function __construct(
        ListingPDFCacheFactory $cacheFactory
    ) {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'App\Model\Facades\ListingsFacade::onListingChange',
            'App\Model\Facades\ItemsFacade::onItemChange'
        ];
    }

    public function onListingChange(Listing $listing)
    {
        $this->handleInvalidation($listing);
    }

    public function onItemChange(Listing $listing)
    {
        $this->handleInvalidation($listing);
    }

    private function handleInvalidation(Listing $listing)
    {
        $cache = $this->cacheFactory
                      ->getCache([
                          'l_year' => $listing->year,
                          'u_id' => $listing->user->id
                      ]);

        $cache->clean([Cache::TAGS => 'listing/' . $listing->id]);
    }

}