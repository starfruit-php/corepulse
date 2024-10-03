<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Services\DataObjectServices;
use CorepulseBundle\Services\Helper\ArrayHelper;
use Pimcore\Model\DataObject\Data\BlockElement;
use CorepulseBundle\Services\ClassServices;
use Pimcore\Model\DataObject\Localizedfields;

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
                 * @var  BlockElement $fieldValue
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

                    $componentData = new $getClass($fieldValue, $field, $this->value, $this->localized);

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
                if (!class_exists($getClass)) {
                    continue;
                }

                $component = new $getClass($this->data, $this->layout, $v['value'], $this->localized);

                $valueData =  $component->getDataSave();

                if($v['type'] == 'localizedfields' && $this->getValue()) {
                    $revertItems = $valueData->getItems();
                    $valueOld = $this->getValue()[$key]['localizedfields'];
                    unset($valueOld[$this->localized]);
                    $valueData->setItems(array_merge($valueOld, $revertItems));
                }
                $blockElement = new BlockElement($k, $v['type'], $valueData);
                $data[$k] = $blockElement;
            }
            $datas[] = $data;
        }

        if ($this->getValue() && ($countDatas = count($datas)) < count($this->getValue())) {
            foreach ($this->getValue() as $key => $value) {
                if ($key > $countDatas - 1 && $value && isset($value['localizedfields'])) {
                    unset($value['localizedfields'][$this->localized]);
                    $valueData = new \Pimcore\Model\DataObject\Localizedfield($value['localizedfields']);
                    $data = new BlockElement('localizedfields', 'localizedfields', $valueData);
                    $datas[] = $data;
                }
            }
        }

        return $datas;
    }

    public function getDefinitions()
    {
        $layouts = [];
        $children = $this->layout->children;
        if (!empty($children)) {
            foreach ($children as $key => $value) {
                $layouts[$key] = ClassServices::getFieldProperty($value, $this->localized, $this->data?->getClassId());
            }
        }

        return $layouts;
    }

    public function getFrontEndType():string
    {
        return '';
    }
}
