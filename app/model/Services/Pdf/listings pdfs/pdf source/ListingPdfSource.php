<?php

namespace App\Model\Pdf\Listing\PdfSources;

use App\Model\Pdf\Listing\DataAdapters\IListingPdfDataAdapter;
use App\Model\Time\TimeUtils;
use Nette\Application\UI\ITemplate;
use Nette\Object;
use Nette\Utils\Strings;

class ListingPdfSource extends Object implements IListingPdfSource
{
    /** @var ITemplate */
    private $template;

    /** @var IListingPdfDataAdapter */
    private $listingPdfDataAdapter;

    /** @var array */
    private $settings;



    public function __construct(
        ITemplate $template,
        IListingPdfDataAdapter $listingPdfData,
        array $settings
    ) {
        $this->template = $template;
        $this->listingPdfDataAdapter = $listingPdfData;
        $this->settings = $this->verifySettings($settings);

        $this->template->setFile($this->getTemplateFile());
        $this->configureTemplate($this->template, $this->settings);

        $this->template->dataAdapter = $this->listingPdfDataAdapter;
    }



    public function setSettings(array $settings)
    {
        $this->settings = $this->verifySettings($settings);
        $this->configureTemplate($this->template, $this->settings);
    }



    public function getPdfTitle()
    {
        // {listingId}i-{monthName}-{year}[-{description}]
        $description = $this->listingPdfDataAdapter->getListingDescription() != '' ? ('-' . Strings::webalize($this->listingPdfDataAdapter->getListingDescription())) : null;

        return Strings::webalize($this->listingPdfDataAdapter->getListingId()
            . 'i-' . TimeUtils::getMonthName($this->listingPdfDataAdapter->getListingMonth())
            . '-' . $this->listingPdfDataAdapter->getListingYear())
            . $description;
    }



    /**
     * @return string
     */
    public function getResultHtml()
    {
        return $this->template->__toString();
    }



    /**
     * @return string
     */
    public function getHashedListingSettings()
    {
        return md5(serialize($this->settings));
    }



    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->listingPdfDataAdapter->getOwnerId();
    }



    /**
     * @return int
     */
    public function getListingId()
    {
        return $this->listingPdfDataAdapter->getListingId();
    }



    /**
     * @return int
     */
    public function getListingYear()
    {
        return $this->listingPdfDataAdapter->getListingYear();
    }



    protected function getTemplateFile()
    {
        return __DIR__ . '/pdf.latte';
    }



    /**
     * @param ITemplate $template
     * @param array $settings
     */
    protected function configureTemplate(ITemplate $template, array $settings)
    {
        $template->isWageVisible         = $settings['listingsSettings']['isWageVisible'];
        $template->areOtherHoursVisible  = $settings['listingsSettings']['areOtherHoursVisible'];
        $template->areWorkedHoursVisible = $settings['listingsSettings']['areWorkedHoursVisible'];
        $template->areLunchHoursVisible  = $settings['listingsSettings']['areLunchHoursVisible'];

        $template->employer = $settings['userSettings']['employer'];
        $template->employeeName = $settings['userSettings']['name'];
    }



    /**
     * @param $listingsPDFGenerationSettings
     * @return array
     */
    protected function verifySettings($listingsPDFGenerationSettings)
    {
        $defaultListingSettings['isWageVisible'] = false;
        $defaultListingSettings['areOtherHoursVisible'] = false;
        $defaultListingSettings['areWorkedHoursVisible'] = false;
        $defaultListingSettings['areLunchHoursVisible'] = false;

        $resultSettings = [];
        if (!isset($listingsPDFGenerationSettings['listingsSettings'])) {
            $resultSettings['listingsSettings'] = $defaultListingSettings;
        } else {
            $resultSettings['listingsSettings'] = array_merge($defaultListingSettings, $listingsPDFGenerationSettings['listingsSettings']);
        }

        $defaultUserSettings['employer'] = '';
        $defaultUserSettings['name'] = '';

        if (!isset($listingsPDFGenerationSettings['userSettings'])) {
            $resultSettings['userSettings'] = $defaultUserSettings;
        } else {
            $resultSettings['userSettings'] = array_merge($defaultUserSettings, $listingsPDFGenerationSettings['userSettings']);
        }

        return $resultSettings;
    }


}