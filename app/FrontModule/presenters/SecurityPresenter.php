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

    /**
     * @var Model\Authorization\Authorizator
     */
    protected $authorizator;

    public function setAuthorizator(Model\Authorization\Authorizator $authorizator)
    {
        $this->authorizator = $authorizator;
        $this->user->setAuthorizator($authorizator);
    }

    protected function startup() {
        if (!$this->getUser()->isLoggedIn()) {
            if ($this->getUser()->getLogoutReason() == Nette\Security\IUserStorage::INACTIVITY) {
                $this->flashMessage(
                    'Byl jste odhlášen z důvodu neaktivity. Přihlašte se prosím znovu.'
                );
            }
            $this->redirect(':User:Login:default');
        }

        if (!$this->user->getIdentity()->isUserAccountAccessible()) {
            $this->flashMessage(
                'Váš účet byl uzavřen.
                 Pro více informací kontaktujte správce aplikace na adrese:
                 vycetkovy-system@alestichava.cz', 'warning'
            );
            $this->user->logout(true);
            $this->redirect(':User:Login:default');
        }

        $this->currentDate = new \DateTime();

        parent::startup();
    }

    public function checkRequirements($element)
    {
        if (!$this->authorizator->isAllowed($this->user->getIdentity(), $this->name, $this->action)
            || ($this->signal !== NULL && !$this->authorizator->isAllowed($this->user->getIdentity(), $this->name, $this->formatSignalString()))) {
            throw new Nette\Application\ForbiddenRequestException;
        }

        parent::checkRequirements($element);
    }


    protected function formatSignalString()
    {
        return $this->signal === NULL ? NULL : ltrim(implode('-', $this->signal), '-') . '!';
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

        $this->template->currentDate = $this->currentDate;
        $this->template->authorizator = $this->authorizator;
    }

    public function handleLogout()
    {
        $this->user->logout();
        $this->redirect(':User:Login:default');
    }
}