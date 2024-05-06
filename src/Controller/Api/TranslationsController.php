<?php

namespace CorepulseBundle\Controller\Api;

use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use CorepulseBundle\Services\AssetServices;
use DateTime;
use Pimcore\Db;
use CorepulseBundle\Services\TranslationsServices;
use Pimcore\Tool;

/**
 * @Route("/translations")
 */
class TranslationsController extends BaseController
{
    /**
     * @Route("/listing", name="api_trans_listing", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function listingAction(
        Request $request,
        PaginatorInterface $paginator): JsonResponse
    {
        try {
            $this->setLocaleRequest();

            $orderByOptions = ['creationDate'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'search' => '',
            ]);
            $messageError = $this->validator->validate($condition, $request);
            if($messageError) return $this->sendError($messageError);

            $languages = Tool::getValidLanguages();

            if ($limit) {
                if ($limit == "-1") {
                    $limit = 999999999;
                }
                $limit = (int)count($languages) * (int)$limit;
            } else {
                $limit = 50;
            }
            $offset = ($page - 1) * $limit;

            $queryBuilder  = Db::getConnection()->createQueryBuilder();
            $queryBuilder->from('translations_messages', 'trans');
            $queryBuilder->addSelect(['trans.key']);
            $queryBuilder->addSelect(['trans.text']);
            $queryBuilder->addSelect(['trans.language']);
            $queryBuilder->setFirstResult($offset);
            $queryBuilder->setMaxResults($limit);

            $dataAll = $queryBuilder->execute()->fetchAll();
            $dataLength = count($dataAll)/2;

            $list = $queryBuilder->execute()->fetchAll();
            $paginationData = $this->helperPaginator($paginator, $list, $page, $limit);
            $data = array_merge(
                [
                    'data' => []
                ],
                $paginationData,
            );

            foreach ($list as $row) {
                $key = $row['key'];
                $language = $row['language'];

                if (!array_key_exists($key,  $data['data'])) {
                    $data['data'][$key] = ["id" => $key];
                }

                $data['data'][$key][$language] = $row['text'];
            }

            $data['data'] = array_values($data['data']);

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    // Trả ra dữ liệu
    public function listingResponse($data, $item)
    {
        $key = $item['key'];
        $language = $item['language'];

        if (!array_key_exists($key, $data)) {
            $data[$key] = ["id" => $key];
        }

        $data[$key][$language] = $item['text'];

        return $data;
    }
}