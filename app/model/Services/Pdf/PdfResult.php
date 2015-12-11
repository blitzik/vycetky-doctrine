<?php

namespace App\Model\Services\Pdf;

use Joseki\Application\Responses\PdfResponse;
use Nette\Object;

class PdfResult extends Object
{
    /** @var array */
    private $pdfData;

    public function __construct(array $pdfData)
    {
        $this->pdfData = $pdfData;
    }

    /**
     * @return array
     */
    public function getListing()
    {
        return $this->pdfData['listing'];
    }

    /**
     * @return PdfResponse
     */
    public function getPdfResponse()
    {
        return $this->pdfData['pdf_response'];
    }

    /**
     * @return string
     */
    public function getPdfFilename()
    {
        return $this->pdfData['cachedData']['filename'];
    }

    /**
     * @return string
     */
    public function getPdfFilePath()
    {
        return $this->pdfData['cachedData']['path'];
    }
}