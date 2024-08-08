<?php

namespace CorepulseBundle\Component\Field;

class Input 
{
    public function getName()
    {
        return 'Input';
    }

    public function getTitle($item)
    {
        return $item->getTitle();
    }

    public function getType()
    {
        return 'input';
    }

    public function getValue($item)
    {
        return $item->getValue();
    }

}