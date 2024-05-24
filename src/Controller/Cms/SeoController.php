<?php

namespace CorepulseBundle\Controller\Cms;

use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Starfruit\BuilderBundle\Model\Seo;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Db;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;

/**
 * @Route("/seo")
 */
class SeoController extends BaseController
{
    /**
     * @Route("/object/data", name="seo_object_data", options={"expose"=true}))
     */
    public function objectData(Request $request)
    {
        $language = $request->get('language');
        $object = DataObject::getById($request->get('id'));

        try {
            $seo = Seo::getOrCreate($object, $language);

            if ($request->get('update')) {
                $keyword = $request->get('keyword');
                $seo->setKeyword($keyword);
                $seo->save();
            }

            $scoring = $seo->getScoring();
        } catch (\Throwable $th) {
            $scoring = [];
        }

        return new JsonResponse($scoring);
    }

    /**
     * @Route("/", name="seo_index", options={"expose"=true}))
     */
    public function index(Request $request)
    {
        $viewData = ['metaTitle' => 'Seo'];

        return $this->renderWithInertia('Pages/Seo/Layout', [], $viewData);
    }

    /**
     * @Route("/seo-monitor", name="seo_monitor", options={"expose"=true}))
     */
    public function seoMonitor(Request $request): JsonResponse
    {
        $db = Db::get();

        $columns = ['code', 'uri', 'date', 'count'];

        $limit = (int)$request->get('limit', 10);
        $page = (int)$request->get('page', 1);
        $orderKey = $request->get('orderKey');
        $order = $request->get('order');
        $filter = $request->get('filter');
        
        $offset = 0;

        if ($page) {
            $offset = ($page - 1) * $limit;
        }

        if (!$orderKey || !in_array($orderKey, $columns)) {
            $orderKey = 'count';
        }

        if (!$order || !in_array($order, ['desc', 'asc'])) {
            $order = 'desc';
        }

        $condition = '';
        if ($filter) {
            $filter = $db->quote('%' . $filter . '%');

            $conditionParts = [];
            foreach (['uri', 'code', 'parametersGet', 'parametersPost', 'serverVars', 'cookies'] as $field) {
                $conditionParts[] = $field . ' LIKE ' . $filter;
            }
            $condition = ' WHERE ' . implode(' OR ', $conditionParts);
        }

        $listData = $db->fetchAllAssociative('SELECT code,uri,`count`, FROM_UNIXTIME(date, "%Y-%m-%d %h:%i") AS "date" FROM http_error_log ' . $condition . ' ORDER BY ' . $orderKey . ' ' . $order . ' LIMIT ' . $offset . ',' . $limit);
        $totalItems = $db->fetchOne('SELECT count(*) FROM http_error_log ' . $condition);

        $fields = [];
        foreach ($columns as $key => $value) {
            $fields[] = [
                'key' => $value,
                'tooltip' => '',
                'title' => $value,
                'removable' => true,
                'searchType' => 'Input',
            ];
        }

        $result = [
            "listing" => $listData,
            "totalItems" => $totalItems,
            "fields" => $fields,
            "limit" => $limit,
        ];

        return new JsonResponse($result);
    }

    /**
     * @Route("/seo-monitor-detail", name="seo_monitor_detail", options={"expose"=true}))
     */
    public function seoMonitorDetail(Request $request): Response
    {
        $db = Db::get();
        $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE uri = ?', [$request->get('uri')]);

        foreach ($data as $key => &$value) {
            if (in_array($key, ['parametersGet', 'parametersPost', 'serverVars', 'cookies'])) {
                $value = unserialize($value);
            }
        }

        $result = [
            "data" => $data,
        ];

        return new JsonResponse($result);
    }

    /**
     * @Route("/http", name="flush", methods={"DELETE"})
     */
    public function httpErrorLogFlushAction(Request $request): JsonResponse
    {
        $db = Db::get();
        $db->executeQuery('TRUNCATE TABLE http_error_log');

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/seo-redirect", name="seo_redirect", options={"expose"=true}))
     */
    public function seoRedirect(Request $request)
    {
        $limit = (int)$request->get('limit', 10);
        $page = (int)$request->get('page', 1);
        $orderKey = $request->get('orderKey');
        $order = $request->get('order');
        $filter = $request->get('filter');
        $offset = 0;

        if ($page) {
            $offset = ($page - 1) * $limit;
        }

        $list = new Redirect\Listing();
        $list->setLimit($limit);
        $list->setOffset($offset);

        if ($orderKey) {
            $list->setOrderKey($orderKey);
        }

        if ($order) {
            $list->setOrder($order);
        }

        if ($filter) {
            if (is_numeric($filter)) {
                $list->setCondition('id = ?', [$filter]);
            }  else {
                $list->setCondition('`source` LIKE ' . $list->quote('%' . $filter . '%') . ' OR `target` LIKE ' . $list->quote('%' . $filter . '%'));
            }
        }

        $list->load();

        $listData = [];
        foreach ($list->getRedirects() as $redirect) {
            if ($link = $redirect->getTarget()) {
                if (is_numeric($link)) {
                    if ($doc = Document::getById((int)$link)) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }
            }

            $listData[] = $redirect->getObjectVars();
        }

        $fields = [];
        $columns = ['type', 'source', 'sourceSite', 'target', 'targetSite', 'statusCode', 'priority', 'regex', 'passThroughParameters', 'active', 'expiry'];
        foreach ($columns as $key => $value) {
            $fields[] = [
                'key' => $value,
                'tooltip' => '',
                'title' => $value,
                'removable' => true,
                'searchType' => 'Input',
            ];
        }

        $result = [
            "listing" => $listData,
            "totalItems" => $list->getTotalCount(),
            "fields" => $fields,
            "limit" => $limit,
        ];

        return new JsonResponse($result);
    }
}
