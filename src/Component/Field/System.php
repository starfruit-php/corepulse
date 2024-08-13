<?php

namespace CorepulseBundle\Component\Field;

class System extends Input
{
    public function format($value)
    {
        if ($this->layout->type == 'modificationDate' || $this->layout->type == 'creationDate') {
            return date('Y/m/d', $value);
        }

        return $value;
    }
}
