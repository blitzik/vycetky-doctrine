<?php

namespace App\Model\Pdf\PdfSources;


interface IPdfSource
{
    /**
     * @return string
     */
    public function getPdfTitle();



    /**
     * @return string
     */
    public function getResultHtml();
}