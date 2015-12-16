<?php

namespace App\Model\Pdf\Listing\PdfFiles;

use App\Model\Pdf\PdfFiles\PdfContent;
use Nette\Object;

class ListingPdfFile extends Object implements IListingPdfFile
{
    /** @var PdfContent */
    private $pdfContent;

    /** @var string */
    private $fileName;

    /** @var string */
    private $storagePath;

    /** @var int */
    private $listingId;

    /** @var int */
    private $listingYear;

    /** @var int */
    private $listingOwnerId;



    /**
     * ListingPdfFile constructor.
     * @param PdfContent $pdfContent
     * @param string $absoluteFileStoragePath
     * @param int $listingId
     * @param int $listingYear
     * @param int $ownerId
     */
    public function __construct(
        PdfContent $pdfContent,
        $absoluteFileStoragePath,
        $listingId,
        $listingYear,
        $ownerId
    ) {
        $this->pdfContent = $pdfContent;

        $this->storagePath = $absoluteFileStoragePath;
        $this->listingId = $listingId;
        $this->listingYear = $listingYear;
        $this->listingOwnerId = $ownerId;

        $this->fileName = mb_substr($absoluteFileStoragePath, mb_strrpos($absoluteFileStoragePath, '/') + 1);
    }



    public function getFileName()
    {
        return $this->fileName;
    }



    public function getStoragePath()
    {
        return $this->storagePath;
    }



    /**
     * @return string
     */
    public function getPdfContent()
    {
        return $this->pdfContent->getContent();
    }



    /**
     * @return int
     */
    public function getListingId()
    {
        return $this->listingId;
    }



    /**
     * @return int
     */
    public function getListingYear()
    {
        return $this->listingYear;
    }



    public function getListingOwnerId()
    {
        return $this->listingOwnerId;
    }

}