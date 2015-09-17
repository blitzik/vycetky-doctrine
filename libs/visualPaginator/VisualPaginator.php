<?php

namespace Components;

use Nette\Application\UI\Control,
    Nette\Utils\Paginator;

    class VisualPaginator extends Control
    {

        /** @var Paginator */
        private $paginator = NULL;

        /** @persistent */
        public $page = 1;

	    private $counter = TRUE;
	    private $borderPages = TRUE;


        public function hideCounter()
        {
            $this->counter = FALSE;
        }


        public function setpage($page)
        {
            $this->page = $page;
            $this->getPaginator()->setPage($page);
        }

        public function hideBorderPagesButton()
        {
            $this->borderPages = FALSE;
        }

        /**
         *
         * @return Paginator
         */
        public function getPaginator()
        {
            if ($this->paginator == NULL) {
                $this->paginator = new Paginator;
            }

            return $this->paginator;
        }

        /**
         * Renders paginator
         * @return Void
         */
        public function render()
        {
            $paginator = $this->getPaginator();

            $this->template->paginator = $paginator;

		    $this->template->counter = $this->counter;
		    $this->template->borderPages = $this->borderPages;

            $this->template->setFile(dirname(__FILE__) . '/template.latte');
            $this->template->render();
        }

        public function loadState(array $params)
        {
            parent::loadState($params);

            $this->getPaginator()->page = $this->page;
        }

    }