<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use App\Model\Query\UsersQuery;
use Doctrine\ORM\AbstractQuery;
use Nette\Application\UI\Control;
use Nette\Application\UI\Multiplier;
use Nette\Utils\Arrays;
use Nextras\Application\UI\SecuredLinksControlTrait;

class UsersOverviewControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var IUserBlockingControlFactory
     */
    protected $userBlockingControlFactory;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var UsersFacade
     */
    protected $usersFacade;

    /**
     * @var UsersQuery
     */
    protected $usersQuery;

    /**
     * @var array
     */
    protected $users = [];

    /**
     * @var array
     */
    protected $alreadyBlockedUsers = [];

    public function __construct(
        User $user,
        UsersFacade $usersFacade,
        IUserBlockingControlFactory $userBlockingControlFactory
    ) {
        $this->user = $user;
        $this->usersFacade = $usersFacade;
        $this->userBlockingControlFactory = $userBlockingControlFactory;

        $this->usersQuery = (new UsersQuery())->onlyWithFields(['id', 'username']);
    }

    protected function createComponentUserBlocking()
    {
        return new Multiplier(function ($userId) {
            $comp = $this->userBlockingControlFactory
                ->create($userId, $this->user);

            $comp->setAlreadyBlockedUsersIDs($this->alreadyBlockedUsers);
            $comp->onBlockUser[] = [$this, 'onBlockUser'];
            $comp->onUnblockUser[] = [$this, 'onUnblockUser'];

            return $comp;
        });
    }

    public function onBlockUser(UserBlockingControl $control, User $user = null)
    {
        // in Ajax request we do NOT want to query database for users
        // so we create 1 item in these Arrays in order to NOT query database
        // (see IF construct in render method)
        $this->users[] = null;
        $this->alreadyBlockedUsers[] = null;
    }

    public function onUnblockUser(UserBlockingControl $control, User $user = null)
    {
        $this->users[] = null;
        $this->alreadyBlockedUsers[] = null;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        if (empty($this->users)) {
            $this->users = Arrays::associate($this->usersFacade
                ->fetchUsers($this->usersQuery)
                ->toArray(AbstractQuery::HYDRATE_ARRAY), 'id=username');

            unset($this->users[$this->user->getId()]);
        }

        $template->users = $this->users;

        if (empty($this->alreadyBlockedUsers)) {
            $alreadyBlockedUsers = Arrays::associate($this->usersFacade->fetchUsers(
                (new UsersQuery())
                ->onlyWithFields(['id'])
                ->findUsersBlockedByMe($this->user)
            )->toArray(AbstractQuery::HYDRATE_ARRAY), 'id');
            $this->alreadyBlockedUsers = $alreadyBlockedUsers;
        }

        $template->render();
    }
}