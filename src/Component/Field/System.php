<?php

namespace CorepulseBundle\Component\Field;

class System extends Input
{
    public function format($value)
    {
        if (in_array($this->layout->type, self::SYSTEM_CONVERT_DATE)) {
            return date('Y/m/d', $value);
        }

        return $value;
    }
}
