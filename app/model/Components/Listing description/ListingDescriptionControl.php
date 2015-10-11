<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use Nette\Application\LinkGenerator;

class ListingDescriptionControl extends BaseComponent
{
    /** @var LinkGenerator */
    private $linkGenerator;

    /** @var Listing */
    private $listing;

    private $link;
    private $isClickable = false;

    public function __construct(
        Listing $listing,
        LinkGenerator $linkGenerator
    ) {
        $this->listing = $listing;
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

        $template->listing = $this->listing;
        $template->link = $this->link;
        $template->isClickable = $this->isClickable;

        $template->render();
    }
}