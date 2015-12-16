<?php

namespace App\Model\Pdf\Listing\PdfSources;

use App\Model\Pdf\Listing\DataAdapters\IListingPdfDataAdapter;
use App\Model\Pdf\PdfSources\IPdfSource;
use Nette\Application\UI\ITemplateFactory;

class ListingPdfSourceFactory
{
    /** @var ITemplateFactory */
    private $templateFactory;



    public function __construct(ITemplateFactory $templateFactory)
    {
        $this->templateFactory = $templateFactory;
    }



    /**
     * @param IListingPdfDataAdapter $pdfDataAdapter
     * @param array $settings
     * @return IPdfSource
     */
    public function create(IListingPdfDataAdapter $pdfDataAdapter, array $settings)
    {
        return new ListingPdfSource($this->templateFactory->createTemplate(), $pdfDataAdapter, $settings);
    }

}