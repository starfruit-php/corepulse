<?php

namespace CorepulseBundle\Component\Field;

class Link extends Input
{
    public function format($value)
    {
        if ($value) {
            return $value->getObjectVars();
        }

        return null;
    }
}
