<?php

namespace CorepulseBundle\Controller\Cms;

use CorepulseBundle\Services\SeoServices;
use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Starfruit\BuilderBundle\Model\Seo;
use Symfony\Component\HttpFoundation\Response;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model\Document;
use Pimcore\Db;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;
use Starfruit\BuilderBundle\Sitemap\Setting;
use Starfruit\BuilderBundle\Config\ObjectConfig;
use Starfruit\BuilderBundle\Model\Option;

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
     * @Route("/sitemap/config", name="seo_sitemap_config", options={"expose"=true}))
     */
    public function sitemapConfig(Request $request)
    {
        if ($request->get('update')) {
            $keys = $request->get('keys');
            Setting::setKeys($keys);

            // $settingDomain = 'builder:option-setting --main-domain=' . $request->getSchemeAndHttpHost();
            // $this->runProcess($settingDomain);

            $comand = 'builder:sitemap:generate';
            $this->runProcess($comand);
        }

        $settingClass = Setting::getKeys();

        $data = [
            'classConfig' => $settingClass,
            'documentConfig' => '',
        ];
        return new JsonResponse($data);
    }

    /**
     * @Route("/setting", name="seo_setting", options={"expose"=true}))
     */
    public function settingConfig(Request $request): JsonResponse
    {

        $setting = SeoServices::getSetting();

        if ($request->getMethod() == Request::METHOD_POST) {
            $setting = SeoServices::saveSetting($setting, $request->request->all());
        }

        $data = json_decode($setting->getContent(), true);

        return new JsonResponse($data);
    }

    /**
     * @Route("/404-301", name="seo_http_error", options={"expose"=true}))
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
        // $httpClientConfig = new \GuzzleHttp\Client([
        //     'proxy' => 'cbs2.localhost',
        //     'verify' => false,
        // ]);

        // $path = PIMCORE_PROJECT_ROOT . '/public/voltaic-cocoa-335508-fef2163d3409.json';

        // $client = new \Google\Client();
        // $client->setAuthConfig($path);

        // $client->addScope('https://www.googleapis.com/auth/indexing');

        // $httpClient = $client->authorize();
        // $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

        // $content = '{
        // "url": "http://cbs2.localhost/cms/seo/indexing/san-pham",
        // "type": "URL_UPDATED"
        // }';

        // $response = $httpClient->post($endpoint, [ 'body' => $content ]);

        // $status_code = $response->getStatusCode();

        return $this->renderWithInertia('Pages/Seo/Indexing', [], $viewData);
    }

    /**
     * @Route("/object-data", name="seo_object_data", options={"expose"=true}))
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
                $seo = SeoServices::saveData($seo, $params);
            }

            // $ogData = [
            //     'og:title' => 'meta title',
            //     'og:description' => 'meta description'
            // ];

            // $twitterMetaData = [
            //     'twitter:title' => 'twitter title',
            //     'twitter:description' => 'twitter description'
            // ];

            // $customMetaData = [];

            // get
            $metaData = $seo->getMetaDatas();

            // lấy danh sách dữ liệu seoscoring
            $scoring = $seo->getScoring(true);
            $data = array_merge($scoring, $metaData);
        } catch (\Throwable $th) {
            $data = [];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/meta-data-type", name="seo_meta_data_type", options={"expose"=true}))
     */
    public function metaDataType(Request $request): JsonResponse
    {
        $data = [
            'ogMeta' => [
                [
                    'key' => 'Meta Title',
                    'value' => 'og:title',
                ],
                [
                    'key' => 'Meta Description',
                    'value' => 'og:description',
                ],
                [
                    'key' => 'Meta Type',
                    'value' => 'og:type',
                ],
                [
                    'key' => 'Meta Image',
                    'value' => 'og:image',
                ],
                [
                    'key' => 'Meta Url',
                    'value' => 'og:url',
                ],
                [
                    'key' => 'Meta Image Alt',
                    'value' => 'og:image:alt',
                ],
            ],
            'twitterMeta' => [
                [
                    'key' => 'Twitter Title',
                    'value' => 'twitter:title',
                ],
                [
                    'value' => 'twitter:description',
                    'key' => 'Twitter Description',
                ],
                [
                    'key' => 'Twitter Card',
                    'value' => 'twitter:card',
                ],
                [
                    'key' => 'Twitter Site',
                    'value' => 'twitter:site',
                ],
                [
                    'key' => 'Twitter Image',
                    'value' => 'twitter:image',
                ],
                [
                    'key' => 'Twitter Image Alt',
                    'value' => 'twitter:image:alt',
                ],
            ]
        ];

        return new JsonResponse($data);
    }

     /**
     * @Route("/document-page", name="seo_document_page", options={"expose"=true}))
     */
    public function documentPage(Request $request): JsonResponse
    {
        $listing = new Document\Listing();
        $listing->setCondition('type = "page"');

        $data = [];
        foreach ($listing as $key => $value) {
            $title = $value->getId() == 1 ? 'Home' : $value->getKey();
            $data[] = [
                'title' => $title,
                'value' => $value->getId(),
                'key' => $title,
            ];
        }

        return new JsonResponse($data);
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
     * @Route("/seo-monitor/detail", name="seo_monitor_detail", options={"expose"=true}))
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
     * @Route("/seo-monitor/truncate", name="seo_monitor_truncate", methods={"POST"}, options={"expose"=true}))
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
     * @Route("/seo-monitor/delete", name="seo_monitor_delete", methods={"POST"}, options={"expose"=true}))
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
        $headers = [];
        $columns = ['type', 'source', 'target', 'statusCode', 'active', 'expiry', 'sourceSite', 'targetSite', 'priority', 'regex', 'passThroughParameters'];
        foreach ($columns as $key => $value) {
            $item = [
                'key' => $value,
                'tooltip' => '',
                'title' => $value,
                'removable' => false,
                'searchType' => 'Input',
            ];

            if ($key < 6) {
                $item['removable'] = true;
                $headers[] = $item;
            }

            $fields[] = $item;
        }

        $result = [
            "listing" => $listData,
            "totalItems" => $list->getTotalCount(),
            "fields" => $fields,
            "limit" => $limit,
            "headers" => $headers,
        ];

        return new JsonResponse($result);
    }

    /**
     * @Route("/seo-redirect/detail", name="seo_redirect_detail", options={"expose"=true}))
     */
    public function seoRedirectDetail(Request $request): JsonResponse
    {
        if ($request->get('action') === 'Update') {
            $data = $request->request->all();
            unset($data['action']);

            // save redirect
            $redirect = Redirect::getById($data['id']);

            if (!$redirect) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Redirect not found',
                ]);
            }

            if ($data['target']) {
                if ($doc = Document::getByPath($data['target'])) {
                    $data['target'] = $doc->getId();
                }
            }

            if (!$data['regex'] && $data['source']) {
                $data['source'] = str_replace('+', ' ', $data['source']);
            }

            $redirect->setValues($data);

            $redirect->save();

            $redirectTarget = $redirect->getTarget();
            if (is_numeric($redirectTarget)) {
                if ($doc = Document::getById((int)$redirectTarget)) {
                    $redirect->setTarget($doc->getRealFullPath());
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Update redirect success',
            ]);
        }
        if ($request->get('action') == 'Create') {
            $data = $request->request->all();
            unset($data['action']);

            $redirect = new Redirect();

            if (!empty($data['target'])) {
                if ($doc = Document::getByPath($data['target'])) {
                    $data['target'] = $doc->getId();
                }
            }

            if (isset($data['regex']) && !$data['regex'] && isset($data['source']) && $data['source']) {
                $data['source'] = str_replace('+', ' ', $data['source']);
            }

            $redirect->setValues($data);

            $redirect->save();

            $redirectTarget = $redirect->getTarget();
            if (is_numeric($redirectTarget)) {
                if ($doc = Document::getById((int)$redirectTarget)) {
                    $redirect->setTarget($doc->getRealFullPath());
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Create new redirect success',
            ]);
        }

        return new JsonResponse([]);
    }

    /**
     * @Route("/seo-redirect/type-option", name="seo_redirect_type_option", options={"expose"=true}))
     */
    public function seoRedirectTypeOption(Request $request): JsonResponse
    {
        $data = [
            [
                'key' => 'Path: /foo',
                'value' => 'path'
            ],
            [
                'key' => 'Auto create',
                'value' => 'auto_create'
            ],
            [
                'key' => 'Path and Query: /foo?key=value',
                'value' => 'path_query'
            ],
            [
                'key' => 'Entire URI: https://host.com/foo?key=value',
                'value' => 'entire_uri'
            ],
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/seo-redirect/delete", name="seo_redirect_delete", methods={"POST"}, options={"expose"=true}))
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
}
