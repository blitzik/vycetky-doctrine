<?php

namespace App\Model\Components;


class UsersRelationshipsRestrictionsControl extends BaseComponent
{
    /** @var array */
    private $usersBlockedByMe;

    /** @var array */
    private $usersBlockingMe;

    /** @var array */
    private $suspendedUsers;

    /** @var array */
    private $usersBlockingEachOther;

    public function __construct(
        array $usersBlockedByMe,
        array $usersBlockingMe,
        array $suspendedUsers
    ) {
        $this->usersBlockedByMe = $usersBlockedByMe;
        $this->usersBlockingMe = $usersBlockingMe;
        $this->suspendedUsers = $suspendedUsers;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $this->usersBlockingEachOther = array_intersect_key(
            $this->usersBlockedByMe,
            $this->usersBlockingMe
        );

        foreach ($this->usersBlockingEachOther as $id => $user) {
            unset($this->usersBlockedByMe[$id]);
            unset($this->usersBlockingMe[$id]);
        }

        foreach ($this->suspendedUsers as $id => $user) {
            unset($this->usersBlockedByMe[$id]);
            unset($this->usersBlockingMe[$id]);
            unset($this->usersBlockingEachOther[$id]);
        }

        $template->usersBlockedByMe = $this->usersBlockedByMe;
        $template->usersBlockingMe = $this->usersBlockingMe;
        $template->usersBlockingEachOther = $this->usersBlockingEachOther;
        $template->suspendedUsers = $this->suspendedUsers;

        $template->render();
    }
}