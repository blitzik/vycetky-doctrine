<?php

namespace App\Model\Services\Pdf;

use App\Model\Services\ItemsService;
use App\Model\Time\TimeUtils;
use Joseki\Application\Responses\PdfResponse;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Caching\Cache;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

class SingleListingPDFGenerator extends Object
{
    const PDF_TEMPLATE = __DIR__ . '/../templates/pdf.latte';

    /** @var ITemplateFactory */
    private $templateFactory;

    /** @var ListingPDFCacheFactory */
    private $cacheFactory;

    /** @var ItemsService */
    private $itemsService;

    /** @var Cache */
    private $cache;

    /** @var ITemplate */
    private $template;

    /** @var string */
    private $pdfStoragePath;

    /** @var string */
    private $documentAuthor;

    public function __construct(
        $pdfStoragePath,
        ItemsService $itemsService,
        ITemplateFactory $templateFactory,
        ListingPDFCacheFactory $cacheFactory
    ) {
        $this->pdfStoragePath = $pdfStoragePath;
        $this->itemsService = $itemsService;
        $this->templateFactory = $templateFactory;
        $this->cacheFactory = $cacheFactory;

        $this->template = $this->templateFactory->createTemplate();
        $this->template->setFile(self::PDF_TEMPLATE);
    }

    public function setDocumentAuthor($documentAuthor)
    {
        $this->documentAuthor = $documentAuthor;
    }

    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @param array $listingData ['listing'][] & ['table'][]; table contains IDisplayableItem items
     * @param array $settings
     * @return PdfResult
     */
    public function generate($listingData, array $settings)
    {
        $listing = $listingData['listing'];

        $settings = $this->getSettings($settings);

        $ss = md5(serialize($settings));
        $storagePath = $this->pdfStoragePath . '/' . $listing['u_id'] . '/' . $listing['l_year'] . '/' . $ss . '/';
        $this->prepareStorage($storagePath);

        $cache = $this->cacheFactory->getCache($listing);

        $cacheKey = 'listing/' . $listing['l_id'] . '/' . $ss;
        $cachedPdfData = $this->getCachedPdfData($cacheKey, $cache, $listingData, $settings, $storagePath);

        $pdfContent = unserialize($cachedPdfData['pdfContent']);
        if (!file_exists($cachedPdfData['path'])) {
            // if there is no .pdf file then create one from cache
            file_put_contents($cachedPdfData['path'], $pdfContent);
        }
        $pdf = new PdfResponse($pdfContent);
        $this->setDocumentProperties($pdf, $listingData);

        $pdfResult = new PdfResult([
            'listing' => $listing,
            'pdf_response' => $pdf,
            'cachedData' => $cachedPdfData
        ]);

        return $pdfResult;
    }

    /**
     * @param array $listingData
     * @param ITemplate $configuredTemplate
     * @return PdfResponse
     */
    private function createPdfResponse(array $listingData, ITemplate $configuredTemplate)
    {
        $pdf = new PdfResponse($configuredTemplate);

        $this->setDocumentProperties($pdf, $listingData);

        return $pdf;
    }

    /**
     * @param PdfResponse $pdfResponse
     * @param array $listingData
     */
    private function setDocumentProperties(PdfResponse $pdfResponse, array $listingData)
    {
        $pdfResponse->documentAuthor = $this->documentAuthor;
        $pdfResponse->documentTitle = Strings::webalize($listingData['listing']['l_id']
                . 'i-' . TimeUtils::getMonthName($listingData['listing']['l_month'])
                . '-' . $listingData['listing']['l_year'])
                . '-' . Strings::webalize($listingData['listing']['l_description']);
    }

    /**
     * @param ITemplate $template
     * @param array $listingData
     * @param array $settings
     * @return ITemplate
     */
    private function prepareTemplate(
        ITemplate $template,
        array $listingData,
        array $settings
    ) {
        $this->setListingsTemplateSettings($template, $settings['listingsSettings']);

        $template->employer = $settings['userSettings']['employer'];
        $template->employeeName = $settings['userSettings']['name'];

        $template->listingData = $listingData;

        return $template;
    }

    /**
     * @param $path
     * @return bool
     */
    private function prepareStorage($path)
    {
        if (!file_exists($path)) {
            FileSystem::createDir($path);
        }
    }

    /**
     * @param $cacheKey
     * @param Cache $cache
     * @param array $listingData
     * @param array $settings
     * @param $storagePath
     * @return mixed|NULL
     */
    private function getCachedPdfData($cacheKey, Cache $cache, array $listingData, array $settings, $storagePath)
    {
        $cachedPdfData = $cache->load($cacheKey, function (& $dependencies) use ($storagePath, $listingData, $settings) {
            $template = $this->prepareTemplate($this->template, $listingData, $settings);
            $pdf = $this->createPdfResponse($listingData, $template);
            $pdfPath = $pdf->save($storagePath);

            $c = [
                'pdfContent' => serialize(file_get_contents($pdfPath)),
                'path' => $pdfPath,
                'filename' => $pdf->documentTitle.'.pdf',
            ];

            $dependencies = [Cache::TAGS => 'listing/' . $listingData['listing']['l_id']];
            return $c;
        });

        return $cachedPdfData;
    }

    /**
     * @param array $listingsPDFGenerationSettings
     * @return array
     */
    private function getSettings(array $listingsPDFGenerationSettings)
    {
        $defaultListingSettings['isWageVisible'] = true;
        $defaultListingSettings['areOtherHoursVisible'] = false;
        $defaultListingSettings['areWorkedHoursVisible'] = false;
        $defaultListingSettings['areLunchHoursVisible'] = false;

        if (!isset($listingsPDFGenerationSettings['listingsSettings'])) {
            $listingsPDFGenerationSettings['listingsSettings'] = $defaultListingSettings;
        } else {
            $listingsPDFGenerationSettings['listingsSettings'] = array_merge($defaultListingSettings, $listingsPDFGenerationSettings['listingsSettings']);
        }

        $defaultUserSettings['employer'] = '';
        $defaultUserSettings['name'] = '';

        if (!isset($listingsPDFGenerationSettings['userSettings'])) {
            $listingsPDFGenerationSettings['userSettings'] = $defaultUserSettings;
        } else {
            $listingsPDFGenerationSettings['userSettings'] = array_merge($defaultUserSettings, $listingsPDFGenerationSettings['userSettings']);
        }

        return $listingsPDFGenerationSettings;
    }

    /**
     * @param ITemplate $template
     * @param array $listingPDFGenerationSettings
     */
    protected function setListingsTemplateSettings(ITemplate $template, array $listingPDFGenerationSettings)
    {
        $template->isWageVisible = $listingPDFGenerationSettings['isWageVisible'];
        $template->areOtherHoursVisible = $listingPDFGenerationSettings['areOtherHoursVisible'];
        $template->areWorkedHoursVisible = $listingPDFGenerationSettings['areWorkedHoursVisible'];
        $template->areLunchHoursVisible = $listingPDFGenerationSettings['areLunchHoursVisible'];
    }
}