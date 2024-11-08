<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Services\ClassServices;

class Localizedfields extends Input
{
    protected $data;
    protected $layout;
    protected $value;
    protected $localized;
    protected $optionKey;

    public function __construct($data, $layout = null, $value = null, $localized = false)
    {
        if (is_array($layout)) $layout = (object)$layout;

        $this->layout = $layout;
        $this->data = $data;
        $this->value = $value;
        $this->localized = $localized;
        $this->optionKey = [];
    }

    public function formatBlock($value) {
        $data = [];

        $items = $value->getItems();

        // update object
        if($this->value) {
            $data = $items;
        }

        // detail object
        if(!$this->value && $items && $this->localized && isset($items[$this->localized])) {
            $data = $items[$this->localized];
        }

        return $data;
    }

    public function getDataSave() {
        $data = null;
        if ($this->localized) {
            $data = new \Pimcore\Model\DataObject\Localizedfield([
                $this->localized => $this->value
            ]);
        }

        return $data;
    }

    public function getDefinitions()
    {
        $layouts = [];
        $children = $this->layout->children;
        if (!empty($children)) {
            foreach ($children as $key => $value) {
                $layout = ClassServices::getFieldProperty($value, $this->localized, $this->data?->getClassId());
                if(in_array( $layout['fieldtype'], ClassServices::TYPE_OPTION)) {
                    $this->optionKey[$layout['name']] = [
                        'fieldId' => $layout['name'],
                    ];
                }
                $layouts[$key] = $layout;
            }
        }

        return $layouts;
    }

    public function getOptionKey()
    {
        return $this->optionKey;
    }
}
