<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ItemsFacade;
use App\Model\Facades\ListingsFacade;
use App\Model\ResultObjects\ListingResult;
use App\Model\Time\TimeUtils;
use Joseki\Application\Responses\PdfResponse;
use Nette;
use Nette\Application\IResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Response;

class ListingPDFGenerationControl extends Control
{
    /**
     * @var array
     */
    private $companyParameters;


    /**
     * @var ItemsFacade
     */
    private $itemsFacade;

    /**
     * @var ListingsFacade
     */
    private $listingsFacade;

    /**
     * @var IListingDescriptionControlFactory
     */
    private $listingDescriptionFactory;

    /**
     * @var ListingResult
     */
    private $listingResult;

    /**
     * @var Listing
     */
    private $listing;

    public function __construct(
        ListingResult $listingResult,
        ItemsFacade $itemsFacade,
        ListingsFacade $listingsFacade,
        IListingDescriptionControlFactory $listingDescriptionControlFactory
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

        $this->itemsFacade = $itemsFacade;
        $this->listingsFacade = $listingsFacade;
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

        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/pdf.latte');

        $template->itemsCollection = $this->itemsFacade
                                          ->generateEntireTable($this->listing);

        $template->isWageVisible = $values['wage'];
        $template->areOtherHoursVisible = $values['otherHours'];
        $template->areWorkedHoursVisible = $values['workedHours'];
        $template->areLunchHoursVisible = $values['lunch'];


        $template->totalWorkedHours = $this->listingResult->getTotalWorkedHours();
        $template->workedHours      = $this->listingResult->getWorkedHours();
        $template->lunchHours       = $this->listingResult->getLunchHours();
        $template->otherHours       = $this->listingResult->getOtherHours();

        $template->listing      = $this->listing;
        $template->username     = $values['name'] == null ?: $values['name'];
        $template->employer     = $values['employer'];
        $template->employeeName = $values['name'];

        $listingName = TimeUtils::getMonthName($this->listing->getMonth()) . ' '
                       . $this->listing->getYear();

        $response = new PdfResponse($template);
        $response->setSaveMode(PdfResponse::INLINE);
        $response->documentAuthor = 'Výčetkový systém - http://vycetky.alestichava.cz';
        $response->documentTitle = $listingName;

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

