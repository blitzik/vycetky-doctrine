<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\ListingsFacade;
use Nette\InvalidArgumentException;
use App\Model\Domain\Entities\Listing;
use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;

class ListingFormControl extends BaseComponent
{
    /** @var IListingDescriptionControlFactory  */
    private $listingDescriptionFactory;

    /** @var ListingFormFactory  */
    private $listingFormFactory;

    /** @var ListingsFacade  */
    private $listingFacade;

    /** @var Listing */
    private $listing;

    /** @var User */
    private $owner;


    public function __construct(
        $listing,
        User $owner,
        IListingDescriptionControlFactory $listingDescriptionFactory,
        ListingFormFactory $listingFormFactory,
        ListingsFacade $listingFacade
    ) {
        parent::__construct();

        if (!($listing instanceof Listing or $listing == null)) {
            throw new InvalidArgumentException;
        }

        $this->listing = $listing;

        $this->listingDescriptionFactory = $listingDescriptionFactory;
        $this->listingFormFactory = $listingFormFactory;
        $this->listingFacade = $listingFacade;
        $this->owner = $owner;
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

    protected function createComponentListingForm()
    {
        $form = $this->listingFormFactory->create();

        if ($this->listing !== null) { // Edit

            $form['month']->setItems(
                [$this->listing->month => TimeUtils::getMonthName($this->listing->month)]
            );
            $form['year']->setItems([$this->listing->year => $this->listing->year]);
            $form['description']->setDefaultValue($this->listing->description);
            $form['hourlyWage']->setDefaultValue(
                $this->listing->hourlyWage != 0 ?
                $this->listing->hourlyWage :
                null
            );

            $form['save']->caption = 'Uložit výčetku';

        } else { // New Listing

            $currentDate = new \DateTime('now');
            $year = $this->presenter->getParameter('year') != null ?
                    $this->presenter->getParameter('year') :
                    $currentDate->format('Y');

            $month = $this->presenter->getParameter('month') != null ?
                     $this->presenter->getParameter('month') :
                     $currentDate->format('n');

            try {
                $form['month']->setDefaultValue($month);
                $form['year']->setDefaultValue($year);

            } catch (InvalidArgumentException $e) {
                $form->addError('Lze vybírat pouze z hodnot, které jsou dostupné ve formuláři.');
            }
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, $values)
    {
        if ($this->listing === null) {
            $this->listing = new Listing(
                $values['year'],
                $values['month'],
                $this->owner
            );
        }

        $this->listing->description = $values['description'];
        $this->listing->hourlyWage = $values['hourlyWage'];

        $this->listingFacade->saveListing($this->listing);

        $this->presenter->flashMessage('Výčetka pro '
                            .TimeUtils::getMonthName($values['month']). ' '
                            .$values['year'] . ' byla uložena.', 'success');

        if (isset($this->presenter->backlink))
            $this->presenter->restoreRequest($this->presenter->backlink);

        $this->presenter->redirect(
            'Listing:overview',
            array('month'=>$values['month'], 'year'=>$values['year'])
        );
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/listingForm.latte');

        $template->render();
    }

}