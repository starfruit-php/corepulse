<?php

namespace CorepulseBundle\Controller\Api;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Model\DataObject\ClassDefinition;


/**
 * @Route("/app")
 */
class AppController extends BaseController
{

    /**
     * @Route("/setting", name="api_app_setting", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function settingAction( Request $request ): JsonResponse
    {
        try {
            $loginSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "login"', []);
            
            $data['data'] = [];

            if (!$loginSetting) {
                Db::get()->insert('vuetify_settings', [
                    'type' => 'login',
                ]);
                $loginSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "login"', []);
            }
            if ($loginSetting['config']) {
                $loginSetting['config'] = json_decode($loginSetting['config'], true);
            } else {
                $loginSetting['config'] = [];
            }

            $setting = $loginSetting['config'];
            $data['data'] = [
                'logo' => isset($setting['logo']) ? $setting['logo'] : '/bundles/corepulse/image/corepulse.png',
                'background' => isset($setting['background']) ? $setting['background'] : '/bundles/pimcoreadmin/img/login/pc11.svg',
                'colorPrimary' => isset($setting['color']) ? $setting['color'] : '#6a1b9a',
                'colorLight' => isset($setting['colorLight']) ? $setting['colorLight'] : '#f3e5f5',
                'title' => isset($setting['title']) ? $setting['title'] : 'Corepluse',
                'footer' => isset($setting['footer']) ? $setting['footer'] : '<p>From Starfruit With Love</p>',

            ];

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

}