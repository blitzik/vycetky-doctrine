<?php

namespace App\Model\Pdf\PdfFiles;

use Nette\Object;

class PdfContent extends Object
{
    /** @var string */
    private $pdfContent;



    public function __construct($pdfContent)
    {
        $this->pdfContent = $pdfContent;
    }



    /**
     * @return string
     */
    public function getContent()
    {
        return $this->pdfContent;
    }
}