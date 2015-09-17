<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IMessagesTableControlFactory;
use MessagesLoaders\ReceivedUnreadMessagesLoader;
use Exceptions\Runtime\MessageNotFoundException;
use MessagesLoaders\ReceivedReadMessagesLoader;
use Exceptions\Runtime\MessageLengthException;
use MessagesLoaders\SentMessagesLoader;
use App\Model\Facades\MessagesFacade;
use MessagesLoaders\IMessagesLoader;
use App\Model\Entities\UserMessage;
use App\Model\Facades\UserManager;
use App\Model\Entities\Message;
use Nette\Application\UI\Form;

class MailBoxPresenter extends SecurityPresenter
{
    /**
     * @var ReceivedUnreadMessagesLoader
     * @inject
     */
    public $receivedUnreadMessagesLoader;

    /**
     * @var ReceivedReadMessagesLoader
     * @inject
     */
    public $receivedReadMessagesLoader;

    /**
     * @var SentMessagesLoader
     * @inject
     */
    public $sentMessagesLoader;

    /**
     * @var IMessagesTableControlFactory
     * @inject
     */
    public $messagesTableControlFactory;

    /**
     * @var UserManager
     * @inject
     */
    public $userManager;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messagesFacade;

    /**
     * @var UserMessage
     */
    private $message;

    /**
     * @var IMessagesLoader
     */
    private $loader;


    /**
     * 		RECEIVED MESSAGES
     */

    public function actionReceivedUnread()
    {
        $this->loader = $this->receivedUnreadMessagesLoader;
    }

    public function renderReceivedUnread()
    {

    }

    public function actionReceivedRead()
    {
        $this->loader = $this->receivedReadMessagesLoader;
    }

    public function renderReceivedRead()
    {

    }

    /**
     * 		SENT MESSAGES
     */

    public function actionSent()
    {
        $this->loader = $this->sentMessagesLoader;
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
                     ->create($this->loader);

        return $comp;
    }

    /**
     * 		MESSAGE
     */
    public function actionMessage($id, $type)
    {
        if ($type === Message::SENT) $t = Message::SENT;
        elseif ($type === Message::RECEIVED) $t = Message::RECEIVED;
        else $this->redirect('MailBox:receivedUnread');

        try {
            $this->message = $this->messagesFacade
                                  ->getMessage($id, $this->user->id, $t);

        } catch (MessageNotFoundException $me){
            $this->flashMessage('Zpráva nebyla nalezena.', 'error');
            $this->redirect('MailBox:receivedUnread');
        }
    }


    public function renderMessage($id)
    {
        $this->template->message = $this->message;
    }


    /**
     * 		MESSAGE
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
                              $this->userManager
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