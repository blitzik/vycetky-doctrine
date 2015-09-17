<?php

namespace App\Model\Components;

use Exceptions\Logic\InvalidArgumentException;

trait TOverviewParametersValidators
{
    /**
     * @param array $filterParams
     */
    private function checkFilterParameters(array $filterParams)
    {
        if (!isset($filterParams['year']) or !ctype_digit($filterParams['year'])) {
            throw new InvalidArgumentException(
                'Argument $filterParam must contain index "year" and be
                 integer number.'
            );

        } else {
            if (isset($filterParams['month'])) {
                if (!ctype_digit($filterParams['month'])) {
                    throw new InvalidArgumentException(
                        'Index "month" of argument $filterParam must be integer number. '
                    );
                }
            }
        }
    }
}