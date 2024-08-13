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

    public static function getElementType(ElementInterface $element)
    {
        return match (true) {
            $element instanceof Asset => [
                'type' => 'asset',
                'id' => $element->getId(),
                'subType' => $element->getType()
            ],
            $element instanceof Document =>  [
                'type' => 'document',
                'id' => $element->getId(),
                'subType' => $element->getType()
            ],
            $element instanceof DataObject\AbstractObject => [
                'type' => 'Object',
                'id' => $element->getId(),
                'subType' => $element->getClassName()
            ],
            default => null,
        };
    }
}
