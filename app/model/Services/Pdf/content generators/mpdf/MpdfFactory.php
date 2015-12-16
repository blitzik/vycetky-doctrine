<?php

namespace App\Model\Pdf\ContentGenerators\Mpdf;

use App\Model\Pdf\PdfSources\IPdfSource;
use Nette\Object;

class MpdfFactory extends Object
{
    /** @var string */
    private $documentAuthor;



    public function __construct(
        $documentAuthor
    ) {
        $this->documentAuthor = $documentAuthor;
    }



    /**
     * @param IPdfSource $pdfSource
     * @return \mPDF
     */
    public function create(IPdfSource $pdfSource)
    {
        $mpdf = $this->createMPdf();
        $this->basicMPdfConfiguration($mpdf);

        $mpdf->SetTitle($pdfSource->getPdfTitle());
        $mpdf->WriteHTML($pdfSource->getResultHtml());

        return $mpdf;
    }



    protected function createMPdf()
    {
        return new \mPDF(
            'utf-8', // $mode
            'A4',    // $format
            '',      // $default_font_size
            '',      // $default_font
            15,      // margin left
            15,      // margin right
            16,      // margin top
            16,      // margin bottom
            9,       // margin header
            9,       // margin footer
            'P'      // orientation (portrait)
        );
    }



    protected function basicMPdfConfiguration(\mPDF $mPDF)
    {
        $mPDF->biDirectional = false;
        $mPDF->useSubstitutions=false;
        $mPDF->simpleTables = true;
        $mPDF->SetAuthor($this->documentAuthor);
        $mPDF->SetDisplayMode('default', 'continuous');
    }
}