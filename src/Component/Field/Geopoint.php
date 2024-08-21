<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\DataObject\Data\GeoCoordinates;

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

    public function formatDataSave($value)
    {
        if (is_array($value)) {
            $latitude = (float)$value['latitude'];
            $longitude = (float)$value['longitude'];

            $data = new GeoCoordinates($latitude, $longitude);
            return $data;
        }

        return null;
    }
}
