<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Services\ClassServices;
use Pimcore\Model\DataObject;
use CorepulseBundle\Services\DataObjectServices;

class Fieldcollections extends Input
{
    public function format($value)
    {
        if (!$value) return null;

        $resultItems = [];
        foreach ($value->getItems() as $item) {
            $type = $item->getType();
            $definition = DataObject\Fieldcollection\Definition::getByKey($type);
            $resultItems[] = [
                'type' => $type,
                'value' => DataObjectServices::getData($item, $definition->getFieldDefinitions())
            ];
        }

        return $resultItems;
    }

    public function formatDataSave($values)
    {
        $items = new DataObject\Fieldcollection();
        foreach ($values as $value) {
            $fieldCollection = $this->createFieldCollection($value);
            if ($fieldCollection) {
                $items->add($fieldCollection);
            }
        }

        return $items;
    }

    private function createFieldCollection($value)
    {
        $func = "Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\" . ucfirst($value["name"]);
        $fieldCollection = new $func();

        foreach ($value['value'] as $k => $v) {
            $component = $this->createComponent($v);
            if ($component) {
                $fieldCollection->{'set' . ucfirst($k)}($component->getDataSave());
            }
        }

        return $fieldCollection;
    }

    private function createComponent($v)
    {
        $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($v['type']);
        return class_exists($getClass) ? new $getClass($this->data, null, $v['value']) : null;
    }

    public function getDefinitions()
    {
        $layouts = [];
        foreach ($this->layout->allowedTypes ?? [] as $type) {
            $layouts[$type] = $this->getDefinition($type);
        }

        return $layouts;
    }

    public function getDefinition($type)
    {
        $layouts = [];
        $definition = DataObject\Fieldcollection\Definition::getByKey($type);
        foreach ($definition->getFieldDefinitions() as $key => $fieldCollection) {
            $layouts[$key] = ClassServices::getFieldProperty($fieldCollection, $this->localized, $this->data?->getClassId());
        }

        return $layouts;
    }

    public function getFrontEndType(): string
    {
        return '';
    }
}
