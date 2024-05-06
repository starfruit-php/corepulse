<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Parent_;
use ValidatorBundle\Validator\Validator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Pimcore\Model\DataObject\Role;
use Pimcore\Db;
use CorepulseBundle\Services\TranslationsServices;
use Symfony\Component\HttpFoundation\JsonResponse;

class TranslationsController extends BaseController
{
    /**
     *
     * @Route("/translations/listing", name="translations_listing", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listing(TranslatorInterface $translator, Request $request)
    {
        $data = [];
        $dataLength = 0;
        $langs = \Pimcore\Tool::getValidLanguages();
        if ($request->isMethod("POST")) {

            $search = json_decode($request->get('search'));

            $page = $request->get('page');
            $limit = $request->get('limit');

            $order_by = $request->get('orderKey');
            $order = $request->get('order');

            $search = $request->get('search');

            $messageError = $this->validate([
                'page' => $page ? 'numeric|positive' : '',
                // 'limit' => $limit ? 'numeric|positive' : '',
                'order_by' => $order_by ? 'choice:key' : '',
                'order' => $order ? 'choice:desc,asc' : ''
            ]);

            if (empty($page)) $page = 1;
            if ($limit) {
                if ($limit == "-1") {
                    $limit = 999999999;
                }
                $limit = 2 * (int)$limit;
            } else {
                $limit = 50;
            }
            if (empty($order_by)) $order_by = 'creationDate';
            if (empty($order)) $order = 'desc';
            $offset = ($page - 1) * $limit;

            if ($messageError) {
                return $this->addFlash('error', 'Lỗi lấy dữ liệu');
            } else {

                $queryBuilder  = Db::getConnection()->createQueryBuilder();

                $queryBuilder->from('translations_messages', 'trans');

                $queryBuilder->addSelect(['trans.key']);
                $queryBuilder->addSelect(['trans.text']);
                $queryBuilder->addSelect(['trans.language']);

                // Add search condition if a key is provided
                if ($search) {
                    foreach (json_decode($search, true) as $key => $value) {
                        if ($value) {
                            if ($key == 'id') {
                                $queryBuilder->andWhere($queryBuilder->expr()->like('trans.key', ':searchKey'))
                                    ->setParameter('searchKey', '%' . $value . '%');
                            } else {
                                $query  = Db::getConnection()->createQueryBuilder();

                                $query->from('translations_messages', 'trans');

                                $query->addSelect(['trans.key']);
                                $query->addSelect(['trans.text']);
                                $query->addSelect(['trans.language']);
                                $query->andWhere($query->expr()->eq('trans.language', ':language'))
                                    ->andWhere($query->expr()->like('trans.text', ':searchText'))
                                    ->setParameter('language', $key)
                                    ->setParameter('searchText', '%' . $value . '%');
                                $item = $query->execute()->fetchAll();
                                $arrKey = [];
                                foreach ($item as $v) {
                                    $arrKey[] = "'" . $v['key'] . "'";
                                }
                                $queryBuilder->andWhere($queryBuilder->expr()->in('trans.key', $arrKey));
                            }
                        }
                    }
                }

                // $queryBuilder->orderBy($order_by, $order);

                $dataAll = $queryBuilder->execute()->fetchAll();
                $dataLength = count($dataAll)/2;
                
                $queryBuilder->setFirstResult($offset);
                $queryBuilder->setMaxResults($limit);

                $result = $queryBuilder->execute()->fetchAll();

                $data = [];

                foreach ($result as $row) {
                    $key = $row['key'];
                    $language = $row['language'];

                    if (!array_key_exists($key, $data)) {
                        $data[$key] = ["id" => $key];
                    }

                    $data[$key][$language] = $row['text'];
                }

                $allLanguages = array_unique(array_column($result, 'language'));

                foreach ($result as &$record) {
                    foreach ($allLanguages as $language) {
                        if (!isset($record[$language])) {
                            $record[$language] = "";
                        }
                    }
                }
                // dd($result);
                $data = array_values($data);
            }
        }
        $formData = [
            'key' => '',
            'languages' => array_combine($langs, array_fill(0, count($langs), '')),
        ];

        $viewData = ['metaTitle' => 'Translations'];

        return $this->renderWithInertia('Pages/Translations/Listing', [
            'data' => $data, 
            'totalItems' => $dataLength, 
            'lang' => $langs, 
            'formData' => $formData,
        ], $viewData);
    }

    /**
     *
     * @Route("/translations/edit", name="translations_edit",methods={"GET","POST"}, options={"expose"=true}))
     */
    public function edit(Request $request)
    {
        try {
            if ( $request->get('data') && $request->get('key')) {
                $data = json_decode($request->get('data'));
                $result = TranslationsServices::edit($data, $request->get('key'));

                if ($result) {
                    $this->addFlash('success', 'Cập nhật thành công');
                } else {
                    $this->addFlash('error', 'Cập nhật không thành công');
                }
            }

            if ($request->get('name')) { 
                $lang = $request->get('name');
                $text = $request->get('value');
                $key = $request->get('id');

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
                if ($result == 1) {
                    $this->addFlash('success', 'Cập nhật thành công');
                } else {
                    $this->addFlash('error', 'Cập nhật không thành công');
                }
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }
        return new JsonResponse();

        // return $this->redirectToRoute('translations_listing');
    }

    /**
     *
     * @Route("/translations/create", name="translations_create",methods={"GET","POST"}, options={"expose"=true}))
     */
    public function create(Request $request)
    {
        if ($request->getMethod() == Request::METHOD_POST) {
            $messageError = $this->validate([
                'key' => 'required',
            ]);

            if ($messageError) {
                $this->addFlash('errors', $messageError);
            } else {
                $data = json_decode($_POST['data']);
                
                $result = TranslationsServices::create($data, $request->get('key'));

                if ($result) {
                    $this->addFlash('success', 'Thêm mới thành công');

                    return $this->renderWithInertia('Pages/Translations/Listing');
                } else {
                    $this->addFlash('error', 'Thêm mới không thành công');
                }
            }
        }

        return $this->renderWithInertia('Pages/Translations/Listing');
    }

    /**
     *
     * @Route("/translations/delete", name="translations_delete", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function delete(Request $request)
    {
        $id = $request->get('id');
        if (is_array($id)) {
            try {
                foreach ($id as $item) {
                    $result = TranslationsServices::delete($item);
                }
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }
            return new JsonResponse();
        } else {
            $result = TranslationsServices::delete($id);

            if ($result) {
                $this->addFlash('success', 'Xóa thành công');
                return $this->renderWithInertia('Pages/Translations/Listing');
            } else {
                $this->addFlash('error', 'Xóa không thành công');
            }
    
            return $this->renderWithInertia('Pages/Translations/Listing', ["result" => $result]);
        }

        
    }
}
