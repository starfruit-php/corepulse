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
     * @Route("/listing", name="api_app_listing", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function listingAction( Request $request ): JsonResponse
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

            $data['data']['config'] = $loginSetting['config'];

            $colorPrimary = "#6a1b9a";
            $colorLight = "#f3e5f5";
            if ($loginSetting['config']) {
                $colorPrimary = $loginSetting['config']['color'] ?? '#6a1b9a';
                $colorLight = $loginSetting['config']['colorLight'] ?? '#f3e5f5';
                if ($loginSetting['config']['logo']) {
                    $this->inertia->viewData('favicon', $loginSetting['config']['logo']);
                }
            }

            $data['data']['colorPrimary'] = $colorPrimary;
            $data['data']['colorLight'] = $colorLight;

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

}