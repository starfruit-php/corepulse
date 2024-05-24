<?php

namespace CorepulseBundle\Services;

use Pimcore\Model\DataObject\Service as DataObjectService;
use Starfruit\BuilderBundle\Model\Seo;

class SeoServices
{
    CONST SUCCESS_ICON = '';
    CONST ERROR_ICON = '';

    static public function scoringData($object, $language)
    {
        // try {
            $seo = Seo::getOrCreate($object, $language);

            // $keyword = 'Hợp đồng điện tử';
            // $seo->setKeyword($keyword);
            // $seo->save();

            $scoring = $seo->getScoring();
            dd($scoring);
        // } catch (\Throwable $th) {
        //     $scoring = [];
        // }
    }

    public function basicData($content)
    {
        $data = [];

        $countWord = $content['countWord'];
        if ($countWord > 1500) {
            $data['countWord'] = [
                'title' => '',
                'icon' => self::SUCCESS_ICON,
            ];
        } else {
            $data['countWord'] = [
                'title' => '',
                'icon' => self::ERROR_ICON,
            ];
        }



        return $data;
    }
}
