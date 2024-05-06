<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use CorepulseBundle\Model\User;
use CorepulseBundle\Model\Role;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject;
use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Model\Notification\Service\UserService;
use CorepulseBundle\Services\UserServices;
use CorepulseBundle\Services\RoleServices;
use CorepulseBundle\Security\Hasher\CorepulseUserPasswordHasher;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends BaseController
{
    /**
     *
     * @Route("/user/profile", name="vuetify_profile", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function profile(Request $request)
    {
        $user = $this->getUser();

        $data['username'] = $user->getUsername();
        $data['name'] = $user->getName();
        $data['email'] = $user->getEmail();
        $data['avatar'] = $user->getAvatar();

        return $this->renderWithInertia('Pages/Profile', ['data' => $data]);
    }

    /**
     *
     * @Route("/user/listing", name="user_listing", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listing(Request $request, PaginatorInterface $paginator)
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

        $listing = new User\Listing();

        $listing->addConditionParam("defaultAdmin is Null Or defaultAdmin = ''");

        if ($search) {
            foreach ($search as $key => $value) {
                $listing->addConditionParam($key . " LIKE '%" . $value . "%'");
            }
        }


        $listing->setOrderKey($order_by);
        $listing->setOrder($order);

        foreach ($listing as $value) {
            $activeValue = $value->getActive() ? "Active" : "Inactive";
            $data[] = [
                'id' => $value->getId(),
                'name' => $value->getName(),
                'username' => $value->getUsername(),
                'email' => $value->getEmail(),
                'active' => $activeValue,
                // 'permission' => json_decode($value->getPermission()),
                // 'role' => $value->getRole()?->getName()
            ];
        }

        $dataLength = $listing->count();

        return $this->renderWithInertia('Pages/User/Listing', ['data' => $data, 'totalItems' => $dataLength]);
    }

    /**
     *
     * @Route("/user/create", name="user_create",methods={"GET","POST"}, options={"expose"=true}))
     */
    public function create(Request $request)
    {
        $listRole = new Role\Listing();
        $roles = [];
        foreach ($listRole as $key => $role) {
            $roles[] = [
                'id' => $role->getId(),
                'name' => $role->getName(),
            ];
        }

        if ($request->getMethod() == Request::METHOD_POST) {
            $params = $request->request->all();

            $users = new User\Listing();
            $email = $params['email'] ?? '';
            if ($email) {
                $users->addConditionParam("email = ?", $email);
                if ($users->count()) {
                    return new JsonResponse(['warning' => "Email is unique!"]);
                }
            }

            try {
                $user = UserServices::create($params, false);

                return new JsonResponse(['id' => $user->getId()]);
            } catch (\Throwable $th) {

                return new JsonResponse(['warning' => $th->getMessage()]);
            }
        }

        return $this->renderWithInertia('Pages/User/Create', ['roles' => $roles]);
    }


    /**
     *
     * @Route("/user/detail", name="user_detail", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function detail(Request $request)
    {
        $listRole = new Role\Listing();
        $roles = [];
        foreach ($listRole as $key => $role) {
            $roles[] = [
                'id' => $role->getId(),
                'name' => $role->getName(),
            ];
        }

        $user = User::getById($request->get('id'));

        if ($request->getMethod() == Request::METHOD_POST) {
            try {
                $param = $request->request->all();
                unset($param['id']);
                $result = UserServices::edit($param, $user);
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }
            return new JsonResponse(['status' => true]);
        }
        // Lấy quyền của role và quyền của user
        $rolePermission = $user->getRole() ? Role::getById($user->getRole()) ? json_decode(Role::getById($user->getRole())->getPermission(), true) : [] : [];
        $userPermission = $user->getPermission() ? json_decode($user->getPermission(), true) : [];
        if ($rolePermission == null) {
            $rolePermission = [];
        }
        // xử lý gộp quyền 
        $mergedArray = array_merge($rolePermission, $userPermission);
        $uniqueRole = array_unique($mergedArray);
        $permission = array_values($uniqueRole);
        $splitArrPermission = RoleServices::splitPermission($permission);

        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'active' => $user->getActive(),
            // 'accessibleData' => $user->getAccessibleData(),
            'permission' => $permission,
            'splitArrPermission' => $splitArrPermission,
            'rolePermission' => $rolePermission,
        ];
        // dd($data);
        return $this->renderWithInertia('Pages/User/Detail', ['user' => $data, 'roles' => $roles]);
    }

    /**
     *
     * @Route("/user/delete", name="user_delete", methods={"GET","POST"}, options={"expose"=true}))
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
                $result = UserServices::delete($id);
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     *
     * @Route("/user/edit-profile", name="vuetify_profile_edit", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function editProfile(Request $request)
    {
        $user = $this->getUser();
        $data = $request->get('data');

        if ($data) {
            $params = json_decode($data);
            $result = null;

            $result = UserServices::editProfile($params, $request, $user);

            if ($result) {
                $this->addFlash('success', 'Change info successfully');
            } else {
                $this->addFlash('errors', 'Change info user failed');
            }
        }

        return $this->redirectToRoute('vuetify_profile');
    }

    /**
     *
     * @Route("/user/edit-password", name="vuetify_profile_edit_passwword", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function editPassword(Request $request)
    {
        $user = $this->getUser();
        $data = $request->get('data');
        try {
            if ($data) {
                $params = json_decode($data);
                $result = null;

                if (property_exists($params, 'oldPassword') && property_exists($params, 'password')) {
                    $oldPassword =  $params->oldPassword;
                    $oldPassword = md5($user->getUsername() . ':corepulse:' . $oldPassword);

                    if (!password_verify($oldPassword, $user->getPassword())) {
                        return new JsonResponse(['error' => 'Your old password is incorrect']);
                    } else {
                        $result = UserServices::editProfile($params, $request, $user);
                    }
                }
                if ($result) {
                    return new JsonResponse(['success' => 'Change password successfully']);
                } else {
                    return new JsonResponse(['error' => 'Change password failed']);
                }
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }
        return new JsonResponse();
    }
}
