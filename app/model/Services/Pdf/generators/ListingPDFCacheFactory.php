<?php

namespace App\Model\Services\Pdf;

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\Object;
use Nette\Utils\FileSystem;

class ListingPDFCacheFactory extends Object
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
     * @param array $listing
     * @return Cache
     */
    public function getCache(array $listing)
    {
        $cacheStoragePath = $this->storagePath . '/' . $listing['u_id'] . '/' . $listing['l_year'] . '/';

        $key = md5($cacheStoragePath);
        if (!isset($this->caches[$key])) {
            $this->caches[$key] = $this->create($cacheStoragePath);
        }

        return $this->caches[$key];
    }

    /**
     * @param $cacheStoragePath
     * @return Cache
     */
    private function create($cacheStoragePath)
    {
        if (!file_exists($cacheStoragePath)) {
            FileSystem::createDir($cacheStoragePath);
        }

        $journal = new SQLiteJournal('cached-pdfs');
        $fileStorage = new FileStorage($cacheStoragePath, $journal);
        return new Cache($fileStorage, 'cached-pdfs');
    }
}