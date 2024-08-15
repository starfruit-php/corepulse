<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Component\Field\FieldInterface;
use Pimcore\Model\DataObject\Data\BlockElement;

class Input implements FieldInterface
{
    const SYSTEM_CONVERT_DATE = ['creationDate', 'modificationDate'];

    protected $data;

    protected $layout;

    public function __construct($data, $layout = null)
    {
        if (is_array($layout)) $layout = (object)$layout;

        $this->layout = $layout;
        $this->data = $data;
    }

    public function getName():string
    {
        if (!$this->layout) {
            return $this->data->getName();
        }

        return $this->layout->name;
    }

    public function getTitle():string
    {
        if (!$this->layout) {
            return $this->data->getTitle();
        }

        return $this->layout->title;
    }

    public function getValue()
    {
        if (!$this->layout) {
            return $this->formatDocument($this->data->getData());
        }

        if ($this->data instanceof BlockElement) {
            return $this->formatBlock($this->data->getData());
        }
      
        return $this->format($this->data->{'get' . ucfirst($this->getName())}());
    }

    public function format($value)
    {
        return $value;
    }

    public function formatDocument($value)
    {
        return $value;
    }

    public function formatBlock($value) 
    {
        return $value;
    }
}
