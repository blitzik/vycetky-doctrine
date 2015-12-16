<?php

namespace App\Model\Pdf\ContentGenerators\Mpdf;

use App\Model\Pdf\ContentGenerators\IPdfContentGenerator;
use App\Model\Pdf\PdfFiles\PdfContent;
use App\Model\Pdf\PdfSources\IPdfSource;
use Nette\Object;

class MpdfPdfContentGenerator extends Object implements IPdfContentGenerator
{
    /** @var MpdfFactory */
    private $mpdfFactory;



    public function __construct(
        MpdfFactory $mpdfFactory
    ) {
        $this->mpdfFactory = $mpdfFactory;
    }



    /**
     * @param IPdfSource $pdfSource
     * @return PdfContent
     */
    public function createPdfContent(IPdfSource $pdfSource)
    {
        $mpdf = $this->mpdfFactory->create($pdfSource);

        return new PdfContent($mpdf->Output('', 'S'));
    }

}