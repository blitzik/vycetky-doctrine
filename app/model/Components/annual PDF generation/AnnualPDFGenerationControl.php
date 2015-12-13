<?php

namespace App\Model\Components;

use App\Model\Components\Forms\Pdf\ListingsPdfSettingsContainer;
use App\Model\Components\Forms\Pdf\UserPdfSettingsContainer;
use App\Model\Domain\Entities\User;
use App\Model\Facades\ListingsFacade;
use App\Model\Services\Pdf\ListingPDFGenerator;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

class AnnualPDFGenerationControl extends BaseComponent
{
    /** @var array */
    private $companyParameters;

    /** @var ListingsFacade */
    private $listingsFacade;

    /** @var ListingPDFGenerator */
    private $PDFGenerator;

    /** @var User */
    private $user;

    public function __construct(
        User $user,
        array $companyParameters,
        ListingsFacade $listingsFacade,
        ListingPDFGenerator $PDFGenerator
    ) {
        $this->user = $user;
        $this->listingsFacade = $listingsFacade;
        $this->companyParameters = $companyParameters;
        $this->PDFGenerator = $PDFGenerator;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/annualPdfGeneration.latte');

        $annualListings = $this->listingsFacade->getListingsYears($this->user);
        krsort($annualListings);

        $template->listingsData = $annualListings;

        $template->render();
    }

    protected function createComponentResultPdf()
    {
        $form = new Form();

        $form->addComponent(new UserPdfSettingsContainer($this->companyParameters), 'userSettings');
        $form->addComponent(new ListingsPdfSettingsContainer(), 'listingsSettings');

        $form['userSettings']['name']->setDefaultValue($this->user->name);

        $form->addSubmit('generatePdf', 'Stáhnout PDF')
                ->onClick[] = [$this, 'generatePdf'];

        $form->addSubmit('reset', 'Reset nastavení')
                ->onClick[] = [$this, 'processReset'];

        $form->addProtection();

        return $form;
    }

    /**
     * @secured
     */
    public function generatePdf(SubmitButton $button)
    {
        $values = $button->getForm()->getHttpData(Form::DATA_TEXT);
        if (!empty($values['listingsSettings'])) {
            array_walk($values['listingsSettings'], function (&$value) { $value = (bool)$value; });
        }

        $zipPath = $this->PDFGenerator
                        ->generateAnnualSeparatedPDFs(
                            $values['year'],
                            $this->user,
                            $values
                        );

        $this->presenter->sendResponse(new FileResponse($zipPath));
    }

    /**
     * @secured
     */
    public function processReset(SubmitButton $button)
    {
        $this->redirect('this');
    }


}