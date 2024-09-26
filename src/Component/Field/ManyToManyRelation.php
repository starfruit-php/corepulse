<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;

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

    public function formatDataSave($values)
    {
        $datas = [];
        if ($values) {
            foreach ($values as $key => $value) {
                switch ($value["type"]) {
                    case 'asset':
                        $data = Asset::getById($value['id']);
                        break;
                    case 'document':
                        $data = Document::getById($value['id']);
                        break;
                    case 'object':
                        $data = DataObject::getById($value['id']);
                        break;

                    default:
                        $data = DataObject::getById($value['id']);
                        break;
                }
                if ($data) {
                    $datas[] = $data;
                }
            }
        }

        return $datas;
    }

    public function getFrontEndType():string
    {
        return 'manyToManyRelation';
    }
}
