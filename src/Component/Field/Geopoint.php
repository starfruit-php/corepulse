<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;

class Geopoint extends Image
{
    public function format($value)
    {
        if ($value) {
            return [
                'latitude' => $value->getLatitude(),
                'longitude' => $value->getLongitude(),
            ];
        }

        return null;
    }
}
