<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\DataObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
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
}
