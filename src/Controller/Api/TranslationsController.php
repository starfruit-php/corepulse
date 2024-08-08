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

    /**
     * @Route("/create", name="api_trans_create", methods={"POST"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createAction( Request $request ): JsonResponse
    {
        try {
            // $condition = [
            //     'key' => 'required',
            // ];

            // $errorMessages = $this->validator->validate($condition, $request);
            // if ($errorMessages) return $this->sendError($errorMessages);

            $data = $request->getContent(); // Lấy dữ liệu JSON từ raw
            $data = json_decode($data, true);

            $key = $data['key'];
            if (!$key) {
                return $this->sendError('key.is.not.null');
            }
            // $queryBuilder = Db::getConnection()->createQueryBuilder();
            // $queryBuilder
            //     ->where('`key` = :key')
            //     ->setParameter('key', $key);
            // $item = $queryBuilder->execute();
          
            // if (!$item) {
                $result = '';
    
                foreach ($data['languages'] as $lang => $text) {
                    $queryBuilder = Db::getConnection()->createQueryBuilder();
                    $queryBuilder
                        ->insert('translations_messages')
                        ->setValue('`type`', 'type')
                        ->setValue('`key`', ':key')
                        ->setValue('`text`', ':text')
                        ->setValue('`language`', ':language')
                        ->setValue('`creationDate`', ':creationDate')
                        ->setValue('`modificationDate`', ':modificationDate')
                        ->setValue('`userOwner`', ':userOwner')
                        ->setValue('`userModification`', ':userModification')
                        ->setParameter('type', 'simple')
                        ->setParameter('key', $key)
                        ->setParameter('text', $text)
                        ->setParameter('language', $lang)
                        ->setParameter('creationDate', time())
                        ->setParameter('modificationDate', time())
                        ->setParameter('userOwner', 0)
                        ->setParameter('userModification', 0);
        
                    $result = $queryBuilder->execute();
                }
    
                if ($result) {
                    return $this->sendResponse("create.trans.success");
                } else {
                    return $this->sendError('create.trans.error');
                }
            // }

            return $this->sendError('trans.already.exist');

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

     /**
     * @Route("/update", name="api_trans_update", methods={"POST"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateAction( Request $request ): JsonResponse
    {
        try {
            $data = $request->getContent(); // Lấy dữ liệu JSON từ raw
            $data = json_decode($data, true);

            $key = $data['key'];
            if (!$key) {
                return $this->sendError('key.is.not.null');
            }
            // $queryBuilder = Db::getConnection()->createQueryBuilder();
            // $queryBuilder
            //     ->select('*')
            //     ->from('translations_messages') 
            //     ->where('`key` = :key')
            //     ->setParameter('key', $key);
            // $item =  $queryBuilder->execute()->fetchAll();
         
            // if ($item) {
                $result = '';

                unset($data['key']);
                foreach ($data as $lang => $text) {
                   
                    $queryBuilder = Db::getConnection()->createQueryBuilder();
                    $queryBuilder
                        ->update('translations_messages')
                        ->set('`text`', ':text')
                        ->where('`key` = :key')
                        ->andWhere('`language` = :language')
                        ->setParameter('text', $text)
                        ->setParameter('key', $key)
                        ->setParameter('language', $lang);
        
                    $result = $queryBuilder->execute();
                }
               
                return $this->sendResponse("update.trans.success");
            
            // }

            return $this->sendError('trans.not.found');

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/delete", name="api_trans_delete", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function deleteAction( Request $request ): JsonResponse
    {
        try {
            $condition = [
                'key' => 'required',
            ];

            $errorMessages = $this->validator->validate($condition, $request);
            if ($errorMessages) return $this->sendError($errorMessages);

            $id = $request->get('key');

            if (is_array($id)) {
                try {
                    foreach ($id as $item) {
                        $result = TranslationsServices::delete($item);
                    }
                } catch (\Throwable $th) {
                    return $this->sendError($th->getMessage(), 500);
                }
            } else {
                $result = TranslationsServices::delete($id);
    
                if ($result) {
                } else {
                    return $this->sendError('Can not find item to be deleted');
                }
            }

            return $this->sendResponse("Delete item success");

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