<?php

namespace CorepulseBundle\Component\Field;

use Carbon\Carbon;

class Date extends Input
{
    public function format($value)
    {
        return $value?->format("Y/m/d");
    }

    public function formatDataSave($value)
    {
        if ($value) {
            $data = Carbon::createFromFormat('Y/m/d', $value);

            return $data;
        }

        return null;
    }

    public function getFrontEndType():string
    {
        return 'date';
    }
}
