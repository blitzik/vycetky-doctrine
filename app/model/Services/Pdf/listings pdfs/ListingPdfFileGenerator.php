<?php

namespace App\Model\Pdf\Listing\FileGenerators;

use App\Model\Pdf\ContentGenerators\IPdfContentGenerator;
use App\Model\Pdf\Listing\Caching\IListingPdfCacheFactory;
use App\Model\Pdf\Listing\PdfFiles\IListingPdfFile;
use App\Model\Pdf\Listing\PdfFiles\ListingPdfFile;
use App\Model\Pdf\Listing\PdfSources\IListingPdfSource;
use App\Model\Pdf\PdfFiles\PdfContent;
use Nette\Caching\Cache;
use Nette\Object;
use Nette\Utils\FileSystem;

class ListingPdfFileGenerator extends Object
{
    /** @var IPdfContentGenerator */
    private $pdfContentGenerator;

    /** @var IListingPdfCacheFactory */
    private $pdfCacheFactory;

    /** @var string */
    private $storagePath;



    public function __construct(
        $storagePath,
        IPdfContentGenerator $pdfContentGenerator,
        IListingPdfCacheFactory $pdfCacheFactory
    ) {
        $this->pdfContentGenerator = $pdfContentGenerator;
        $this->pdfCacheFactory = $pdfCacheFactory;
        $this->storagePath = $storagePath;
    }

    // Every Pdf file object is cached to make annual listing pdf download much faster
    // There are 2 things that are being cached:
    //  1st: Pdf File object itself because it contains once generated PDF data
    //  2nd: array that contains absolute paths to every generated real(.pdf) Pdf File for particular Listing
    // One Listing can have many .pdf files according to generation settings
    // Invalidation of cached Listings Pdf Files objects and .pdf files is
    // handled in every action that somehow change the Listing (for example look at ListingFacade events)

    /**
     * @param IListingPdfSource $pdfSource
     * @return IListingPdfFile
     */
    public function generate(IListingPdfSource $pdfSource)
    {
        $cache = $this->pdfCacheFactory
                      ->getCache(
                          $pdfSource->getOwnerId(),
                          $pdfSource->getListingYear()
                      );

        $pdfFileCacheKey = $this->createListingCacheKey(
            $pdfSource->getListingId(),
            $pdfSource->getHashedListingSettings()
        );

        $listingCacheKey = 'listing/' . $pdfSource->getListingId();

        $listingPdfFile = $cache->load($pdfFileCacheKey);
        if ($listingPdfFile === null) {
            $pdfContent = $this->pdfContentGenerator->createPdfContent($pdfSource);

            $listingPdfFile = $this->createPdfFile($pdfContent, $pdfSource);

            $cache->save($pdfFileCacheKey, $listingPdfFile, [Cache::TAGS => [$listingCacheKey]]);
        }

        $generatedPdfFilesCacheKey = 'generatedPdfFilesByListing/' . $listingPdfFile->getListingId();
        $generatedPdfFilesByListings = $cache->load($generatedPdfFilesCacheKey);
        if ($generatedPdfFilesByListings === null) {
            $generatedPdfFilesByListings = [];
        }
        $generatedPdfFilesByListings[$pdfFileCacheKey] = $listingPdfFile->getStoragePath();
        $cache->save($generatedPdfFilesCacheKey, $generatedPdfFilesByListings, [
            Cache::TAGS => [$listingCacheKey]
        ]);


        FileSystem::write($listingPdfFile->getStoragePath(), $listingPdfFile->getPdfContent());

        return $listingPdfFile;
    }



    /**
     * @param $listingId
     * @param $hashedSettings
     * @return string
     */
    private function createListingCacheKey($listingId, $hashedSettings)
    {
        return 'listing/' . $listingId . '/' . $hashedSettings;
    }



    /**
     * @param PdfContent $pdfContent
     * @param IListingPdfSource $pdfSource
     * @return ListingPdfFile
     */
    private function createPdfFile(PdfContent $pdfContent, IListingPdfSource $pdfSource)
    {
        $relativeFilePath = $this->getStoragePath(
            $pdfSource->getPdfTitle(),
            $pdfSource->getOwnerId(),
            $pdfSource->getListingYear(),
            $pdfSource->getHashedListingSettings()
        );

        $absoluteFilePath = $this->storagePath . '/' . $relativeFilePath;

        return new ListingPdfFile(
            $pdfContent,
            $absoluteFilePath,
            $pdfSource->getListingId(),
            $pdfSource->getListingYear(),
            $pdfSource->getOwnerId()
        );
    }



    /**
     * @param string $pdfName
     * @param int $userId
     * @param int $listingYear
     * @param string $hashedSettings
     * @return string
     */
    private function getStoragePath($pdfName, $userId, $listingYear, $hashedSettings)
    {
        return $userId.'/'.$listingYear.'/'.$hashedSettings.'/'.$pdfName.'.pdf';
    }
}