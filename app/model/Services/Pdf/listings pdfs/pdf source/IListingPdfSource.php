<?php

namespace App\Model\Pdf\Listing\PdfSources;

use App\Model\Pdf\PdfSources\IPdfSource;

interface IListingPdfSource extends IPdfSource
{
    /**
     * @return int
     */
    public function getListingId();



    /**
     * @return int
     */
    public function getListingYear();



    /**
     * @return int
     */
    public function getOwnerId();



    /**
     * @return string
     */
    public function getHashedListingSettings();
}