<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use Tracy\Debugger;

class UserBlockingControl extends BaseComponent
{
    /** @var array */
    public $onBlockUser = [];

    /** @var array */
    public $onUnblockUser = [];

    /** @var array */
    public $onCloseAccount = [];

    /** @var array */
    public $onOpenAccount = [];

    /** @var UsersFacade  */
    private $usersFacade;

    /** @var int */
    private $userBeingBlockedID;

    /** @var User */
    private $userEntity;

    /** @var array */
    private $blockedUsersIDs = [];
    private $usersWithClosedAccountIDs = [];

    public function __construct(
        $userBeingBlockedId,
        User $userEntity,
        UsersFacade $usersFacade
    ) {
        $this->userBeingBlockedID = $userBeingBlockedId;
        $this->userEntity = $userEntity;
        $this->usersFacade = $usersFacade;
    }

    public function setAlreadyBlockedUsersIDs(array $usersIDs)
    {
        $this->blockedUsersIDs = $usersIDs;
    }

    public function setUsersWithClosedAccountIDs(array $usersIDs)
    {
        $this->usersWithClosedAccountIDs = $usersIDs;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->userEntity = $this->userEntity;
        $template->userBeingBlockedID = $this->userBeingBlockedID;

        $template->blockedUsersIDs = $this->blockedUsersIDs;
        $template->usersWithClosedAccountIDs = $this->usersWithClosedAccountIDs;

        $template->render();
    }

    /**
     * @param $id
     * @return User|null
     */
    private function getUser($id)
    {
        return $this->usersFacade
                    ->getUserByID($id);
    }

    /**
     * @secured
     */
    public function handleBlockUser($id)
    {
        try {
            $user = $this->getUser($id);
            if ($user !== null) {
                $this->userEntity->blockUser($user);
                $this->usersFacade->saveUser($this->userEntity);
                // add item with null value into array to toggle block button in template
                $this->blockedUsersIDs[$user->getId()] = null;
            }

            $this->refresh('userRestriction');
            $this->onBlockUser($this, $user);

        } catch (\Exception $e) {
            $this->flashMessage('Při blokaci uživatele nastala chyba.', 'error');
            $this->redirect('this');
        }
    }

    /**
     * @secured
     */
    public function handleUnblockUser($id)
    {
        try {
            $user = $this->getUser($id);
            if ($user !== null) {
                $this->userEntity->unblockUser($user);
                $this->usersFacade->saveUser($this->userEntity);
            }

            $this->refresh('userRestriction');
            $this->onUnblockUser($this, $user);

        } catch (\Exception $e) {
            $this->flashMessage('Při pokusu o odblokování uživatele nastala chyba.', 'error');
            $this->redirect('this');
        }
    }

    /**
     * @secured
     */
    public function handleCloseAccount($id)
    {
        $user = $this->toggleAccountAccessibility($id);

        $this->usersWithClosedAccountIDs[$user->getId()] = null;

        $this->refresh('accountRestriction');
        $this->onCloseAccount($this, $user);
    }

    /**
     * @secured
     */
    public function handleOpenAccount($id)
    {
        $user = $this->toggleAccountAccessibility($id);

        $this->refresh('accountRestriction');
        $this->onOpenAccount($this, $user);
    }

    private function refresh($snippet = null)
    {
        if ($this->presenter->isAjax()) {
            $this->redrawControl($snippet);
        } else {
            $this->redirect('this');
        }
    }

    private function toggleAccountAccessibility($id)
    {
        try {
            $this->checkPermission($id);
            $user = $this->getUser($id);
            if ($user !== null) {
                $user->toggleAccessibility();
                $this->usersFacade->saveUser($user);
            } else {
                $this->redirect('this');
            }

            return $user;
        } catch (\Exception $e) {
            $this->flashMessage('Nastala uzavírání/otevírání účtu nastala chyba.', 'error');
            $this->redirect('this');
        }
    }

    private function checkPermission($userBeingProcessedID)
    {
        if (!$this->presenter->user->isAllowed('users_overview', 'suspend_user')) {
            Debugger::log(
                'User: ' .$this->userEntity->username. ' tried to close Account
                 of user with ID = ' .$userBeingProcessedID);
            $this->presenter->flashMessage('Nemáte dostatečná oprávnění k provedení akce.', 'warning');
            $this->redirect('this');
        }
    }
}