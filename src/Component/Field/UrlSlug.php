<?php

namespace CorepulseBundle\Component\Field;

class UrlSlug extends Input
{
    public function format($data)
    {
        if (empty($data)) {
            return null;
        }

        $value = array_shift($data)?->getSlug();

        return $value;
    }
}
