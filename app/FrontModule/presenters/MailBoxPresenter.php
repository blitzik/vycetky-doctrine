<?php

namespace App\FrontModule\Presenters;

use App\Model\MessagesHandlers\IReceivedUnreadMessagesHandlerFactory;
use App\Model\MessagesHandlers\IReceivedReadMessagesHandlerFactory;
use App\Model\MessagesHandlers\ISentMessagesHandlerFactory;
use App\Model\Components\IMessageDetailControlFactory;
use App\Model\Components\IMessagesTableControlFactory;
use App\Model\Components\INewMessageControlFactory;
use App\Model\MessagesHandlers\IMessagesHandler;
use Exceptions\Runtime\MessageTypeException;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\IMessage;
use App\Model\Facades\MessagesFacade;

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
     * @var INewMessageControlFactory
     * @inject
     */
    public $newMessageControlFactory;

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

    /** @var IMessagesHandler */
    private $messagesHandler;

    /** @var IMessage */
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

        try {
            $this->message = $this->messagesFacade->readMessage($id, $type, $user);
        } catch (MessageTypeException $e) {
            $this->redirect('MailBox:receivedUnread');
        }

        if ($this->message === null or
            !$this->authorizator->isAllowed($user, $this->message, 'view')) {
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
    protected function createComponentNewMessage()
    {
        return $this->newMessageControlFactory->create($this->user->getIdentity());
    }

}