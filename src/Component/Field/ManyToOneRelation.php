<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;

class ManyToOneRelation extends Select
{
    public function format($value)
    {
        if ($value) {
            return $this->getElementType($value);
        }

        return null;
    }

    public function getElementType(ElementInterface $element)
    {
        $data = null;

        $visibleFields = ['key', 'path', 'fullpath'];
        $displayMode = $this->layout->displayMode;
 
        if (property_exists($this->layout, 'visibleFields') && $this->layout->visibleFields) {
            $visibleFields = explode(',',$this->layout->visibleFields);
        }

        if ($element instanceof Asset) {
            $data = [
                'type' => 'asset',
                'id' => $element->getId(),
                'subType' => $element->getType()
            ];
        }

        if ($element instanceof Document) {
            $data = [
                'type' => 'document',
                'id' => $element->getId(),
                'subType' => $element->getType()
            ];
            if ($key = array_search("filename", $visibleFields)) {
                unset($visibleFields[$key]);
            }
        }

        if ($element instanceof DataObject\AbstractObject) {
            $data = [
                'type' => 'Object',
                'id' => $element->getId(),
                'subType' => $element->getClassName()
            ];
           
            if ($key = array_search("filename", $visibleFields)) {
                unset($visibleFields[$key]);
            }
        }

        foreach ($visibleFields as $field) {
            $value = $element->{'get' . ucfirst($field)}();
            if (in_array($field, self::SYSTEM_CONVERT_DATE)) {
                $value = date('Y/m/d', $value);
            }

            $data[$field] = $value;
        }

        return $data;
    }

    public function getOption()
    {

    }
}
