<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use CorepulseBundle\Model\Role;
use CorepulseBundle\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Parent_;
use ValidatorBundle\Validator\Validator;
use Symfony\Component\HttpFoundation\RequestStack;
use CorepulseBundle\Services\RoleServices;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoleController extends BaseController
{
    /**
     *
     * @Route("/role/listing", name="role_listing", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listing(Request $request)
    {
        $data = [];
        $dataLength = 0;

        $search = json_decode($request->get('search'));

        $page = $request->get('page');
        $limit = $request->get('limit');

        $order_by = $request->get('orderKey');
        $order = $request->get('order');

        $messageError = $this->validate([
            'page' => $page ? 'numeric|positive' : '',
            'limit' => $limit ? 'numeric|positive' : '',
            'order_by' => $order_by ? 'choice:title' : '',
            'order' => $order ? 'choice:desc,asc' : ''
        ]);

        if (empty($page)) $page = 1;
        if (empty($limit)) $limit = 10;
        if (empty($order_by)) $order_by = 'name';
        if (empty($order)) $order = 'desc';

        if ($messageError) {
            return $this->addFlash('error', 'Lỗi lấy dữ liệu');
        } else {

            $listing = new Role\Listing();
            if ($search) {
                foreach ($search as $key => $value) {
                    $listing->addConditionParam($key . " LIKE '%" . $value . "%'");
                }
            }

            $listing->setOrderKey($order_by);
            $listing->setOrder($order);

            foreach ($listing as $value) {
                $data[] = [
                    'id' => $value->getId(),
                    'name' => $value->getName(),
                ];
            }

            $dataLength = $listing->count();
        }

        return $this->renderWithInertia('Pages/Role/Listing', [
            'data' => $data,
            'totalItems' => $dataLength
        ]);
    }

    /**
     *
     * @Route("/role/create", name="role_create",methods={"GET","POST"}, options={"expose"=true}))
     */
    public function create(Request $request)
    {
        $objectKey = $request->get('objectKey');

        $role = new Role;

        try {
            \Pimcore\Model\Version::enable();

            $role->setName($objectKey);

            $role->save();
            \Pimcore\Model\Version::disable();
            return new JsonResponse(['id' => $role->getId()]);
        } catch (\Throwable $th) {

            return new JsonResponse(['warning' => $th->getMessage()]);
        }

        return $this->renderWithInertia('Pages/Role/Listing');
    }


    /**
     *
     * @Route("/role/detail/{id}", name="role_detail", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function detail(Request $request)
    {
        $role = Role::getById($request->get('id'));
        $tab = $request->get('tab') ?? 'information';

        if ($request->getMethod() == Request::METHOD_POST) {

            try {
                $params = $request->request->all();
                unset($params['id']);
                unset($params['splitArrPermission']);

                $result = RoleServices::edit($params, $role);
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }
            return new JsonResponse(['status' => true]);
        }

        $permission = $role->getPermission() ? json_decode($role->getPermission(), true) : [];

        $splitArrPermission = RoleServices::splitPermission($permission);

        $data = [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'permission' => $permission,
            'splitArrPermission' => $splitArrPermission,
        ];
        return $this->renderWithInertia('Pages/Role/Detail', ['role' => $data, 'tab' => $tab]);
    }

    /**
     *
     * @Route("/role/delete", name="role_delete", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function delete(Request $request)
    {
        $messageError = $this->validate([
            'id' => 'required',
        ]);
        if ($messageError) {
            return $this->addFlash('error', $messageError);
        } else {
            $id = $request->get('id');
            try {
                $result = RoleServices::delete($id);
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     *
     * @Route("/role/user_check_assigned", name="user_check_assigned", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function checkUser(Request $request)
    {
        $id = json_decode($request->get('id'), true);

        $userNames = [];

        if (is_array($id)) {
            foreach ($id as $roleId) {
                $users = new User\Listing();
                $users->addConditionParam('role = ? ', $roleId);
                foreach ($users as $user) {
                    $userNames[] = $user->name;
                }
            }
        } else {
            $users = new User\Listing();
            $users->addConditionParam('role = ? ', $id);
            foreach ($users as $user) {
                $userNames[] = $user->name;
            }
        }

        $userNamesString = implode(', ', $userNames);
        return new JsonResponse(['data' => $userNamesString]);
    }
}
