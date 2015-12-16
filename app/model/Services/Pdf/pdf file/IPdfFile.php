<?php

namespace App\Model\Pdf\PdfFiles;

interface IPdfFile
{
    /**
     * @return PdfContent
     */
    public function getPdfContent();



    /**
     * @return string
     */
    public function getFileName();



    /**
     * @return string
     */
    public function getStoragePath();
}