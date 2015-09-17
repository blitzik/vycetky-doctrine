<?php

namespace App\Model\Components;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Control;

class ListingDescriptionControl extends Control
{
    /**
     * @var LinkGenerator
     */
    private $linkGenerator;

    /**
     * @var \DateTime
     */
    private $period;

    /**
     * @var string
     */
    private $description;

    private $link;
    private $isClickable = false;

    public function __construct(
        \DateTime $period,
        $description,
        LinkGenerator $linkGenerator
    ) {
        $this->period = $period;
        $this->description = $description;
        $this->linkGenerator = $linkGenerator;
    }

    /**
     * @param $destination
     * @param array $params
     */
    public function setAsClickable($destination, array $params = [])
    {
        $this->link = $this->linkGenerator->link($destination, $params);
        $this->isClickable = true;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->period = $this->period;
        $template->description = $this->description;
        $template->link = $this->link;
        $template->isClickable = $this->isClickable;

        $template->render();
    }
}