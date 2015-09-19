<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Facades\ListingsFacade;
use App\Model\Time\TimeUtils;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class ListingCopyFormControl extends Control
{
    /**
     * @var array
     */
    public $onListingCopySuccess;


    /**
     * @var Listing
     */
    private $listing;

    /**
     * @var IListingDescriptionControlFactory
     */
    private $listingDescriptionFactory;

    /**
     * @var ListingFormFactory
     */
    private $listingFormFactory;

    /**
     * @var ListingsFacade
     */
    private $listingsFacade;

    public function __construct(
        Listing $listing,
        IListingDescriptionControlFactory $listingDescriptionControlFactory,
        ListingFormFactory $listingFormFactory,
        ListingsFacade $listingsFacade
    ) {
        $this->listing = $listing;
        $this->listingDescriptionFactory = $listingDescriptionControlFactory;
        $this->listingFormFactory = $listingFormFactory;
        $this->listingsFacade = $listingsFacade;
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

    protected function createComponentForm()
    {
        $comp = $this->listingFormFactory->create();

        $monthNumber = $this->listing->getMonth();
        $comp['month']->setItems([$monthNumber => TimeUtils::getMonthName($monthNumber)]);

        $comp['year']->setItems([$this->listing->getYear() => $this->listing->getYear()]);

        $comp['save']->caption = 'Vytvořit kopii';

        $comp->onSuccess[] = [$this, 'processForm'];

        return $comp;
    }

    public function processForm(Form $form, $values)
    {
        try {
            $newListing = $this->listingsFacade
                               ->establishListingCopy($this->listing, true, (array)$values);

        } catch (\Exception $e) {
            $this->flashMessage(
                'Kopie výčetky nemohla být založena. Zkuste prosím akci
                 opakovat později.',
                'warning'
            );
            $this->redirect('this');
        }

        $this->onListingCopySuccess($newListing);
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');



        $template->render();
    }

}