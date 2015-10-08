<?php

namespace App\Model\Components;

use App\Model\Authorization\Authorizator;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\UsersFacade;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\MessageLengthException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\Arrays;

class NewMessageControl extends Control
{
    /**
     * @var UsersFacade
     */
    private $usersFacade;

    /**
     * @var Authorizator
     */
    private $authorizator;

    /**
     * @var MessagesFacade
     */
    private $messagesFacade;

    /**
     * @var IUsersRelationshipsRestrictionsControlFactory
     */
    private $restrictionsControlFactory;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $users;
    private $recipients;
    private $restrictedUsers;

    public function __construct(
        User $user,
        UsersFacade $usersFacade,
        Authorizator $authorizator,
        MessagesFacade $messagesFacade,
        IUsersRelationshipsRestrictionsControlFactory $restrictionsControlFactory
    ) {
        $this->user = $user;
        $this->usersFacade = $usersFacade;
        $this->authorizator = $authorizator;
        $this->messagesFacade = $messagesFacade;
        $this->restrictionsControlFactory = $restrictionsControlFactory;

        $this->restrictedUsers = $this->usersFacade->findRestrictedUsers($this->user);
        $this->users = $this->findUsers();

        $this->recipients = $this->prepareRecipientsForList(
            $this->restrictedUsers,
            $this->users
        );
    }

    private function prepareRecipientsForList(
        array $restrictedUsers,
        array $possibleRecipients
    ) {
        $recipients = [];
        if (!$this->authorizator->isAllowed($this->user, 'message', 'send_to_restricted_recipients')) {
            $recipients = array_diff_key(
                $possibleRecipients['activeUsers'],
                $possibleRecipients['suspendedUsers'],
                $restrictedUsers['usersBlockedByMe'],
                $restrictedUsers['usersBlockingMe']
            );
        } else {
            $recipients = $possibleRecipients['activeUsers'] +
                $possibleRecipients['suspendedUsers'] +
                $restrictedUsers['usersBlockedByMe'] +
                $restrictedUsers['usersBlockingMe'];
        }

        return Arrays::associate($recipients, 'id=username');
    }

    private function findUsers()
    {
        $recipients = $this->usersFacade
                           ->findAllUsers();

        unset(
            $recipients['suspendedUsers'][$this->user->getId()],
            $recipients['activeUsers'][$this->user->getId()]
        );

        return $recipients;
    }

    protected function createComponentNewMessageForm()
    {
        $form = new Form();

        $form->addText('subject', 'Předmět', 35, 80)
            ->setRequired('Vyplňte prosím předmět zprávy.');

        $form->addTextArea('text', 'Zpráva', 65, 12)
            ->setRequired('Vyplňte prosím text zprávy.')
            ->addRule(Form::MAX_LENGTH, 'Zpráva může obsahovat maximálně %d znaků.', 2000);

        $form->addMultiSelect('recipients', 'Příjemci', $this->recipients, 13)
                ->setRequired('Vyberte alespoň jednoho příjemce.');

        $form->addCheckbox('isSendAsAdmin', 'Odeslat zprávu jako správce aplikace');

        $form->addSubmit('send', 'Odeslat');

        $form->getElementPrototype()->id = 'new-message-form';

        $form->onSuccess[] = [$this, 'processNewMessageForm'];

        $form->addProtection();

        return $form;
    }

    public function processNewMessageForm(Form $form)
    {
        $values = $form->getValues();

        if ($values['isSendAsAdmin'] == true and
            !$this->authorizator->isAllowed($this->user, 'message', 'send_as_admin')) {
            $form->addError('Nemáte dostatečná oprávnění k akci.');
            return;
        }

        $texy = new \Texy();
        $texy->setOutputMode(\Texy::HTML4_TRANSITIONAL);
        $texy->encoding = 'utf-8';
        $texy->allowedTags = \Texy::ALL;

        $text = $texy->process($values->text);

        $message = new SentMessage(
            $values->subject,
            $text,
            $this->user,
            (bool)$values->isSendAsAdmin
        );

        try {
            $result = $this->messagesFacade
                           ->sendMessage($message, $values->recipients);

            //if (!$result->isValid()) {
            //    $er = $result->getFirstError();
            //    $form->addError($er['message']);
            //    return;
            //}

        } catch (MessageLengthException $ml) {
            $form->addError('Zprávu nelze uložit, protože je příliš dlouhá.');
            return;

        } catch (DBALException $e) {
            $this->flashMessage('Zpráva nemohla být odeslána. Zkuste akci opakovat později.', 'errror');
            $this->redirect('this');
        }

        $this->presenter->flashMessage('Zpráva byla úspěšně odeslána', 'success');
        $this->presenter->redirect('MailBox:sent');
    }

    protected function createComponentRelationshipsRestrictions()
    {
        $comp = $this->restrictionsControlFactory
                     ->create(
                         $this->restrictedUsers['usersBlockedByMe'],
                         $this->restrictedUsers['usersBlockingMe'],
                         $this->users['suspendedUsers']
                     );

        return $comp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');


        $template->render();
    }
}