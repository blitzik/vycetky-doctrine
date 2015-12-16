<?php

namespace App\Model\Pdf\Listing\Caching;

use Nette\Caching\Cache;

interface IListingPdfCacheFactory
{
    /**
     * @param $userId
     * @param $listingYear
     * @return Cache
     */
    public function getCache($userId, $listingYear);
}