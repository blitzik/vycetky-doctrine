<?php

namespace App\Model\Services;

use Nette\Object;

class LocalityService extends Object
{
    /**
     * @param array $localities
     * @return string
     */
    public function prepareTagsForAutocomplete(array $localities)
    {
        $tags = [];
        foreach ($localities as $locality) {
            $tags[] = $locality;
        }
        return $tags;
    }
}