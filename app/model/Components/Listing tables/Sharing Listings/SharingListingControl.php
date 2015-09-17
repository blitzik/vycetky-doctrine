<?php

namespace App\Model\Components;

use App\Model\Components\ItemsTable\IItemsTableControlFactory;
use App\Model\Notifications\SharedListingNotification;
use Nextras\Application\UI\SecuredLinksControlTrait;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\ListingFacade;
use App\Model\Facades\UserManager;
use Nette\Application\UI\Control;
use App\Model\Entities\Listing;
use Nette\Application\UI\Form;
use Nette\Security\User;

class SharingListingControl extends Control
{
    use SecuredLinksControlTrait;
    
    /**
     * @var SharedListingNotification
     */
    private $sharedListingNotification;

    /**
     * @var IItemsTableControlFactory
     */
    private $itemsTableControlFactory;

    /**
     * @var ListingFacade
     */
    private $listingFacade;

    /**
     * @var MessagesFacade
     */
    private $messagesFacade;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var User
     */
    private $user;


    /**
     * @var Listing
     */
    private $listing;

    /**
     * @var array
     */
    private $users;

    /**
     * @var Listing[]
     */
    private $newListings;


    public function __construct(
        Listing $listing,
        SharedListingNotification $sharedListingNotification,
        IItemsTableControlFactory $itemsTableControlFactory,
        MessagesFacade $messagesFacade,
        ListingFacade $listingFacade,
        UserManager $userManager,
        User $user
    ) {
        $this->listing = $listing;

        $this->sharedListingNotification = $sharedListingNotification;
        $this->itemsTableControlFactory = $itemsTableControlFactory;
        $this->messagesFacade = $messagesFacade;
        $this->listingFacade = $listingFacade;
        $this->userManager = $userManager;
        $this->user = $user;

        $this->users = $this->userManager->findAllUsers([$this->user->id]);
    }

    protected function createComponentItemsTable()
    {
        $comp = $this->itemsTableControlFactory->create($this->listing);
        $comp->showTableCaption(
            $this->listing->description,
            $this->listing->workedDays,
            $this->listing->totalWorkedHours,
            'Front:Listing:detail',
            ['id' => $this->listing->listingID]
        );

        $comp->showCheckBoxes();

        return $comp;
    }

    protected function createComponentForm()
    {
        $form = new Form();

        $form->addText('description', 'Popis výčetky:')
                ->setRequired('Vyplňte pole Popis Výčetky.')
                ->setAttribute('placeholder', 'Vyplňte popis výčetky');

        $form->addMultiSelect('recipients', 'Pro uživatele:', null, 7)
                ->setRequired('Vyberte alespoň jednoho příjemce.')
                ->setItems($this->users);

        $form->addSubmit('send', 'Odeslat výčetku');

        $form->onSuccess[] = [$this, 'processListingSharing'];
        $form->onSuccess[] = [$this, 'sendNotifications'];

        return $form;
    }

    public function processListingSharing(Form $form, $values)
    {
        $ignoredItems = $form->getHttpData(Form::DATA_TEXT, 'items[]');

        try {
            $this->newListings = $this->listingFacade->shareListing(
                $this->listing,
                $values['description'],
                $values['recipients'],
                $ignoredItems
            );

        } catch (\DibiException $e) {
            $this->presenter->flashMessage('Nastala chyba při pokusu o sdílení výčetky. Zkuste akci opakovat později.', 'error');
            $this->redirect('this');
        }

        $this->presenter->flashMessage('Výčetka byla úspěšně sdílena.', 'success');
    }

    public function sendNotifications(Form $form, $values)
    {
        $messages = [];
        foreach ($this->newListings as $listing) {
            $message = $this->sharedListingNotification->getNotificationMessage(
                $listing,
                $this->user->getIdentity()->username,
                $this->users[$listing->getOwnerID()]
            );

            $messages[$listing->getOwnerID()] = $message;
        }

        try {
            $this->messagesFacade->sendMessages($messages);

        } catch (\DibiException $e) {
            $this->presenter->flashMessage('Nepodařilo se odeslat upozornění příjemcům.', 'warning');
            $this->redirect('this');
        }

        $this->redirect('this');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $this['itemsTable']->setListingItems($this->listing->listingItems);

        $template->render();
    }
}