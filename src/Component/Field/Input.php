<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Component\Field\FieldInterface;

class Input implements FieldInterface
{
    protected $data;

    protected $layout;

    public function __construct($data, $layout)
    {
        if (is_array($layout)) $layout = (object)$layout;

        $this->layout = $layout;
        $this->data = $data;
    }

    public function getName():string
    {
        return $this->layout->name;
    }

    public function getTitle():string
    {
        return $this->layout->title;
    }

    public function getValue()
    {
        return $this->format($this->data->{'get' . ucfirst($this->getName())}());
    }

    public function format($value)
    {
        return $value;
    }
}
