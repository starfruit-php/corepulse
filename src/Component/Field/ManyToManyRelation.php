<?php

namespace CorepulseBundle\Component\Field;

class ManyToManyRelation extends ManyToOneRelation
{
    public function format($value)
    {
        if (!empty($value)) {
            $result = [];
            foreach ($value as $element) {
                $result[] = $this->getElementType($element);
            }

            return $result;
        }

        return null;
    }
}
