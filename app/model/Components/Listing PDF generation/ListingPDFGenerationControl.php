<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ItemsFacade;
use App\Model\Facades\ListingsFacade;
use App\Model\ResultObjects\ListingResult;
use App\Model\Services\Pdf\ListingPDFGenerator;
use App\Model\Services\Pdf\PdfResult;
use App\Model\Time\TimeUtils;
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

    /** @var ListingsFacade */
    private $listingsFacade;

    /** @var ListingResult */
    private $listingResult;

    /**  @var ItemsFacade */
    private $itemsFacade;

    /** @var Listing */
    private $listing;

    public function __construct(
        ListingResult $listingResult,
        ItemsFacade $itemsFacade,
        ListingsFacade $listingsFacade,
        ListingPDFGenerator $listingPDFGenerator,
        IListingDescriptionControlFactory $listingDescriptionControlFactory
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

        $this->itemsFacade = $itemsFacade;
        $this->listingsFacade = $listingsFacade;
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

    protected function createComponentListingResultSettings()
    {
        $form = new Form();

        $form->addText('employer', 'Zaměstnavatel:', 25, 70)
                ->setDefaultValue($this->companyParameters['name']);

        $form->addText('name', 'Jméno:', 25, 70);

        $form->addCheckbox('wage', 'Zobrazit "Základní mzdu"')
                ->setDefaultValue(true);

        $form->addCheckbox('otherHours', 'Zobrazit "Ostatní hodiny"');
        $form->addCheckbox('workedHours', 'Zobrazit "Odpracované hodiny"');
        $form->addCheckbox('lunch', 'Zobrazit hodiny strávené obědem');

        $form->addSubmit('generatePdf', 'Vygeneruj PDF')
                ->onClick[] = [$this, 'generatePdf'];

        $form->addSubmit('reset', 'Reset nastavení')
                ->onClick[] = [$this, 'processReset'];

        return $form;
    }

    public function generatePdf(SubmitButton $button)
    {
        $values = $button->getForm()->getValues();

        $settings = [
            'isWageVisible' => $values['wage'],
            'areOtherHoursVisible' => $values['otherHours'],
            'areWorkedHoursVisible' => $values['workedHours'],
            'areLunchHoursVisible' => $values['lunch']
        ];

        /** @var PdfResult $pdf */
        $pdf = $this->listingPDFGenerator
                    ->generateListingPDF(
                        $this->listingResult,
                        $settings
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

