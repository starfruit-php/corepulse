<?php

namespace CorepulseBundle\Component\Field;

class Wysiwyg extends Input
{
    public function getFrontEndType():string
    {
        return 'wysiwyg';
    }
}
