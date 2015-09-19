<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ItemsFacade;
use App\Model\Facades\ListingsFacade;
use App\Model\Query\ListingsQuery;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Tracy\Debugger;

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
     * @var Listing
     */
    private $listing;

    public function __construct(
        Listing $listing,
        ItemsFacade $itemsFacade,
        ListingsFacade $listingsFacade,
        IListingDescriptionControlFactory $listingDescriptionControlFactory
    ) {
        $this->listing = $listing;
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

        $listingQuery = new ListingsQuery();
        $listingQuery->resetSelect()
                     ->withTotalWorkedHours()
                     ->byId($this->listing->getId());

        $template->isWageVisible = $values['wage'];

        $template->areOtherHoursVisible = $values['otherHours'];
        if ($values['otherHours']) {
            $listingQuery->withOtherHours();
        }

        $template->areWorkedHoursVisible = $values['workedHours'];
        if ($values['workedHours']) {
            $listingQuery->withWorkedHours();
        }

        $template->areLunchHoursVisible = $values['lunch'];
        if ($values['lunch']) {
            $listingQuery->withLunchHours();
        }

        $listingData = $this->listingsFacade
                            ->fetchListings($listingQuery)
                            ->toArray()[0];

        $template->totalWorkedHours = new \InvoiceTime(isset($listingData['total_worked_hours']) ?
                                                            (int)$listingData['total_worked_hours'] :
                                                            null);
        $template->workedHours      = new \InvoiceTime(isset($listingData['worked_hours']) ?
                                                             $listingData['worked_hours'] :
                                                             null);
        $template->lunchHours       = new \InvoiceTime(isset($listingData['lunch_hours']) ?
                                                             $listingData['lunch_hours'] :
                                                             null);
        $template->otherHours       = new \InvoiceTime(isset($listingData['other_hours']) ?
                                                             $listingData['other_hours'] :
                                                             null);

        $template->listing      = $this->listing;
        $template->username     = $values['name'] == null ?: $values['name'];
        $template->employer     = $values['employer'];
        $template->employeeName = $values['name'];

        $pdf = new \PdfResponse\PdfResponse($template);

        $this->presenter->sendResponse($pdf);
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