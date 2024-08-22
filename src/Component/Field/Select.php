<?php

namespace CorepulseBundle\Component\Field;

class Select extends Input
{
    public function getFrontEndType():string
    {
        return 'select';
    }
}
