<?php

namespace App\Model\Components;

use App\Forms\Fields\RecipientsSelectBoxFactory;
use App\Model\Components\ItemsTable\IItemsTableControlFactory;
use App\Model\Domain\Entities\User;
use App\Model\Notifications\SharedListingNotification;
use App\Model\ResultObjects\ListingResult;
use Doctrine\DBAL\DBALException;
use App\Model\Facades\ListingsFacade;
use App\Model\Facades\UsersFacade;
use App\Model\Domain\Entities\Listing;
use Exceptions\Runtime\RecipientsNotFoundException;
use Nette\Application\UI\Form;

class SharingListingControl extends BaseComponent
{
    /** @var SharedListingNotification  */
    private $sharedListingNotification;

    /** @var IItemsTableControlFactory  */
    private $itemsTableControlFactory;

    /** @var RecipientsSelectBoxFactory  */
    private $recipientsSelectBoxFactory;

    /** @var ListingsFacade  */
    private $listingFacade;

    /** @var UsersFacade  */
    private $usersFacade;

    /** @var User  */
    private $user;

    /** @var ListingResult  */
    private $listingResult;

    /** @var Listing  */
    private $listing;

    /** @var array  */
    private $users = [];

    /** @var array */
    private $restrictedUsers = [];


    public function __construct(
        ListingResult $listingResult,
        RecipientsSelectBoxFactory $recipientsSelectBoxFactory,
        SharedListingNotification $sharedListingNotification,
        IItemsTableControlFactory $itemsTableControlFactory,
        //MessagesFacade $messagesFacade,
        ListingsFacade $listingFacade,
        UsersFacade $usersFacade
    ) {
        $this->listingResult = $listingResult;
        $this->listing = $listingResult->getListing();
        $this->user = $listingResult->getListing()->getUser();

        $this->recipientsSelectBoxFactory = $recipientsSelectBoxFactory;
        $this->sharedListingNotification = $sharedListingNotification;
        $this->itemsTableControlFactory = $itemsTableControlFactory;
        $this->listingFacade = $listingFacade;
        $this->usersFacade = $usersFacade;

        $this->restrictedUsers = $this->usersFacade->findRestrictedUsers($this->user);
        $this->users = $this->usersFacade->findAllUsers();
    }

    protected function createComponentItemsTable()
    {
        $comp = $this->itemsTableControlFactory->create($this->listingResult);
        $comp->showTableCaption(
            'Front:Listing:detail',
            ['id' => $this->listing->getId()]
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

        $form['recipient'] = $this->recipientsSelectBoxFactory
                                   ->create(
                                       $this->user,
                                       array_merge($this->users, $this->restrictedUsers)
                                   );
        $form['recipient']->setAttribute('size', 10);

        $form->addSubmit('send', 'Odeslat výčetku');

        $form->onSuccess[] = [$this, 'processListingSharing'];
        //$form->onSuccess[] = [$this, 'sendNotifications'];

        return $form;
    }

    public function processListingSharing(Form $form, $values)
    {
        $ignoredDays = $form->getHttpData(Form::DATA_TEXT, 'items[]');
        if (count($ignoredDays) == $this->listingResult->getWorkedDays()) {
            $form->addError(
                'Nelze odeslat prázdnou výčetku!
                 Nezapomeňte, zaškrtnutím se řádek výčetky nebude sdílet.'
            );
            return;
        }

        try {
            $resultObject = $this->listingFacade->shareListing(
                $this->listing,
                $values['recipient'],
                $values['description'],
                $ignoredDays
            );

            $this->presenter->flashMessage('Výčetka byla úspěšně sdílena.', 'success');

            if (!$resultObject->hasNoErrors()) {
                $err = $resultObject->getFirstError();
                $this->flashMessage($err['message'], $err['type']);
            }

        } catch (RecipientsNotFoundException $rnf) {
            $form->addError(
                'Nelze zaslat výčetku vybranému uživateli.'
            );
            return;

        } catch (DBALException $e) {
            $form->addError(
                'Nastala chyba při pokusu o sdílení výčetky.
                 Zkuste akci opakovat později.'
            );
        }

        $this->redirect('this');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $template->render();
    }
}