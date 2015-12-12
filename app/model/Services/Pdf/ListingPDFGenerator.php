<?php

namespace App\Model\Services\Pdf;

use App\Model\Domain\Entities\ListingItem;
use App\Model\ResultObjects\ListingResult;
use App\Model\Services\ItemsService;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Readers\ListingsReader;
use App\Model\Domain\Entities\User;
use Joseki\Application\Responses\PdfResponse;
use Nette\Object;
use Nette\Utils\Arrays;

class ListingPDFGenerator extends Object
{
    /** @var ListingPDFGenerator */
    private $listingPDFGenerator;

    /** @var ListingItemsReader */
    private $listingItemsReader;

    /** @var ListingsReader */
    private $listingsReader;

    /** @var ItemsService */
    private $itemsService;

    public function __construct(
        \App\Model\Services\Pdf\SingleListingPDFGenerator $listingPDFGenerator,
        ListingItemsReader $listingItemsReader,
        ListingsReader $listingsReader,
        ItemsService $itemsService
    ) {
        $this->listingPDFGenerator = $listingPDFGenerator;
        $this->listingsReader = $listingsReader;
        $this->itemsService = $itemsService;
        $this->listingItemsReader = $listingItemsReader;
    }

    public function generateAnnualSeparatedPDFs(
        $year,
        User $user,
        array $settings = []
    ) {
        $listings = $this->listingsReader->getAnnualListingsForPDFGeneration($year, $user);
        $listings = Arrays::associate($listings, 'l_id');

        $listingsData = $this->getListingsData($listings);

        $processedListings = [];
        foreach ($listingsData as $listingID => $data) {
            $processedListings[$listingID] = $this->listingPDFGenerator
                                                  ->generate($data, $settings);
        }

        return $this->zipPDFs($processedListings, $year);
    }

    /**
     * @param ListingResult $listingResult
     * @param array $settings
     * @return PdfResponse
     */
    public function generateListingPDF(ListingResult $listingResult, array $settings)
    {
        $listing = $listingResult->getListing();
        $l = [
            $listing->id => [
                'l_id' => $listing->id,
                'l_year' => $listing->year,
                'l_month' => $listing->month,
                'l_description' => $listing->description,
                'l_hourlyWage' => $listing->hourlyWage,
                'u_id' => $listing->user->id,
                'u_name' => $listing->user->name,
                'worked_days' => $listingResult->getWorkedDays(),
                'worked_hours' => $listingResult->getWorkedHours(),
                'total_worked_hours_in_sec' => $listingResult->getTotalWorkedHours()->toSeconds(),
                'lunch_hours' => $listingResult->getLunchHours(),
                'other_hours' => $listingResult->getOtherHours()
            ]
        ];


        $listingsData = $this->getListingsData($l);

        /** @var PdfResult $result */
        $result = $this->listingPDFGenerator->generate($listingsData[$listing->id], $settings);

        return $result;
    }

    /**
     * @param int $year
     * @param array $processedListings
     * @return null|string
     */
    private function zipPDFs(array $processedListings, $year)
    {
        $zip = new \ZipArchive();

        /** @var PdfResult $firstItem */
        $firstItem = reset($processedListings);
        $userStoragePath = $firstItem->getPdfFilePath();
        $zipPath = mb_substr($userStoragePath, 0, mb_strrpos($userStoragePath, '/')) . "/vycetky-$year.zip";

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            return null;
        }

        foreach ($processedListings as $id => $pdfResult) {
            //dump($pdfResult);
            $zip->addFile($pdfResult->getPdfFilePath(), $pdfResult->getPdfFilename());
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * @param array $listings
     * @return array
     */
    private function getListingsData(array $listings)
    {
        $items = $this->listingItemsReader->findListingsItems(array_keys($listings));

        $itemsByListing = [];

        /** @var ListingItem $item */
        foreach ($items as $item) {
            $l = $listings[$item->getListing()->getId()];
            $itemsByListing[$l['l_id']]['items'][$item->id] = $item;

            if (!isset($itemsByListing[$l['l_id']]['total_worked_hours_in_sec'])
                or !isset($itemsByListing[$l['l_id']]['period'])) {
                $itemsByListing[$l['l_id']]['total_worked_hours_in_sec'] = $l['total_worked_hours_in_sec'];
                $itemsByListing[$l['l_id']]['period'] = \DateTime::createFromFormat('!Y-m', $l['l_year'] . '-' . $l['l_month']);
            }
        }

        // because of listings that does not have any rows
        $diff = array_diff_key($listings, $itemsByListing);
        foreach ($diff as $listingId => $nope) {
            $itemsByListing[$listingId]['items'] = [];
            $itemsByListing[$listingId]['total_worked_hours_in_sec'] = 0;
            $itemsByListing[$listingId]['period'] = \DateTime::createFromFormat('!Y-m', $listings[$listingId]['l_year'].'-'.$listings[$listingId]['l_month']);
        }


        $entireListings = [];
        foreach ($itemsByListing as $id => $listing) {
            $di = $this->itemsService->convert2DisplayableItems($listing['items']);
            $et = $this->itemsService->generateEntireTable($di, $listing['period']);

            $entireListings[$id]['table'] = $et;
            $entireListings[$id]['listing'] = $listings[$id];
            $entireListings[$id]['listing']['period'] = $listing['period'];
        }

        return $entireListings;
    }
}