<?php

namespace CorepulseBundle\Component\Field;

class Date extends Input
{
    public function getValue()
    {
        $value = $this->data->{'get' . ucfirst($this->getName())}();

        $format = $value ? date('d/m/Y', $value->timestamp) : null;

        return $format;
    }
}
