<?php

namespace CorepulseBundle\Component\Field;

class Textarea
{
    public function getName()
    {
        return 'Textarea';
    }

    public function getTitle($item)
    {
        return $item->getTitle();
    }

    public function getType()
    {
        return 'textarea';
    }

    public function getValue($item)
    {
        return $item->getValue();
    }

}