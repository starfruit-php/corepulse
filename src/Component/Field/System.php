<?php

namespace CorepulseBundle\Component\Field;

use App\Controller\BaseController;

class System extends Input
{
    public function format($value)
    {
        if (in_array($this->layout->type, self::SYSTEM_CONVERT_DATE)) {
            return date('Y/m/d', $value);
        }

        if ($this->layout->type == 'published') {
            $item = $this->data;
            $draft = $this->checkLastest($item);
            $status = 'Draft';
            if (!$draft) {
                if ($item->getPublished()) {
                    $status = 'Publish';
                } else {
                    $status = 'Unpublish';
                }
            }

            return $status;
        }

        return $value;
    }

    public function checkLastest($object)
    {
        $lastest = $this->getLastest($object);

        if ($lastest) {
            return $object->getModificationDate() !== $lastest->getModificationDate();
        }
        return false;
    }

    public function getLastest($object)
    {
        $versions = $object->getVersions();

        if (empty($versions)) {
            return $object;
        }

        $previousVersion = $versions[count($versions) - 1];
        $previousObject = $previousVersion->getData();
        return $previousObject;
    }
}
