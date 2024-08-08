<?php

namespace CorepulseBundle\Component\Field;

class Wysiwyg
{
    public function getName()
    {
        return 'Wysiwyg';
    }

    public function getTitle($item)
    {
        return $item->getTitle();
    }

    public function getType()
    {
        return 'Wysiwyg';
    }

    public function getValue($item)
    {
        return $item->getValue();
    }

}