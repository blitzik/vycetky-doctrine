<?php

namespace App\Model\Pdf\ContentGenerators;

use App\Model\Pdf\PdfFiles\PdfContent;
use App\Model\Pdf\PdfSources\IPdfSource;

interface IPdfContentGenerator
{
    /**
     * @param IPdfSource $listingPdf
     * @return PdfContent
     */
    public function createPdfContent(IPdfSource $listingPdf);
}