<?php

namespace CorepulseBundle\Component\Field;

class Date extends Input
{
    public function format($value)
    {
        return $value?->format("Y/m/d");
    }

    public function getFrontEndType():string
    {
        return 'date';
    }
}
