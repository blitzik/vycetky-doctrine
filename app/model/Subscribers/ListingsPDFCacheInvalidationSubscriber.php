<?php

namespace App\Model\Subscribers;

use App\Model\Domain\Entities\Listing;
use App\Model\Pdf\Listing\Caching\IListingPdfCacheFactory;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Object;
use Nette\Utils\FileSystem;

class ListingsPDFsFileInvalidationSubscriber extends Object implements Subscriber
{
    /** @var IListingPDFCacheFactory */
    private $cacheFactory;

    public function __construct(
        IListingPdfCacheFactory $cacheFactory
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
                      ->getCache(
                          $listing->user->id,
                          $listing->year
                      );

        // removal of all generated pdf files of given Listing
        $generatedFiles = $cache->load('generatedPdfFilesByListing/' . $listing->getId());
        if ($generatedFiles !== null) {
            foreach ($generatedFiles as $key => $filePath) {
                if (!is_dir($filePath) and file_exists($filePath)) {
                    FileSystem::delete($filePath);
                }
            }
        }

        $cache->clean([Cache::TAGS => 'listing/' . $listing->id]);
    }

}