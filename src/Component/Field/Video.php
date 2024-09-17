<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\Asset;
use CorepulseBundle\Component\Field\Image;

class Video extends Image
{
    public function format($value)
    {
        $data = [
            'type' => $value?->getType() ?? null,
            'data' => $value?->getData() instanceof Asset ? $value->getData()?->getFullPath() : $value?->getData(),
            'title' => $value?->getTitle() ?? null,
            'description' => $value?->getDescription() ?? null,
            'poster' => $value?->getPoster() ? $value->getPoster()?->getFullPath() : null,
        ];

        return $data;
    }

    public function getFrontEndType():string
    {
        return 'video';
    }
}
