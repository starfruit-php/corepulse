<?php

namespace CorepulseBundle\Component\Field;

use CorepulseBundle\Services\DataObjectServices;

class Localizedfields extends Input
{
    public function formatBlock($value) {
        $data = [];

        $items = $value->getItems();

        // update object
        if($this->value) {
            $data = $items;
        }

        // detail object
        if(!$this->value && $items && $this->localized && isset($items[$this->localized])) {
            $data = $items[$this->localized];
        }

        return $data;
    }

    public function getDataSave() {
        $data = null;
        if ($this->localized) {
            $data = new \Pimcore\Model\DataObject\Localizedfield([
                $this->localized => $this->value
            ]);
        }

        return $data;
    }
}
