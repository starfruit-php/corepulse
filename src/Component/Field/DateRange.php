<?php

namespace CorepulseBundle\Component\Field;

class DateRange extends Input
{
    public function getValue()
    {
        $value = $this->data->{'get' . ucfirst($this->getName())}();

        $format = $value?->getStartDate()?->format("Y/m/d") . " - " . $value?->getEndDate()?->format("Y/m/d");

        return $format;
    }
}
