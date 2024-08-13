<?php

namespace CorepulseBundle\Component\Field;

class DateRange extends Date
{
    public function format($value)
    {
        $format = $value?->getStartDate()?->format("Y/m/d") . " - " . $value?->getEndDate()?->format("Y/m/d");

        return $format;
    }
}
