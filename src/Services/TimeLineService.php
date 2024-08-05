<?php

namespace CorepulseBundle\Services;

use Pimcore\Db;
use \CorepulseBundle\Model\TimeLine;

class TimeLineService
{
    public static function create($params)
    {
        $timeline = new TimeLine();

        foreach ($params as $key => $value) {
            $setValue = 'set' . ucfirst($key);
            if (method_exists($timeline, $setValue)) {
                $timeline->$setValue($value);
            }
        }
        $timeline->setUpdateAt(date('Y-m-d H:i:s'));
        $timeline->setCreateAt(date('Y-m-d H:i:s'));
        $timeline->save();

        return $timeline;
    }

    public static function edit($params, $timeline)
    {
        foreach ($params as $key => $value) {
            $setValue = 'set' . ucfirst($key);
            if (method_exists($timeline, $setValue)) {
                $timeline->$setValue($value);
            }
        }
        $timeline->save();

        return $timeline;
    }

    public static function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                $timeline = TimeLine::getById($i);
                $timeline->delete();
            }
        } else {
            $timeline = TimeLine::getById($id);
            $timeline->delete();
        }

        return true;
    }
}
