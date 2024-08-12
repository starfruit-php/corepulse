<?php

namespace CorepulseBundle\Controller\Admin;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\ClassDefinition;
use CorepulseBundle\Controller\Cms\FieldController;

use CorepulseBundle\Services\UserServices;
use CorepulseBundle\Model\User;
use CorepulseBundle\Services\ClassServices;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/vuetify/setting")
 */
class SettingController extends BaseController
{
    const listField = [
        "input", "textarea", "wysiwyg", "password",
        "number", "numericRange", "slider", "numeric",
        "date", "datetime", "dateRange", "time", "manyToOneRelation",
        "select"
    ];

    const chipField = [
        "select", "multiselect", "manyToOneRelation", "manyToManyObjectRelation", "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"
    ];

    const relationField = [
        "manyToOneRelation", "manyToManyObjectRelation", "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"
    ];

    const relationsField = [];

    /**
     * @Route("/login", name="vuetify_setting_login", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function settingLogin(Request $request)
    {
        $loginSetting = self::getData('login');

        if ($request->isMethod('POST')) {
            if ($request->files->get('logo')) {
                $file = $request->files->get('logo')[0];

                $newAsset = self::saveAsset($file);

                $loginSetting['config']['logo'] = $newAsset->getFullPath();
            }

            if ($request->files->get('background')) {
                $file = $request->files->get('background')[0];

                $newAsset = self::saveAsset($file);

                $loginSetting['config']['background'] = $newAsset->getFullPath();
            }

            if ($request->files->get('logo') || $request->files->get('background')) {
                Db::get()->update(
                    'vuetify_settings',
                    [
                        'config' => json_encode($loginSetting['config']),
                    ],
                    [
                        'type' => 'login',
                    ]
                );
            }
        }

        return $this->renderWithInertia('Admin/LoginSetting', ['data' => $loginSetting['config']]);
    }

    /**
     * @Route("/object", name="vuetify_setting_object", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectLogin(Request $request)
    {
        $user = new User\Listing();
        $user->addConditionParam('defaultAdmin = 1');
        $user = $user->current();

        $objectSetting = self::getData('object');
        $blackList = ["user", "role"];

        $chipsColor = [
            'checked' => [
                'publish' => 'primary',
                'unpublish' => 'red',
            ],
        ];

        //get data logo and backgoroud
        $loginSetting = self::getData('login');

        $data = $this->getObjectSetting($blackList, $objectSetting['config']);

        if ($request->isMethod('POST')) {
            $dataPost = $request->get("data");

            $userPost = $request->get("user");

            if ($userPost && !$user) {
                $user = UserServices::create($userPost, true);
            } elseif ($userPost && $user) {
                $user = UserServices::edit($userPost, $user);
            }

            if ($dataPost) {
                $dataSave = [];
                foreach ($dataPost as $item) {
                    if ($item['checked'] == "1")
                        $dataSave[] = $item['id'];
                }

                Db::get()->update(
                    'vuetify_settings',
                    [
                        'config' => json_encode($dataSave),
                    ],
                    [
                        'type' => 'object',
                    ]
                );

                $data = $this->getObjectSetting($blackList, $dataSave);
            }

            // update logo and backgroud
            $fields = ['logo', 'background', 'color', 'title', 'colorLight', 'footer'];

            foreach ($fields as $field) {
                if ($request->get($field)) {
                    $value = $request->get($field);
                    $loginSetting['config'][$field] = $value;
                }
            }

            // save data
            if ($request->get('logo') || $request->get('background') || $request->get('color')) {
                Db::get()->update(
                    'vuetify_settings',
                    [
                        'config' => json_encode($loginSetting['config']),
                    ],
                    [
                        'type' => 'login',
                    ]
                );
            }
            $this->addFlash("success", 'Save Success');
        }

        foreach ($data as $item) {
            $classDefinition = ClassDefinition::getById($item['id']);
            $dataNew[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'title' => $classDefinition ? $classDefinition->getTitle() : '',
                'checked' => $item['checked'] == true ? 'publish' : 'unpublish',
            ];
        };

        if ($user) {
            $user = [
                'username' => $user->getUsername(),
            ];
        }

        if ($loginSetting['config']) {
            if ($loginSetting['config']['background']) {
                $image = Asset::getByPath($loginSetting['config']['background']);
                $loginSetting['config']['background'] = $image ? $loginSetting['config']['background'] : '/bundles/pimcoreadmin/img/login/pc11.svg';
            }
        }

        return $this->renderWithInertia('Admin/ObjectSetting', [
            'user' => $user,
            'data' => $dataNew,
            'appearance' => $loginSetting['config'],
            'chips' => ['checked'],
            'chipsColor' => $chipsColor,
            'totalItems' => count($data),
        ]);
    }

    public function getObjectSetting($blackList, $objectSetting)
    {
        // lấy danh sách bảng
        $query = 'SELECT * FROM `classes` WHERE id NOT IN ("' . implode('","', $blackList) . '")';
        $classListing = Db::get()->fetchAllAssociative($query);
        $data = [];
        foreach ($classListing as $class) {
            $data[] = [
                "id" => $class['id'],
                "name" => $class['name'],
                "checked" => in_array($class['id'], $objectSetting)
            ];
        }
        return $data;
    }

    /**
     * @Route("/public-object", name="vuetify_setting_public_object", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function publicObject(Request $request)
    {
        $status = $request->get('publish');
        try {
            if ($status) {
                $dataNew = $request->get('id');

                // lấy danh sách các object đã được hiển thị
                $objectSetting = self::getData('object');
                $dataOld = $objectSetting['config'];

                // tạo danh sách object mới để lưu lại
                $dataSave = [];
                if ($status == 'publish') {
                    $dataSave = array_unique(array_merge($dataOld, $dataNew));
                } elseif ($status == 'unpublish') {
                    $dataSave = array_diff($dataOld, $dataNew);
                    $dataSave = array_values($dataSave);
                }

                $dataSave = self::save($dataSave);
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }
        return new JsonResponse();
    }

    /**
     * @Route("/detail-object", name="vuetify_setting_detail_object", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function detailObject(Request $request)
    {
        $classId = $request->get('id');

        $classDefinition = ClassDefinition::getById($classId);

        $dataJson = [
            "id" => $classDefinition->getId(),
            "name" => $classDefinition->getName(),
            "title" => $classDefinition->getTitle() ? $classDefinition->getTitle() :  $classDefinition->getName()
        ];
        $string = '\\Pimcore\\Model\\DataObject\\' . ucfirst($classId);
        $object = new $string;
        $data = FieldController::getData($object);
        $classes = $object->getClass();

        $breadcrumb = [
            'root' => [
                'title' => $classes->getTitle() ? $classes->getTitle() : $classes->getName(),
                'id' => $classes->getId(),
            ],
            'item' => $object->getKey(),
        ];

        return $this->renderWithInertia('Admin/ObjectSettingDetail', [
            'data' => $data,
            'dataJson' => $dataJson,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * @Route("/edit-object", name="vuetify_setting_edit_object", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function editObject(Request $request)
    {
        $dataPost = $request->get("data");
        try {
            if ($dataPost) {
                // lấy danh sách các object đã được hiển thị
                $objectSetting = self::getData('object');
                $dataOld = $objectSetting['config'];
                $dataPost = json_decode($dataPost);
                $dataNew[] = $dataPost->id;
                $dataSave = [];
                if ($dataPost->checked == 'publish') {
                    $dataSave = array_unique(array_merge($dataOld, $dataNew));
                } elseif ($dataPost->checked == 'unpublish') {
                    $dataSave = array_diff($dataOld, $dataNew);
                    $dataSave = array_values($dataSave);
                }

                $dataSave = self::save($dataSave);

                $classDefinition = ClassDefinition::getById($dataPost->id);

                if ($classDefinition) {
                    if ($dataPost->title) {
                        $classDefinition->setTitle($dataPost->title);
                        $classDefinition->save();
                    }
                }

                $this->addFlash("success", 'Save Success');
            } else {
                $this->addFlash("error", 'Save failed');
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }
        return new JsonResponse();
    }

    // lưu hình ảnh mới vào asset
    public static function saveAsset($file)
    {
        $filename = $file->getClientOriginalName();
        $path = $file->getPathname();

        $newAsset = new \Pimcore\Model\Asset();
        $newAsset->setFilename(time() . '-' . $filename);
        $newAsset->setData(file_get_contents($path));
        $newAsset->setParent(\Pimcore\Model\Asset::getByPath("/"));
        $newAsset->save();

        return $newAsset;
    }

    // get data with obect or login
    public static function getData($type)
    {
        $item = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "' . $type . '"', []);
        if (!$item) {
            Db::get()->insert('vuetify_settings', [
                'type' => $type,
            ]);
            $item = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "' . $type . '"', []);
        }
        if ($item['config']) {
            $item['config'] = json_decode($item['config'], true);
        } else {
            $item['config'] = [];
        }

        return $item;
    }

    public static function save($dataSave)
    {
        foreach ($dataSave as $classId) {
            $params = ClassServices::examplesAction($classId);
            $update = ClassServices::updateTable($classId, $params);
        }

        Db::get()->update(
            'vuetify_settings',
            [
                'config' => json_encode($dataSave),
            ],
            [
                'type' => 'object',
            ]
        );

        return $dataSave;
    }
}
