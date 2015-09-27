<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IMessageDetailControlFactory;
use App\Model\Components\IMessagesTableControlFactory;
use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\MessageReference;
use App\Model\Facades\MessagesFacade;
use App\Model\MessagesHandlers\IMessagesHandler;
use App\Model\MessagesHandlers\IReceivedReadMessagesHandlerFactory;
use App\Model\MessagesHandlers\IReceivedUnreadMessagesHandlerFactory;
use App\Model\MessagesHandlers\ISentMessagesHandlerFactory;
use App\Model\Query\MessageReferencesQuery;
use App\Model\Query\MessagesQuery;
use Exceptions\Runtime\MessageLengthException;
use Nette\Application\UI\Form;

class MailBoxPresenter extends SecurityPresenter
{
    /**
     * @var IReceivedUnreadMessagesHandlerFactory
     * @inject
     */
    public $receivedUnreadMessagesHandlerFactory;

    /**
     * @var IReceivedReadMessagesHandlerFactory
     * @inject
     */
    public $receivedReadMessagesHandlerFactory;

    /**
     * @var ISentMessagesHandlerFactory
     * @inject
     */
    public $sentMessagesHandlerFactory;

    /**
     * @var IMessagesTableControlFactory
     * @inject
     */
    public $messagesTableControlFactory;

    /**
     * @var IMessageDetailControlFactory
     * @inject
     */
    public $messageDetailFactory;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messagesFacade;

    /**
     * @var IMessagesHandler
     */
    private $messagesHandler;

    /**
     * @var Message
     */
    private $message;


    /*
     * -----------------------------
     * ----- RECEIVED MESSAGES -----
     * -----------------------------
     */


    public function actionReceivedUnread()
    {
        $this->messagesHandler = $this->receivedUnreadMessagesHandlerFactory
                                      ->create($this->user->getIdentity());
    }

    public function renderReceivedUnread()
    {

    }

    public function actionReceivedRead()
    {
        $this->messagesHandler = $this->receivedReadMessagesHandlerFactory
                                      ->create($this->user->getIdentity());
    }

    public function renderReceivedRead()
    {

    }

    /*
     * -------------------------
     * ----- SENT MESSAGES -----
     * -------------------------
     */


    public function actionSent()
    {
        $this->messagesHandler = $this->sentMessagesHandlerFactory
                                      ->create($this->user->getIdentity());
    }

    public function renderSent()
    {

    }

    /**
     * @Actions receivedUnread, receivedRead, sent
     */
    public function createComponentMessagesTable()
    {
        $comp = $this->messagesTableControlFactory
                     ->create($this->messagesHandler);

        return $comp;
    }


    /*
     * --------------------------
     * ----- MESSAGE DETAIL -----
     * --------------------------
     */


    public function actionMessage($id, $type)
    {
        $user = $this->user->getIdentity();

        if ($type === Message::SENT) {
            $this->message = $this->messagesFacade
                                  ->fetchMessage(
                                      (new MessagesQuery())
                                      ->byId($id)
                                      ->byAuthor($user)
                                  );
        } else if ($type === Message::RECEIVED) {
            $reference = $this->messagesFacade
                                  ->fetchMessageReference(
                                      (new MessageReferencesQuery())
                                      ->includingMessage()
                                      ->includingMessageAuthor(['id', 'username'])
                                      ->byMessage($id)
                                      ->byRecipient($user)
                                  );

            $this->message = $reference === null ? null : $reference->getMessage();
        } else {
            $this->redirect('MailBox:receivedUnread');
        }

        if ($this->message === null) {
            $this->flashMessage('Zpráva nebyla nalezena.', 'warning');
            $this->redirect('MailBox:receivedUnread');
        }
    }

    public function renderMessage($id)
    {
        $this->template->message = $this->message;
    }

    /**
     * @Actions message
     */
    protected function createComponentMessageDetail()
    {
        $comp = $this->messageDetailFactory->create($this->message);

        return $comp;
    }


    /*
     * -----------------------
     * ----- NEW MESSAGE -----
     * -----------------------
     */


    public function actionNewMessage()
    {

    }

    public function renderNewMessage()
    {

    }

    /**
     * @Actions newMessage
     */
    protected function createComponentNewMessageForm()
    {
        $form = new Form();

        $form->addText('subject', 'Předmět', 35, 80)
                ->setRequired('Vyplňte prosím předmět zprávy.');

        $form->addTextArea('message', 'Zpráva', 50, 12)
                ->setRequired('Vyplňte prosím text zprávy.')
                ->addRule(Form::MAX_LENGTH, 'Zpráva může obsahovat maximálně %d znaků.', 2000);

        $form->addMultiSelect('receivers', 'Příjemci',
                              $this->usersFacade
                                   ->findAllUsers([$this->user->id]), 13)
                ->setRequired('Vyberte alespoň jednoho příjemce.');

        $form->addCheckbox('isSystemMessage', 'Odeslat jako systémovou zprávu');

        $form->addSubmit('send', 'Odeslat');

        $form->getElementPrototype()->id = 'new-message-form';

        $form->onSuccess[] = $this->processNewMessageForm;

        return $form;
    }

    public function processNewMessageForm(Form $form)
    {
        $values = $form->getValues();

        $texy = new \Texy();
        $texy->setOutputMode(\Texy::HTML4_TRANSITIONAL);
        $texy->encoding = 'utf-8';
        $texy->allowedTags = \Texy::ALL;

        $text = $texy->process($values->message);

        // 0 == system account
        $author = $values['isSystemMessage'] == true ? 0 : $this->user->id;
        try {
            $this->messagesFacade
                 ->sendMessage(
                     $values->subject,
                     $text,
                     $author,
                     $values->receivers
                 );

        } catch (MessageLengthException $ml) {
            $form->addError('Zprávu nelze uložit, protože je příliš dlouhá.');
            return;

        } catch (\DibiException $e) {
            $this->flashMessage('Zpráva nemohla být odeslána. Zkuste akci opakovat později.', 'errror');
            $this->redirect('this');
        }

        $this->flashMessage('Zpráva byla úspěšně odeslána', 'success');
        $this->redirect('MailBox:sent');
    }


    protected function createTemplate()
    {
        $template = parent::createTemplate();

        $template->registerHelper('texy', function($text) {

            $texy = new \Texy();
            $texy->setOutputMode(\Texy::HTML4_TRANSITIONAL);
            $texy->encoding = 'utf-8';
            $texy->allowedTags = array(
                'strong' => \Texy::NONE,
                'b' => \Texy::NONE,
                'a' => array('href'),
                'em' => \Texy::NONE,
                'p' => \Texy::NONE,
            );
            //$texy->allowedTags = \Texy::NONE;

            return $texy->process($text);

        });

        return $template;
    }

}