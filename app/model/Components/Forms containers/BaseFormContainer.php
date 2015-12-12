<?php

namespace App\Model\Components\Forms;

use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Application\UI\Presenter;
use Nette\Forms\Container;
use Nette\Forms\Form;

abstract class BaseFormContainer extends Container
{
    /** @var ITemplateFactory */
    private $templateFactory;

    /** @var ITemplate */
    private $template;



    public function __construct()
    {
        parent::__construct();
        $this->monitor('Nette\Forms\Form');
    }



    abstract public function render();



    abstract public function configure();



    protected function attached($obj)
    {
        parent::attached($obj);
        if ($obj instanceof Form) {
            $this->configure();
        }
    }



    public function setTemplateFactory(ITemplateFactory $templateFactory)
    {
        $this->templateFactory = $templateFactory;
    }



    public function getTemplate()
    {
        if ($this->template === null) {
            $value = $this->createTemplate();
            if (!$value instanceof ITemplate && $value !== NULL) {
                $class2 = get_class($value); $class = get_class($this);
                throw new \Nette\UnexpectedValueException("Object returned by $class::createTemplate() must be instance of Nette\\Application\\UI\\ITemplate, '$class2' given.");
            }
            $this->template = $value;
        }
        return $this->template;
    }



    protected function createTemplate()
    {
        $this->templateFactory = $this->templateFactory ?: $this->lookup(Presenter::class)->getTemplateFactory();
        return $this->templateFactory->createTemplate();
    }

}