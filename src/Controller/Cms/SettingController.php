<?php

namespace CorepulseBundle\Controller\Cms;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\ClassDefinition;
use CorepulseBundle\Controller\Cms\FieldController;

/**
 * @Route("/setting")
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
     * @Route("/setting", name="vuetify_setting_cms", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectLogin(Request $request)
    {
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

        foreach ($data as $item) {
            $classDefinition = ClassDefinition::getById($item['id']);
            $dataNew[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'title' => $classDefinition ? $classDefinition->getTitle() : '',
                'checked' => $item['checked'] == true ? 'publish' : 'unpublish',
            ];
        };

        return $this->renderWithInertia('Pages/Setting/Setting', [
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
     * @Route("/edit-setting", name="vuetify_setting_edit", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function editSetting(Request $request)
    {
        $params = [
            'csrfToken' => $request->get('csrfToken'),
        ];

        $blackList = ["user", "role"];
        $dataPost = $request->get("data");
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
        //logo
        if ($request->get('logo')) {
            $file = $request->get('logo');
            $loginSetting['config']['logo'] = $file;
        }
        //backgroud
        if ($request->get('background')) {
            $file = $request->get('background');
            $loginSetting['config']['background'] = $file;
        }
        //color
        if ($request->get('color')) {
            $file = $request->get('color');
            $loginSetting['config']['color'] = $file;
        }
        //title
        if ($request->get('title')) {
            $file = $request->get('title');
            $loginSetting['config']['title'] = $file;
        }
        //colorlight
        if ($request->get('colorLight')) {
            $file = $request->get('colorLight');
            $loginSetting['config']['colorLight'] = $file;
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

        return $this->redirectToRoute('vuetify_setting_cms', $params);
    }

    /**
     * @Route("/public-object", name="vuetify_setting_public_object_cms", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function publicObject(Request $request)
    {
        $status = $request->get('publish');

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

            // lưu dữ liệu
            Db::get()->update(
                'vuetify_settings',
                [
                    'config' => json_encode($dataSave),
                ],
                [
                    'type' => 'object',
                ]
            );
        }
        $params = [
            'csrfToken' => $request->get('csrfToken'),
        ];

        return $this->redirectToRoute('vuetify_setting_cms', $params);
    }

     /**
     * @Route("/edit-object-cms", name="vuetify_setting_edit_object_cms", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function editObject(Request $request)
    {
        $dataPost = $request->get("data");
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

                 // lưu dữ liệu
                Db::get()->update(
                    'vuetify_settings',
                    [
                        'config' => json_encode($dataSave),
                    ],
                    [
                        'type' => 'object',
                    ]
                );

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
        $params = [
            'csrfToken' => $request->get('csrfToken'),
        ];

        return $this->redirectToRoute('vuetify_setting_cms', $params);
    }

    // lưu hình ảnh mới vào asset
    public static function saveAsset($file) {
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
    public static function getData($type) {
        $item = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "'. $type .'"', []);
        if (!$item) {
            Db::get()->insert('vuetify_settings', [
                'type' => $type,
            ]);
            $item = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "'. $type .'"', []);
        }
        if ($item['config']) {
            $item['config'] = json_decode($item['config'], true);
        } else {
            $item['config'] = [];
        }

        return $item;
    }
}
