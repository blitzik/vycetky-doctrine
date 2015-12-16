<?php

namespace App\Model\Pdf\Listing\Generators;

use App\Model\Pdf\Listing\PdfSources\IListingPdfSource;
use App\Model\Pdf\Listing\PdfFiles\IListingPdfFile;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Readers\ListingsReader;
use App\Model\Domain\Entities\User;
use Nette\Utils\Arrays;
use Nette\Object;
use Nette\Utils\FileSystem;

class AnnualPdfGenerator extends Object
{
    /** @var ListingPdfGenerator */
    private $listingPdfGenerator;

    /** @var ListingItemsReader */
    private $listingItemsReader;

    /** @var ListingsReader */
    private $listingsReader;

    /** @var string */
    private $storagePath;



    public function __construct(
        $storagePath,
        ListingPdfGenerator $listingPdfGenerator,
        ListingItemsReader $listingItemsReader,
        ListingsReader $listingsReader
    ) {
        $this->storagePath = $storagePath;
        $this->listingPdfGenerator = $listingPdfGenerator;
        $this->listingItemsReader = $listingItemsReader;
        $this->listingsReader = $listingsReader;
    }



    public function generate(
        $year,
        User $user,
        array $settings = []
    ) {
        $listings = $this->listingsReader->getAnnualListingsForPDFGeneration($year, $user);
        $listings = Arrays::associate($listings, 'l_id');

        $items = $this->listingItemsReader->findListingsItems(array_keys($listings));

        $pdfFiles = [];
        $listingItemsCollection = [];
        /**
         * @var int $listingId
         * @var IListingPdfSource $pdfSource
         */
        foreach ($listings as $listingId => $listing) {
            foreach ($items as $key => $item) {
                if ($item->getListing()->getId() == $listingId) {
                    $listingItemsCollection[] = $item;
                    unset($items[$key]);
                }
            }

            $pdfFiles[] = $this->listingPdfGenerator->generate($listing, $listingItemsCollection, $settings);
            $listingItemsCollection = [];
        }

        $zipStorageFilePath = $this->storagePath . '/' . $user->getId() . '/' . $year . "/vycetky-$year.zip";

        return $this->zipFiles($pdfFiles, $zipStorageFilePath);
    }



    /**
     * @param IListingPdfFile[] $pdfFiles
     * @param string $zipStorageFilePath
     * @return null
     */
    private function zipFiles(array $pdfFiles, $zipStorageFilePath)
    {
        if (file_exists($zipStorageFilePath) and !is_dir($zipStorageFilePath)) {
            FileSystem::delete($zipStorageFilePath);
        }

        $zip = new \ZipArchive();

        if ($zip->open($zipStorageFilePath, \ZipArchive::CREATE) !== true) {
            return null;
        }

        /** @var IListingPdfFile $pdfFile */
        foreach ($pdfFiles as $pdfFile) {
            if (!file_exists($pdfFile->getStoragePath())) {
                FileSystem::write($pdfFile->getStoragePath(), $pdfFile->getPdfContent());
            }

            $zip->addFile($pdfFile->getStoragePath(), $pdfFile->getFileName());
        }

        $zip->close();

        return $zipStorageFilePath;
    }
}