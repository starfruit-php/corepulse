<?php

namespace CorepulseBundle\Component\Field;

class Datetime extends Date
{
    public function format($value)
    {
        return $value?->format("Y/m/d H:i");
    }
}
