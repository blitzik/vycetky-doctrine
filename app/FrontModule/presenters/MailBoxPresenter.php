<?php

namespace App\FrontModule\Presenters;

use App\Model\MessagesHandlers\IReceivedUnreadMessagesHandlerFactory;
use App\Model\MessagesHandlers\IReceivedReadMessagesHandlerFactory;
use App\Model\MessagesHandlers\ISentMessagesHandlerFactory;
use App\Model\Components\IMessageDetailControlFactory;
use App\Model\Components\IMessagesTableControlFactory;
use App\Model\Components\INewMessageControlFactory;
use App\Model\MessagesHandlers\IMessagesHandler;
use App\Model\Query\MessageReferencesQuery;
use App\Model\Domain\Entities\Message;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\MessagesQuery;

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
    protected function createComponentNewMessage()
    {
        return $this->newMessageControlFactory->create($this->user->getIdentity());
    }

}