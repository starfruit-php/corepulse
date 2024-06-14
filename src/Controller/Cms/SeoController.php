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
     * @Route("/sitemap", name="seo_sitemap", options={"expose"=true}))
     */
    public function sitemap(Request $request)
    {
        $viewData = ['metaTitle' => 'Sitemap'];

        return $this->renderWithInertia('Pages/Seo/Sitemap', [], $viewData);
    }

    /**
     * @Route("/http-error", name="seo_http_error", options={"expose"=true}))
     */
    public function httpError(Request $request)
    {
        $viewData = ['metaTitle' => '404 & 301'];

        return $this->renderWithInertia('Pages/Seo/HttpError', [], $viewData);
    }

    /**
     * @Route("/indexing", name="seo_indexing", options={"expose"=true}))
     */
    public function indexing(Request $request)
    {
        $viewData = ['metaTitle' => 'Indexing'];

        return $this->renderWithInertia('Pages/Seo/Indexing', [], $viewData);
    }

    /**
     * @Route("/object/data", name="seo_object_data", options={"expose"=true}))
     */
    public function objectData(Request $request): JsonResponse
    {
        $language = $request->get('language');
        $object = DataObject::getById($request->get('id'));

        try {
            // lấy thông tin or thêm mới object vào bảng seo
            $seo = Seo::getOrCreate($object, $language);

            //lưu dữ liệu vào bảng seo
            if ($request->get('update')) {
                $params = $request->request->all();
                $keyUpdate = ['keyword', 'title', 'slug', 'description', 'image', 'canonicalUrl', 'redirectLink',
                'nofollow', 'indexing', 'redirectType', 'destinationUrl', 'schemaBlock', 'image', 'imageAsset'];

                foreach ($params as $key => $value) {
                    $function = 'set' . ucfirst($key);

                    if (in_array($key, $keyUpdate) && method_exists($seo, $function)) {
                        if ($key == 'nofollow' || $key == 'indexing' || $key == 'redirectLink') {
                           $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        }
                        $seo->$function($value);
                    }
                }
                $seo->save();
            }

            // lấy danh sách dữ liệu seoscoring
            $scoring = $seo->getScoring(true);
        } catch (\Throwable $th) {
            $scoring = [];
        }

        return new JsonResponse($scoring);
    }

    /**
     * @Route("/redirect-type", name="seo_redirect_type", options={"expose"=true}))
     */
    public function redirectType(Request $request): JsonResponse
    {
        $data = [
            [
                'key' => '301 Permanent Move',
                'value' => 301
            ],
            [
                'key' => '302 Temporary Move',
                'value' => 302
            ],
            [
                'key' => '307 Temporary Redirect',
                'value' => 307
            ],
            [
                'key' => '401 Content Deleted',
                'value' => 401
            ],
            [
                'key' => '451 Content Unavailable',
                'value' => 451
            ]
        ];

        return new JsonResponse($data);
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
        $search = $request->get('search');

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
        if ($search) {
            $search = json_decode($search, true);

            $search = array_filter($search, function($value) {
                return $value !== "" && $value !== " ";
            });

            $conditionParts = [];
            foreach ($search as $key => $value) {
                $conditionParts[] = $key . ' LIKE ' . $db->quote('%' . $value . '%');
            }

            if (count($conditionParts)) {
                $condition = ' WHERE ' . implode(' OR ', $conditionParts);
            }
        }

        $listData = $db->fetchAllAssociative('SELECT id, code,uri,`count`, FROM_UNIXTIME(date, "%Y-%m-%d %h:%i") AS "date" FROM http_error_log ' . $condition . ' ORDER BY ' . $orderKey . ' ' . $order . ' LIMIT ' . $offset . ',' . $limit);

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
    public function seoMonitorDetail(Request $request): JsonResponse
    {
        $db = Db::get();
        $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE id = ?', [$request->get('id')]);

        foreach ($data as $key => &$value) {
            if (in_array($key, ['parametersGet', 'parametersPost', 'serverVars', 'cookies'])) {
                $value = unserialize($value);
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/seo-monitor-truncate", name="seo_monitor_truncate", methods={"POST"}, options={"expose"=true}))
     */
    public function seoMonitorTruncate(Request $request): JsonResponse
    {
        $db = Db::get();
        $db->executeQuery('TRUNCATE TABLE http_error_log');

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/seo-monitor-delete", name="seo_monitor_delete", methods={"POST"}, options={"expose"=true}))
     */
    public function seoMonitorDelete(Request $request): JsonResponse
    {
        $db = Db::get();
        if ($request->get('all')) {
            $ids = $request->get('id');
            foreach($ids as $id) {
                $db->executeQuery('DELETE FROM http_error_log WHERE id = ' . $id);
            }
        } else {
            $db->executeQuery('DELETE FROM http_error_log WHERE id = ' . $request->get('id'));
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/seo-redirect", name="seo_redirect", options={"expose"=true}))
     */
    public function seoRedirect(Request $request): JsonResponse
    {
        $limit = (int)$request->get('limit', 10);
        $page = (int)$request->get('page', 1);
        $orderKey = $request->get('orderKey');
        $order = $request->get('order');
        $search = $request->get('search');
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

        $condition = '';
        if ($search) {
            $search = json_decode($search, true);

            $search = array_filter($search, function($value) {
                return $value !== "" && $value !== " ";
            });

            $conditionParts = [];
            foreach ($search as $key => $value) {
                $conditionParts[] = $key . ' LIKE ' . $list->quote('%' . $value . '%');
            }

            $condition =  implode(' OR ', $conditionParts);
        }

        $list->setCondition($condition);

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

    /**
     * @Route("/seo-redirect-delete", name="seo_redirect_delete", methods={"POST"}, options={"expose"=true}))
     */
    public function seoRedirectDelete(Request $request): JsonResponse
    {
        if ($request->get('all')) {
            $ids = $request->get('id');
            foreach($ids as $id) {
                $redirect = Redirect::getById($id);
                $redirect->delete();
            }
        } else {
            $redirect = Redirect::getById($request->get('id'));
            $redirect->delete();
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    public function siteOption(Request $request)
    {
        $excludeMainSite = $request->get('excludeMainSite');

        $sitesList = new Site\Listing();
        $sitesObjects = $sitesList->load();

        $sites = [];
        if (!$excludeMainSite) {
            $sites[] = [
                'id' => 0,
                'rootId' => 1,
                'domains' => '',
                'rootPath' => '/',
                'domain' => $this->translator->trans('main_site', [], 'admin'),
            ];
        }

        foreach ($sitesObjects as $site) {
            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        'id' => $site->getId(),
                        'rootId' => $site->getRootId(),
                        'domains' => implode(',', $site->getDomains()),
                        'rootPath' => $site->getRootPath(),
                        'domain' => $site->getMainDomain(),
                    ];
                }
            } else {
                // site is useless, parent doesn't exist anymore
                $site->delete();
            }
        }
    }
}
