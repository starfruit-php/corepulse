<?php

namespace CorepulseBundle\Component\Field;

class Datetime extends Input
{
    public function getValue()
    {
        $value = $this->data->{'get' . ucfirst($this->getName())}();

        return $value?->format("Y/m/d H:i");
    }
}
