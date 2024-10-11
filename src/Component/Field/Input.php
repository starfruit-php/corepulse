<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Component\Field\FieldInterface;
use Pimcore\Model\DataObject\Data\BlockElement;

class Input implements FieldInterface
{
    protected $data;
    protected $layout;
    protected $value;
    protected $localized;

    public function __construct($data, $layout = null, $value = null, $localized = false)
    {
        if (is_array($layout)) $layout = (object)$layout;

        $this->layout = $layout;
        $this->data = $data;
        $this->value = $value;
        $this->localized = $localized;
    }

    public function getName():string
    {
        return $this->layout ? $this->layout->name : $this->data->getName();
    }

    public function getTitle():string
    {
        return $this->layout ? $this->layout->title : $this->data->getTitle();
    }

    public function getInvisible():string
    {
        return $this->layout->invisible;
    }

    public function getVisibleSearch():string
    {
        return $this->layout->visibleSearch;
    }

    public function getVisibleGridView():string
    {
        return $this->layout->visibleGridView;
    }

    public function getValue()
    {
        if (!$this->layout) return $this->formatDocument($this->data->getData());

        if ($this->data instanceof BlockElement) return $this->formatBlock($this->data->getData());

        $value = $this->data->{'get' . ucfirst($this->getName())}();

        if ($this->localized) $value = $this->data->{'get' . ucfirst($this->getName())}($this->localized);

        return $this->format($value);
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

    public function getFrontEndType():string
    {
        return 'string';
    }
}
