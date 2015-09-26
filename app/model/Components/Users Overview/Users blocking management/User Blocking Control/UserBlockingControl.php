<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use App\Model\Query\UsersQuery;
use Nette\Application\UI\Control;
use Nextras\Application\UI\SecuredLinksControlTrait;

class UserBlockingControl extends Control
{
    use SecuredLinksControlTrait;

    /** @var array  */
    public $onBlockUser = [];

    /** @var array  */
    public $onUnblockUser = [];

    /**
     * @var UsersFacade
     */
    private $usersFacade;

    /**
     * @var int
     */
    private $userBeingBlockedId;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $blockedUsersIDs = [];

    public function __construct(
        $userBeingBlockedId,
        User $user,
        UsersFacade $usersFacade
    ) {
        $this->userBeingBlockedId = $userBeingBlockedId;
        $this->user = $user;
        $this->usersFacade = $usersFacade;
    }

    public function setAlreadyBlockedUsersIDs(array $usersIDs)
    {
        $this->blockedUsersIDs = $usersIDs;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->user = $this->user;
        $template->userBeingBlockedId = $this->userBeingBlockedId;

        $template->blockedUsersIDs = $this->blockedUsersIDs;

        $template->render();
    }

    private function getUser($id)
    {
        return $this->usersFacade
                    ->fetchUser(
                        (new UsersQuery())->byId($id)
                    );
    }

    /**
     * @secured
     */
    public function handleBlockUser($id)
    {
        $user = $this->getUser($id);
        if ($user !== null) {
            $this->user->blockUser($user);
            $this->usersFacade->saveUser($this->user);
            $this->blockedUsersIDs[$user->getId()] = null;
        }

        $this->refresh();
        $this->onBlockUser($this, $user);
    }

    /**
     * @secured
     */
    public function handleUnblockUser($id)
    {
        $user = $this->getUser($id);
        if ($user !== null) {
            $this->user->unblockUser($user);
            $this->usersFacade->saveUser($this->user);
        }

        $this->refresh();
        $this->onUnblockUser($this, $user);
    }

    private function refresh()
    {
        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }
}