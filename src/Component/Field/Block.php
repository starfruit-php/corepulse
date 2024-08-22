<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Services\DataObjectServices;
use CorepulseBundle\Services\Helper\ArrayHelper;
use Pimcore\Model\DataObject\Data\BlockElement;

class Block extends Input
{
    public function format($value)
    {
        $result = null;

        if ($value) {
            $result = [];
            $fieldDefinitions = $this->layout->children;
            foreach ($value as $block) {
                $resultItem = [];
                /**
                 * @var  string $key
                 * @var  DataObject\Data\BlockElement $fieldValue
                 */
                foreach ($block as $key => $fieldValue) {
                    $fd = ArrayHelper::filterData($fieldDefinitions, 'name', $key);

                    if (empty($fd)) {
                        $resultItem[$key] = null;
                        continue;
                    }

                    $field = DataObjectServices::convertField(reset($fd));

                    if (isset($field['invisible']) && $field['invisible']) {
                        continue;
                    }

                    $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($field['fieldtype']);
                    if (!class_exists($getClass)) {
                        $resultItem[$key] = null;
                        continue;
                    }

                    $componentData = new $getClass($fieldValue, $field);

                    $resultItem[$key] = $componentData->getValue();
                }
                $result[] = $resultItem;
            }
        }

        return $result;
    }

    public function formatDataSave($values)
    {
        $datas = [];
        foreach ($values as $key => $value) {
            $data = [];
            foreach ($value as $k => $v) {
                $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($v['type']);
                if (class_exists($getClass)) {
                    $component = new $getClass($this->data, null, $v['value']);

                    $valueData =  $component->getDataSave();
                    $blockElement = new BlockElement($k, $v['type'], $valueData);
                    $data[$k] = $blockElement;
                }
            }
            $datas[] = $data;
        }

        return $datas;
    }

    public function getFrontEndType():string
    {
        return '';
    }
}
