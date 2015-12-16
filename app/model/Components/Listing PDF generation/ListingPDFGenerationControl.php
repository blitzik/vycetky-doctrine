<?php

namespace App\Model\Components;

use App\Model\Components\Forms\Pdf\ListingsPdfSettingsContainer;
use App\Model\Components\Forms\Pdf\UserPdfSettingsContainer;
use App\Model\Domain\Entities\Listing;
use App\Model\Pdf\Listing\Generators\ListingPdfGenerator;
use App\Model\Pdf\Listing\PdfFiles\IListingPdfFile;
use App\Model\ResultObjects\ListingResult;
use App\Model\Services\Readers\ListingItemsReader;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

class ListingPDFGenerationControl extends BaseComponent
{
    /** @var array */
    private $companyParameters;

    /** @var IListingDescriptionControlFactory */
    private $listingDescriptionFactory;

    /** @var ListingItemsReader */
    private $listingItemsReader;

    /** @var ListingPdfGenerator */
    private $listingPDFGenerator;

    /** @var ListingResult */
    private $listingResult;

    /** @var Listing */
    private $listing;



    public function __construct(
        ListingResult $listingResult,
        ListingItemsReader $listingItemsReader,
        ListingPdfGenerator $listingPDFGenerator,
        IListingDescriptionControlFactory $listingDescriptionControlFactory
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

        $this->listingItemsReader = $listingItemsReader;
        $this->listingPDFGenerator = $listingPDFGenerator;
        $this->listingDescriptionFactory = $listingDescriptionControlFactory;
    }

    public function setCompanyParameters(array $companyParameters)
    {
        $this->companyParameters = $companyParameters;
    }

    protected function createComponentListingDescription()
    {
        $desc = $this->listingDescriptionFactory
            ->create($this->listing);

        $desc->setAsClickable(
            'Front:Listing:detail',
            ['id' => $this->listing->getId()]
        );

        return $desc;
    }

    protected function createComponentResultPdf()
    {
        $form = new Form();

        $form->addComponent(new UserPdfSettingsContainer($this->companyParameters), 'userSettings');
        $form->addComponent(new ListingsPdfSettingsContainer(), 'listingsSettings');

        $form->addSubmit('generatePdf', 'Stáhnout PDF')
                ->onClick[] = [$this, 'generatePdf'];

        $form->addSubmit('reset', 'Reset nastavení')
                ->onClick[] = [$this, 'processReset'];

        $form->addProtection();

        return $form;
    }

    public function generatePdf(SubmitButton $button)
    {
        $values = $button->getForm()->getValues(true);

        $items = $this->listingItemsReader->findListingsItems([$this->listingResult->getListingId()]);

        $listing = $this->listingResult->getListing();
        $listingData = [
            'l_id' => $listing->id,
            'l_year' => $listing->year,
            'l_month' => $listing->month,
            'l_description' => $listing->description,
            'l_hourlyWage' => $listing->hourlyWage,
            'u_id' => $listing->user->id,
            'u_name' => $listing->user->name,
            'worked_days' => $this->listingResult->getWorkedDays(),
            'worked_hours' => $this->listingResult->getWorkedHours(),
            'total_worked_hours_in_sec' => $this->listingResult->getTotalWorkedHours()->toSeconds(),
            'lunch_hours' => $this->listingResult->getLunchHours(),
            'other_hours' => $this->listingResult->getOtherHours()
        ];

        /** @var IListingPdfFile $pdf */
        $pdf = $this->listingPDFGenerator
                    ->generate(
                        $listingData,
                        $items,
                        $values // settings
                    );

        $response = new Nette\Application\Responses\FileResponse($pdf->getStoragePath(), $pdf->getFileName()/*, 'application/pdf', false*/);

        $this->presenter->sendResponse($response);
    }

    public function processReset(SubmitButton $button)
    {
        $this->redirect('this');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $template->render();
    }
}

