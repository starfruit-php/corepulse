<?php

namespace CorepulseBundle\Controller\Api;

use Symfony\Component\Routing\Annotation\Route;
use Starfruit\BuilderBundle\Sitemap\Setting;
use CorepulseBundle\Services\GoogleServices;
use Pimcore\Db;
use Symfony\Component\HttpFoundation\Request;
use CorepulseBundle\Services\Helper\ArrayHelper;
use CorepulseBundle\Services\SeoServices;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model\Document;

/**
 * @Route("/seo")
 */
class SeoController extends BaseController
{
    /**
     * @Route("/404/listing", name="api_seo_monitor_listing", methods={"GET", "POST"})
     *
     * {mô tả api}
     */
    public function monitorListing()
    {
        try {
            $orderByOptions = ['code', 'uri', 'date', 'count', 'id'];
            $conditions = $this->getPaginationConditions($this->request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $order_by = $this->request->get('order_by', 'uri');
            $order = $this->request->get('order', 'asc');

            $condition = '';

            $db = Db::get();
            $listData = $db->fetchAllAssociative('SELECT id, code, uri, `count`, FROM_UNIXTIME(date, "%Y-%m-%d %h:%i") AS "date" FROM http_error_log ? ORDER BY ?', [$condition, $order_by]);


            $filter = ArrayHelper::sortArrayByField($listData, $order_by, $order);

            $pagination = $this->paginator($filter, $page, $limit);

            $data = [
                'paginationData' => $pagination->getPaginationData(),
            ];

            foreach($pagination as $item) {
                $data['data'][] =  $item;
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/404/detail", name="api_seo_monitor_detail", methods={"GET"})
     */
    public function monitorDetail()
    {
        try {
            $condition = [
                'id' => 'required|numeric',
            ];

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $db = Db::get();
            $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE id = ?', [$this->request->get('id')]);

            foreach ($data as $key => &$value) {
                if (in_array($key, ['parametersGet', 'parametersPost', 'serverVars', 'cookies'])) {
                    $value = unserialize($value);
                }
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/404/truncate", name="api_seo_monitor_truncate", methods={"POST"})
     */
    public function monitorTruncate()
    {
        try {
            $db = Db::get();
            $db->executeQuery('TRUNCATE TABLE http_error_log');

            $data = [
                'success' => true,
            ];

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/404/delete", name="api_seo_monitor_delete", methods={"POST"})
     */
    public function monitorDelete()
    {
        try {
            $condition = [
                'id' => 'required',
            ];

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $idsOrId = $this->request->get('id');
            $db = Db::get();

            if (is_array($idsOrId)) {
                $conditions = [];
                $placeholders = [];
                $params = [];

                foreach ($idsOrId as $id) {
                    $conditions[] = 'FIND_IN_SET(?, id)';
                    $placeholders[] = '?';
                    $params[] = $id;
                }

                $where = '(' . implode(' OR ', $conditions) . ')';
                $placeholders = implode(', ', $placeholders);

                $query = "DELETE FROM http_error_log WHERE $where";
                $db->executeQuery($query, $params);
            } else {
                $db->executeQuery('DELETE FROM http_error_log WHERE id = ?', [$idsOrId]);
            }

            $data = [
                'success' => true,
            ];

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/301/listing", name="api_seo_redirect_listing", methods={"GET", "POST"})
     *
     * {mô tả api}
     */
    public function redirectListing()
    {
        try {
            $orderByOptions = [];
            $conditions = $this->getPaginationConditions($this->request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $order_by = $this->request->get('order_by', 'creationDate');
            $order = $this->request->get('order', 'asc');

            $listing = new Redirect\Listing();

            $filter = ArrayHelper::sortArrayByField($listing->getRedirects(), $order_by, $order);

            $pagination = $this->paginator($filter, $page, $limit);

            $data = [
                'paginationData' => $pagination->getPaginationData(),
            ];

            foreach($pagination as $item) {
                if ($link = $item->getTarget()) {
                    if (is_numeric($link)) {
                        if ($doc = Document::getById((int)$link)) {
                            $item->setTarget($doc->getRealFullPath());
                        }
                    }
                }

                $data['data'][] = $item->getObjectVars();
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/301/create", name="api_seo_redirect_detail", methods={"POST"})
     */
    public function redirectCreate()
    {
        try {
            $data = $this->request->request->all();

            $redirect = new Redirect();

            $redirect = SeoServices::updateRedirect($redirect, $data);

            $data = [
                'success' => true,
                'data' => $redirect->getObjectVars(),
            ];

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/301/update", name="api_seo_redirect_update", methods={"POST"})
     */
    public function redirectUpdate()
    {
        try {
            $condition = [
                'id' => 'required',
            ];

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $data = $this->request->request->all();

            $redirect = Redirect::getById($data['id']);

            if (!$redirect) {
                return $this->sendError([
                    'success' => false,
                    'message' => 'Redirect not found',
                ]);
            }

            $redirect = SeoServices::updateRedirect($redirect, $data);

            $data = [
                'success' => true,
                'data' => $redirect->getObjectVars(),
            ];

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/301/delete", name="api_seo_redirect_delete", methods={"POST"})
     */
    public function redirectDelete()
    {
        try {
            $condition = [
                'id' => 'required',
            ];

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $idsOrId = $this->request->get('id');

            $data = [
                'success' => true,
            ];

            if (is_array($idsOrId)) {
                foreach ($idsOrId as $id) {
                    $redirect = Redirect::getById($id);
                    if (!$redirect) {
                        $data['error'][] = $id;
                        $data['success'] = false;
                    } else {
                        $redirect->delete();
                    }
                }
            } else {
                $redirect = Redirect::getById($idsOrId);
                if (!$redirect) {
                    return $this->sendError([
                        'success' => false,
                        'message' => 'Redirect not found',
                    ]);
                }
                $redirect->delete();
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
