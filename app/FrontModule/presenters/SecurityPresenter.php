<?php

namespace App\FrontModule\Presenters;

use App\Model;
use Nette;

abstract class SecurityPresenter extends Nette\Application\UI\Presenter
{
    use \Nextras\Application\UI\SecuredLinksPresenterTrait;

    /**
     * @var \DateTime
     */
    protected $currentDate;

    protected function startup() {
        parent::startup();
        //$this->user->logout();

        if (!$this->getUser()->isLoggedIn()) {

            if ($this->getUser()->getLogoutReason() == Nette\Security\IUserStorage::INACTIVITY) {
                $this->flashMessage(
                    'Byl jste odhlášen z důvodu neaktivity. Přihlašte se prosím znovu.'
                );
            }
            $this->redirect(':User:Account:default');
        }

        $this->currentDate = new \DateTime();
    }

    protected function createComponent($name)
    {
        $ucname = ucfirst($name);
        $method = 'createComponent' . $ucname;

        $presenterReflection = $this->getReflection();
        if ($presenterReflection->hasMethod($method)) {
            $methodReflection = $presenterReflection->getMethod($method);
            $this->checkRequirements($methodReflection);

            if ($methodReflection->hasAnnotation('Actions')) {
                $actions = explode(',', $methodReflection->getAnnotation('Actions'));
                foreach ($actions as $key => $action) {
                    $actions[$key] = trim($action);
                }

                if (!empty($actions) and !in_array($this->getAction(), $actions)) {
                    throw new Nette\Application\ForbiddenRequestException("Creation of component '$name' is forbidden for action '$this->action'.");
                }
            }
        }

        return parent::createComponent($name);
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->template->username = $this->getUser()->getIdentity()->username;
        $this->template->currentDate = $this->currentDate;
    }

    public function handleLogout()
    {
        $this->user->logout();
        $this->redirect(':User:Account:default');
    }
}