<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Services\ClassServices;
use Pimcore\Model\DataObject;
use CorepulseBundle\Services\DataObjectServices;

class Fieldcollections extends Input
{
    public function format($value)
    {
        if ($value) {
            $resultItems = [];
            $items = $value->getItems();

            foreach ($items as $item) {
                $type = $item->getType();

                $definition = DataObject\Fieldcollection\Definition::getByKey($type);
                $value =  DataObjectServices::getData($item, $definition->getFieldDefinitions());

                $resultItems[] = [
                    'type' => $type,
                    'value' => $value
                ];
            }

            return $resultItems;
        }

        return null;
    }

    public function formatDataSave($values)
    {
        $items = new DataObject\Fieldcollection();
        foreach ($values as $key => $value) {
            $func = "Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\". ucfirst($value["name"]);

            $fieldCollection = new $func();
            foreach ($value['value'] as $k => $v) {
                $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($v['type']);
                if (class_exists($getClass)) {
                    $component = new $getClass($this->data, null, $v['value']);
                    $valueData = $component->getDataSave();

                    $fieldCollection->{'set' . ucfirst($k)}($valueData);
                }
            }

            $items->add($fieldCollection);
        }

        return $items;
    }

    public function getDefinitions()
    {
        $layouts = [];
        $allowedTypes = $this->layout->allowedTypes;
        if (!empty($allowedTypes)) {
            foreach ($allowedTypes as $type) {
                $layouts[$type] = $this->getDefinition($type);
            }
        }

        return $layouts;
    }

    public function getDefinition($type)
    {
        $layouts = [];
        $definition = DataObject\Fieldcollection\Definition::getByKey($type);
        foreach ($definition->getFieldDefinitions() as $key => $fieldCollection) {
            $layouts[$key] = ClassServices::getFieldProperty($fieldCollection);
        }

        return $layouts;
    }

    public function getFrontEndType():string
    {
        return '';
    }
}
