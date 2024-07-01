<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use CorepulseBundle\Services\SeoServices;
use Pimcore\Model\DataObject;
use Pimcore\Db;

/**
 * @Route("/ai")
 */
class AIController extends BaseController
{
    /**
     * @Route("/setting", name="ai_setting", methods={"POST"}, options={"expose"=true}))
     */
    public function setting(Request $request)
    {
        $setting = $request->get('setting');

        return new JsonResponse([
            'success' => SeoServices::checkApi($setting)
        ]);
    }

    /**
     * @Route("/seo/send-keyword", name="ai_seo_send_keyword", methods={"POST"}, options={"expose"=true}))
     */
    public function sendKeyword(Request $request)
    {
        $keyword = $request->get('keyword');
        $language = $request->get('language');
        $type = $request->get('type');

        $content =  SeoServices::choicesContent($keyword, $type, $language);

        $response = SeoServices::sendCompletions($content);

        // if ($response) {
            // $contentRes = $response['choices'][0]['message']['content'];

            // $result = [];

            // if ($type == 'outline') {
            //     $sections = explode("\n\n", $contentRes);

            //     foreach ($sections as $section) {
            //         $lines = explode("\n", $section);
            //         $keyValue = explode(". ", $lines[0]);
            //         $key = trim($keyValue[0]);
            //         $value = isset($keyValue[1]) ? trim($keyValue[1]) : '';

            //         $children = [];
            //         for ($i = 1; $i < count($lines); $i++) {
            //             $children[] = trim($lines[$i]);
            //         }

            //         $item = [
            //             'value' => $value,
            //             'children' => $children
            //         ];

            //         $result[$key] = $item;
            //     }
            // } else {
            //     $sections = explode("\n", $contentRes);
            //     $sections = array_map('trim', $sections);

            //     foreach ($sections as $section) {
            //         $parts = explode('. ', $section);
            //         $key = $parts[0];
            //         $value = $parts[1] ?? '';

            //         $result[$key] = $value;
            //     }
            // }

        //     return new JsonResponse([
        //         'success' => 'true',
        //         'data' => $result
        //     ]);
        // }

        return new JsonResponse([
            'success' => $response['success'],
            'data' => $response['data'],
        ]);
    }
}
