<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Asset\Image\Thumbnail;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use CorepulseBundle\Controller\Cms\ImageController;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use CorepulseBundle\Services\Helper\SearchHelper;

class SearchController extends BaseController
{
    /**
     * @Route("/search", name="cms_search", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function searchAction(Request $request)
    {
        $searchHelper = isset($_COOKIE['searchHelper']) ? $_COOKIE['searchHelper'] : null;

        if (!$searchHelper) {
            $dataConfig = SearchHelper::dataConfig();
            setcookie("searchHelper", json_encode($dataConfig), time() + (600 * 30), "/");
        } else {
            $dataConfig = json_decode($searchHelper, true);
        }

        // $listkey = ['document', 'asset', 'dataobject'];
        $listkey = ['document', 'asset'];
        $checkCMS = $request->get('cms', '');
        $search = $request->get('search', '');
        $type = $request->get('type', []);
        $limit = $request->get('limit');
        $datas = [];

        if (count($type)) {
            $listkey = $type;
        }

        foreach ($dataConfig as $item) {
            $data = SearchHelper::getTree($item['name'], $search, $item['visibleSearch'],$limit);
            $datas = array_merge($data, $datas);
        }

        foreach ($listkey as $item) {
            $data = SearchHelper::getTree($item, $search, null, $limit);
            $datas = array_merge($data, $datas);
        }

        if ($checkCMS) {
            $classSearch = SearchHelper::getClassSearch($search, 'create');
            $datas = array_merge($classSearch, $datas);
            $classSearch = SearchHelper::getClassSearch($search, 'listing');
            $datas = array_merge($classSearch, $datas);
        }

        return new JsonResponse($datas);
    }
}
