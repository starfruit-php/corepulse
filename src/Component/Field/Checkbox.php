<?php

namespace CorepulseBundle\Component\Field;

class Checkbox extends Input
{
    public function getFrontEndType():string
    {
        return 'boolean';
    }
}
