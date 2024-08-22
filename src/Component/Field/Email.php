<?php

namespace CorepulseBundle\Component\Field;

class Email extends Input
{
    public function getFrontEndType():string
    {
        return 'email';
    }
}
