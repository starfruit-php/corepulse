<?php

namespace CorepulseBundle\Component\Field;

class Password extends Input
{
    public function getFrontEndType(): string
    {
        return 'password';
    }
}
