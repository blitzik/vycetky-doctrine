<?php

namespace App\Model\Components;

use App\Model\Components\Forms\Pdf\ListingsPdfSettingsContainer;
use App\Model\Components\Forms\Pdf\UserPdfSettingsContainer;
use App\Model\Domain\Entities\Listing;
use App\Model\ResultObjects\ListingResult;
use App\Model\Services\Pdf\ListingPDFGenerator;
use App\Model\Services\Pdf\PdfResult;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

class ListingPDFGenerationControl extends BaseComponent
{
    /** @var array */
    private $companyParameters;

    /** @var IListingDescriptionControlFactory */
    private $listingDescriptionFactory;

    /** @var ListingPDFGenerator */
    private $listingPDFGenerator;

    /** @var ListingResult */
    private $listingResult;

    /** @var Listing */
    private $listing;

    public function __construct(
        ListingResult $listingResult,
        ListingPDFGenerator $listingPDFGenerator,
        IListingDescriptionControlFactory $listingDescriptionControlFactory
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

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

        /** @var PdfResult $pdf */
        $pdf = $this->listingPDFGenerator
                    ->generateListingPDF(
                        $this->listingResult,
                        $values
                    );

        $this->presenter->sendResponse(new Nette\Application\Responses\FileResponse($pdf->getPdfFilePath(), $pdf->getPdfFilename()));
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

