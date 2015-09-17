<?php

namespace App\Model\Repositories;

trait TRepositoryModifiers
{
    private function queryPeriodModifier($query, $year, $month, $alias = null)
    {
        $as = $alias === null ? '' : $alias.'.';
        if ($month === null) {
            $query->where($as.'period BETWEEN ? AND ?', $year.'-01-01', $year.'-12-31');
        } else {
            $query->where($as.'period = ?', $year.'-'.$month.'-01');
        }
    }
}