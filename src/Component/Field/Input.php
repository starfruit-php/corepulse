<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Component\Field\FieldInterface;
use Pimcore\Model\DataObject\Data\BlockElement;

class Input implements FieldInterface
{
    const SYSTEM_CONVERT_DATE = ['creationDate', 'modificationDate'];

    protected $data;

    protected $layout;

    protected $value;

    public function __construct($data, $layout = null, $value = null)
    {
        if (is_array($layout)) $layout = (object)$layout;

        $this->layout = $layout;
        $this->data = $data;
        $this->value = $value;
    }

    public function getName():string
    {
        return $this->layout ? $this->layout->name : $this->data->getName();
    }

    public function getTitle():string
    {
        return $this->layout ? $this->layout->title : $this->data->getTitle();
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

    public function getDataSave()
    {
        return $this->formatDataSave($this->value);
    }

    public function formatDataSave($value)
    {
        return $value;
    }
}
