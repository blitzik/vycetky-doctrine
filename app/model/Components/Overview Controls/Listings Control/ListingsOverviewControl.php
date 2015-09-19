<?php

namespace App\Model\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use App\Model\Domain\Entities\Listing;
use Nette\Application\UI\Multiplier;
use Nette\Application\UI\Control;
use Nextras\Datagrid\Datagrid;
use blitzik\Arrays\Arrays;
use Nette\Security\User;

class ListingsOverviewControl extends Control
{
    use SecuredLinksControlTrait;
    use TOverviewParametersValidators;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Listing[]
     */
    private $listings;

    /**
     * @var string
     */
    private $heading;


    public function __construct(
        User $user
    ) {
        $this->user = $user;
    }

    /**
     * @param array $listings
     */
    public function setListings(array $listings)
    {
        foreach ($listings as $listing) {
            $this->listings[$listing['month']][] = (object) $listing;
        }
    }

    protected function createComponentDataGrid()
    {
        return new Multiplier(function ($month) {
            $grid = new Datagrid();

            $grid->addColumn('year', 'Rok');
            $grid->addColumn('month', 'Měsíc');
            $grid->addColumn('description', 'Popis');
            $grid->addColumn('worked_days', 'Dny');
            $grid->addColumn('total_worked_hours', 'Hodiny');

            $grid->setDataSourceCallback(function ($filter, $order) use ($month) {
                return $this->listings[$month];
            });

            $grid->addCellsTemplate(__DIR__ . '/templates/grid/grid.latte');

            return $grid;
        });
    }

    /* * * * * * OPTIONS * * * * * */

    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        if ($this->presenter->getParameter('month') !== null) {
            $this->template->date = new \DateTime($this->presenter->getParameter('year') . '-' .
                                                  $this->presenter->getParameter('month') . '-01');
        }

        $template->heading = $this->heading;
        $template->numberOfListings = isset($this->listings) ? Arrays::count_recursive($this->listings, 1) : 0;
        $template->listings = $this->listings;

        $template->render();
    }
}