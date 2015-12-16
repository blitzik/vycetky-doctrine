<?php

namespace App\Model\Pdf\Listing\PdfFiles;

use App\Model\Pdf\PdfFiles\IPdfFile;

interface IListingPdfFile extends IPdfFile
{
    /**
     * @return int
     */
    public function getListingId();



    /**
     * @return int
     */
    public function getListingOwnerId();
}