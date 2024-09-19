<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\Asset;
use CorepulseBundle\Component\Field\Image;
use Pimcore\Model\DataObject\Data\Video as DataVideo;

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

    public function formatDataSave($value)
    {
        $video = new DataVideo();

        if ($value && isset($value['type'])) {
            $video->setType($value['type']);
            if ($value['type'] == 'asset') {
                if (isset($value['title'])) $video->setTitle($value['title']);
                if (isset($value['description'])) $video->setDescription($value['description']);
                if (isset($value['data'])) $video->setData(Asset::getByPath($value['data']));
                if (isset($value['poster'])) $video->setPoster( Asset\Image::getByPath($value['poster']));
            } else {
                if (isset($value['data'])) $video->setData($value['data']);
            }
        }
        
        return $video;
    }
}
