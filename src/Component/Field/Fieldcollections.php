<?php

namespace CorepulseBundle\Component\Field;

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

                $resultItems[] = $value;
            }

            return $resultItems;
        }

        return null;
    }

    public function formatDataSave($values)
    {
        $items = new DataObject\Fieldcollection();
        foreach ($values as $key => $value) {
            $fieldCollection = new $this->layout();
            foreach ($value as $k => $v) {
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
}
