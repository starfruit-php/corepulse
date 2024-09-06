<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\DataObject\SelectOptions\Province;

class Select extends Input
{
    public function getOption()
    {
        $layoutDefinition = $this->layout;
        $options = [];
        switch ($layoutDefinition->optionsProviderType) {
            case 'class':
                $optionsProviderClass = $layoutDefinition->optionsProviderClass;
                if (class_exists($optionsProviderClass)) {
                    $optionProvider = new $optionsProviderClass;
                    $options = $optionProvider->getOptions( $this->data ? compact('object') : [], $layoutDefinition);
                } else {
                    $options = $layoutDefinition->options;
                }

                break;
            case 'select_options':
                $optionsProviderClass = 'Pimcore\\Model\\DataObject\\SelectOptions\\' . $layoutDefinition->optionsProviderData;
                if (method_exists($optionsProviderClass, 'cases')) {
                    $cases = $optionsProviderClass::cases();

                    foreach ($cases as $key => $value) {
                        $options[] = [
                            'key' => $value->getLabel(),
                            'value' => $value->value,
                        ];
                    }
                } else {
                    $options = $layoutDefinition->options;
                }
                break;

            default:
                $options = $layoutDefinition->options;
                break;
        }

        return $options;
    }

    public function getFrontEndType():string
    {
        return 'select';
    }
}
