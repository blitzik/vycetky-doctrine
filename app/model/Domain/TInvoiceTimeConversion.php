<?php

namespace App\Model\Domain\Entities;

trait TInvoiceTimeConversion
{
    public function toInvoiceTime($time)
    {
        return new \InvoiceTime($time);
    }

    public function fromInvoiceTime(\InvoiceTime $time)
    {
        return $time->getTime();
    }
}