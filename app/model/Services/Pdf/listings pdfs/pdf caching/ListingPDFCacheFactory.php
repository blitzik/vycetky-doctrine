<?php

namespace App\Model\Pdf\Listing\Caching;

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\Object;
use Nette\Utils\FileSystem;

class ListingPDFCacheFactory extends Object implements IListingPdfCacheFactory
{
    /** @var Cache[] */
    private $caches;

    /** @var string */
    private $storagePath;

    public function __construct($storagePath)
    {
        $this->storagePath = $storagePath;
    }



    /**
     * @param string $userId
     * @param $listingYear
     * @return Cache
     */
    public function getCache($userId, $listingYear)
    {
        $cacheStoragePath = $this->storagePath . '/' . $userId . '/' . $listingYear;
        $key = md5($cacheStoragePath);
        if (!isset($this->caches[$key])) {
            $this->caches[$key] = $this->create($cacheStoragePath);
        }

        return $this->caches[$key];
    }



    /**
     * @param string $cacheStoragePath
     * @return Cache
     */
    protected function create($cacheStoragePath)
    {
        if (!file_exists($cacheStoragePath)) {
            FileSystem::createDir($cacheStoragePath);
        }

        $journal = new SQLiteJournal($this->storagePath . '/cached-pdfs-journal');
        $fileStorage = new FileStorage($cacheStoragePath, $journal);
        return new Cache($fileStorage, 'cached-pdfs');
    }
}