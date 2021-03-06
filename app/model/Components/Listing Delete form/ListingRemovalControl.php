<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ListingsFacade;
use App\Model\ResultObjects\ListingResult;
use Doctrine\DBAL\DBALException;
use Nette\Application\UI\Form;

class ListingRemovalControl extends BaseComponent
{
    /** @var  array */
    public $onRemoveSuccess;

    /** @var  array */
    public $onCancelClick;

    /**
     * @var ListingsFacade
     */
    private $listingsFacade;

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
        ListingsFacade $listingsFacade
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();

        $this->listingsFacade = $listingsFacade;
    }

    protected function createComponentDeleteListingForm()
    {
        $form = new Form();

        $form->addText('check', 'Pro smazání výčetky napište do pole "smazat".')
                ->addRule(Form::FILLED, 'Kontrola musí být vyplněna.')
                ->addRule(Form::EQUAL, 'Pro smazání výčetky musí být vyplňeno správné kontrolní slovo.', 'smazat')
                ->setHtmlId('listing-check-input');

        $form->addSubmit('delete', 'Odstranit výčetku')
                ->setHtmlId('listing-remove-button')
                ->onClick[] = [$this, 'processDeleteListing'];

        $form->addSubmit('cancel', 'Vrátit se zpět')
                ->setValidationScope(false)
                ->onClick[] = [$this, 'processCancel'];

        $form->addProtection();

        return $form;
    }

    public function processDeleteListing(\Nette\Forms\Controls\SubmitButton $button)
    {
        try {
            $this->listingsFacade->removeListing($this->listing);
        } catch (DBALException $e) {
            $this->flashMessage(
                'Výčetka nemohla být odstraněna. Zkuste akci opakovat později.',
                'warning'
            );
            $this->redirect('this');
        }

        $this->onRemoveSuccess($this->listing->getYear(), $this->listing->getMonth());
    }

    public function processCancel()
    {
        $this->onCancelClick($this->listing->getYear(), $this->listing->getMonth());
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->listing = $this->listing;

        $template->workedDays = $this->listingResult->getWorkedDays();

        $template->render();
    }
}