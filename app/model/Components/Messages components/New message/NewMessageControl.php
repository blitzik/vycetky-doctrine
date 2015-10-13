<?php

namespace App\Model\Components;

use App\Forms\Fields\RecipientsSelectBoxFactory;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\UsersFacade;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\MessageLengthException;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class NewMessageControl extends BaseComponent
{
    /** @var IUsersRelationshipsRestrictionsControlFactory  */
    private $restrictionsControlFactory;

    /** @var RecipientsSelectBoxFactory  */
    private $recipientsSelectBoxFactory;

    /** @var MessagesFacade  */
    private $messagesFacade;

    /** @var UsersFacade  */
    private $usersFacade;

    /**  @var User */
    private $user;

    /** @var array */
    private $users;
    private $restrictedUsers;
    private $possibleRecipients = [];

    private $responseRecipient;

    public function __construct(
        User $user,
        UsersFacade $usersFacade,
        MessagesFacade $messagesFacade,
        RecipientsSelectBoxFactory $recipientsSelectBoxFactory,
        IUsersRelationshipsRestrictionsControlFactory $restrictionsControlFactory
    ) {
        $this->user = $user;
        $this->usersFacade = $usersFacade;
        $this->messagesFacade = $messagesFacade;
        $this->recipientsSelectBoxFactory = $recipientsSelectBoxFactory;
        $this->restrictionsControlFactory = $restrictionsControlFactory;
    }

    public function setResponseRecipient($recipient)
    {
        $this->responseRecipient = $recipient;
    }

    private function loadUsers()
    {
        if (empty($this->possibleRecipients)) {
            $this->restrictedUsers = $this->usersFacade->findRestrictedUsers($this->user);
            $this->users = $this->usersFacade->findAllUsers();
            $this->possibleRecipients = array_merge($this->users, $this->restrictedUsers);
        }
    }

    protected function createComponentNewMessageForm()
    {
        $this->loadUsers();

        $form = new Form();

        $form->addText('subject', 'Předmět', 35, 80)
            ->setRequired('Vyplňte prosím předmět zprávy.');

        $form->addTextArea('text', 'Zpráva', 65, 12)
                ->setRequired('Vyplňte prosím text zprávy.')
                ->addRule(Form::MAX_LENGTH, 'Zpráva může obsahovat maximálně %d znaků.', 2000);

        $form['recipients'] = $this->recipientsSelectBoxFactory
                                   ->create(
                                       $this->user,
                                       $this->possibleRecipients,
                                       $this->authorizator->isAllowed($this->user, 'message', 'send_to_multiple_recipients')
                                   );

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

        $recipients = is_array($values->recipients) ?
                      $values->recipients :
                      [$values->recipients];

        if (!$this->authorizator->isAllowed($this->user, 'message', 'sent_to_restricted_recipients')) {
            $s = $this->messagesFacade
                      ->canMessageBeSentTo(
                          $values->recipients,
                          $this->restrictedUsers,
                          $this->users
                      );
            if ($s === false) {
                $form->addError(
                    'Nelze odeslat zprávu vybranému příjemci.'
                );
                return;
            }
        }

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
            $this->user
        );

        if ($values['isSendAsAdmin']) {
            $message->sendByAuthorRole();
        }

        try {
            $this->messagesFacade
                 ->sendMessage($message, $recipients);

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

        $this->loadUsers();
        $canBeSent = $this->messagesFacade
                          ->canMessageBeSentTo(
                              $this->responseRecipient,
                              $this->restrictedUsers,
                              $this->users
                          );
        if (isset($this->responseRecipient) and $canBeSent) {
            $this['newMessageForm-recipients']->setDefaultValue($this->responseRecipient);
        }

        $template->render();
    }
}