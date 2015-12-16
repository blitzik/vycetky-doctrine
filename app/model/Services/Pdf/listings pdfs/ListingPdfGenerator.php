<?php

namespace App\Model\Pdf\Listing\Generators;

use App\Model\Pdf\Listing\DataAdapters\ListingPdfDataAdapter;
use App\Model\Pdf\Listing\FileGenerators\ListingPdfFileGenerator;
use App\Model\Pdf\Listing\PdfSources\IListingPdfSource;
use App\Model\Pdf\Listing\PdfSources\ListingPdfSourceFactory;
use App\Model\Services\ItemsService;
use Nette\Object;

class ListingPdfGenerator extends Object
{
    /** @var ListingPdfSourceFactory */
    private $listingPdfSourceFactory;

    /** @var ListingPdfFileGenerator */
    private $listingPdfFileGenerator;

    /** @var ItemsService */
    private $itemsService;



    public function __construct(
        ListingPdfFileGenerator $listingPdfFileGenerator,
        ListingPdfSourceFactory $listingPdfSourceFactory,
        ItemsService $itemsService
    ) {
        $this->listingPdfFileGenerator = $listingPdfFileGenerator;
        $this->listingPdfSourceFactory = $listingPdfSourceFactory;
        $this->itemsService = $itemsService;
    }



    /**
     * @param array $listing
     * @param array $listingItems
     * @param array $settings
     * @return \App\Model\Pdf\Listing\PdfFiles\IListingPdfFile
     */
    public function generate(array $listing, array $listingItems, array $settings)
    {
        $pdfSource = $this->getListingPDFSource($listing, $listingItems, $settings);
        $pdfFile = $this->listingPdfFileGenerator->generate($pdfSource);

        return $pdfFile;
    }



    /**
     * @param array $listing
     * @param array $listingItems
     * @param array $settings
     * @return IListingPdfSource
     */
    private function getListingPDFSource(
        array $listing,
        array $listingItems,
        array $settings
    ) {
        $itemsByListing = [];

        if (empty($listingItems)) {
            $itemsByListing['listing'] = $listing;
            $itemsByListing['items'] = [];
            $itemsByListing['total_worked_hours_in_sec'] = 0;
            $itemsByListing['period'] = \DateTime::createFromFormat('!Y-m', $listing['l_year'].'-'.$listing['l_month']);
        } else {
            $itemsByListing['listing'] = $listing;
            $itemsByListing['items'] = $listingItems;
        }

        $d = new ListingPdfDataAdapter($itemsByListing, $this->itemsService);
        $listingsPdfSource = $this->listingPdfSourceFactory->create($d, $settings);

        return $listingsPdfSource;
    }
}