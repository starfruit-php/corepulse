<?php

namespace CorepulseBundle\Controller\Admin;

use CorepulseBundle\Services\AssetServices;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use CorepulseBundle\Services\Helper\SearchHelper;

class ImageController extends BaseController
{
    /**
     * @Route("/save-image-admin", name="save_image_admin", methods={"POST"}, options={"expose"=true}))
     */
    public function saveImageAction(Request $request)
    {
        $param = $request->files->get('test');
        $parentId = $request->get('parentId');
        $image = $this->saveImage($param, $parentId);

        $data = '';
        if ($image) {
            $data = $image->getFullPath();
        }

        return new JsonResponse(['files' => $data]);
    }

    static public function saveImage($value, $parentId = null)
    {
        if ($value) {
            $path = "/Temp/";
            if ($parentId) {
                $item = Asset::getById($parentId);
                if ($item) {
                    $path = '/' . $item->getFilename() . '/';
                }
            }
            $newAsset = new \Pimcore\Model\Asset();
            $filename = time() . '-' . $value->getClientOriginalName();

            // convent filename
            $filename = preg_replace('/[^a-zA-Z0-9.]/', '-', $filename);
            $filename = preg_replace('/-+/', '-', $filename);
            $filename = trim($filename, '-');
            $newAsset->setFilename($filename);

            $valueFolder = Asset::getByPath($path) ?? Asset\Service::createFolderByPath($path);
            $newAsset->setParent($valueFolder);
            $newAsset->setData(file_get_contents($value));
            $newAsset->save();
            $image = Asset\Image::getById($newAsset->getId());

            return $image;
        } else {
            return '';
        }
    }


    /**
     * @Route("/get-asset-admin", name="get_asset_admin", methods={"POST"}, options={"expose"=true}))
     */
    public static function getAssetAction(Request $request)
    {
        $parentId = $request->get('id');
        $types = $request->get('types');
        $search = $request->get('search');

        $pathFolder = $request->get('path');

        if ($pathFolder) {
            $item = Asset::getByPath($pathFolder);
            if ($item) {
                $parentId = $item->getId();
            }
        }

        $conditionQuery = 'id != 1';
        $conditionParams = [];

        $nameFoldes = 'Home';
        $nameParent = [];
        if ($parentId && $parentId != 'null') {
            $conditionQuery .= ' AND parentId = :parentId';
            $conditionParams['parentId'] = $parentId;

            if ($parentId == 1) {
                $types = $types ? $types : "image,pdf,txt,document,video";
            }

            $parentInfo = Asset::getById($parentId);
            $nameFoldes = $parentInfo->getFileName();
            $pathParent = $parentInfo->getPath() . $parentInfo->getFileName();
            $nameParent = explode('/', $pathParent);
            $result = array_filter($nameParent, function ($nameParent) {
                return !empty($nameParent);
            });
            $nameParent = [];
            foreach ($result as $val) {
                $idChill = '';
                if (strpos($parentInfo->getPath(), $val) !== false) {
                    $substring = substr($parentInfo->getPath(), 0, strpos($parentInfo->getPath(), $val)) . $val;
                    $asset = Asset::getByPath($substring);
                    if ($asset) {
                        $idChill = $asset->getId();
                    }
                }
                $nameParent[] = [
                    'id' => $idChill,
                    'name' => $val,
                    'end' =>  $val == end($result),
                ];
            }
        } 

        if ($types) {
            $types = explode(',', $types);
            $conditionTypes = '';
            for ($i = 0; $i < count($types); $i++) {
                $or = " OR ";
                if ($i == (count($types) - 1)) {
                    $or = '';
                }
                $conditionName = "type = '" . $types[$i] . "'" . $or;
                $conditionTypes .= $conditionName;
            }
            $conditionQuery .= ' AND (' . $conditionTypes . ')';
        }

        if (isset($search) && !empty($search)) {
            $conditionQuery .= ' AND LOWER(filename) LIKE LOWER(:search) AND type != "folder"';
            $conditionParams['search'] = '%' . $search . '%';
        }

        $listingAsset = new \Pimcore\Model\Asset\Listing();
        $listingAsset->setCondition($conditionQuery, $conditionParams);
        $listingAsset->setOrderKey('mimetype');
        $listingAsset->setOrder('ASC');

        $images = [];

        foreach ($listingAsset as $item) {
            $publicURL = AssetServices::getThumbnailPath($item);
            $images[] = [
                'id' => $item->getId(),
                'type' => $item->getType(),
                'mimetype' => $item->getMimetype(),
                'name' => $item->getFileName(),
                'fullPath' => $publicURL,
                'parentId' => $item->getParentId(),
                'checked' => false,
                'path' => $item->getFullPath(),
            ];
        };

        return new JsonResponse(['data' => $images, 'nameParent' => $nameParent]);
    }

    /**
     * @Route("/get-folder-admin", name="get_folder_admin", methods={"POST"}, options={"expose"=true}))
     */
    public static function getFolderAction(Request $request)
    {
        $conditionQuery = 'id != 1 AND parentId = :parentId';
        $conditionParams = [
            'parentId' => 1,
        ];

        $list = new \Pimcore\Model\Asset\Listing();
        $list->setCondition($conditionQuery, $conditionParams);
        $list->setOrderKey('creationDate');
        $list->setOrder('ASC');
        $list->load();

        $folders[] = [
            'id' => 1,
            'name' => 'Home',
        ];
        $images = [];
        foreach ($list as $item) {
            if ($item->getType() == "folder") {
                $folders[] = [
                    'id' => $item->getId(),
                    'name' => $item->getFilename(),
                ];
            } else {
                $publicURL = AssetServices::getThumbnailPath($item);
                $images[] = [
                    'id' => $item->getId(),
                    'type' => $item->getType(),
                    'mimetype' => $item->getMimetype(),
                    'name' => $item->getFileName(),
                    'fullPath' =>  $publicURL,
                    'parentId' => $item->getParentId(),
                    'path' => $item->getFullPath(),
                ];
            }
        }

        return new JsonResponse(['folders' => $folders, 'images' => $images]);
    }

    /**
     * @Route("/search-folder-admin", name="search_folder_admin", methods={"POST"}, options={"expose"=true}))
     */
    public static function searchFolder(Request $request)
    {
        $conditionQuery = "id != 1 AND parentId = :parentId AND type = 'folder'";
        $conditionParams = [
            'parentId' => 1,
        ];

        $search = $request->get('search');
        if ($search) {
            $conditionQuery .= ' AND filename LIKE :search';
            $conditionParams['search'] = '%' . $search . '%';
        }

        $folders[] = [
            'id' => 1,
            'name' => 'Home',
        ];

        $list = new \Pimcore\Model\Asset\Listing();
        $list->setCondition($conditionQuery, $conditionParams);
        $list->setOrderKey('creationDate');
        $list->setOrder('ASC');
        $list->load();
        foreach ($list as $item) {
            $folders[] = [
                'id' => $item->getId(),
                'name' => $item->getFilename(),
            ];
        }
        return new JsonResponse(['data' => $folders]);
    }

    /**
     * @Route("/tree-listing-asset-admin", name="tree_listing_asset_admin", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeListingAction(Request $request)
    {
        $datas = [];
        $root = $request->get('root');
        $listing = new Asset\Listing();
        $listing->setCondition('`parentId` = 0 OR `parentId` = 1 AND type = "folder"');
        $listing->setOrderKey('mimetype');
        $listing->setOrder('ASC');

        foreach ($listing as $item) {
            $data = [];
            foreach ($item->getChildren() as $children) {
                if ($children->getType() == "folder") {
                    $data[] = (string)$children->getId();
                }
            }

            $publicURL = AssetServices::getThumbnailPath($item);

            if ($item->getId() == 1) {
                $datas['home'] = [
                    'id' => 1,
                    'key' =>  "Home",
                    'type' => $item->getType(),
                    'children' => [],
                    'icon' => "mdi-home",
                    'image' => "/bundles/pimcoreadmin/img/flat-color-icons/home-gray.svg",
                    'publish' => true,
                    'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                ];
            }

            $datas['nodes'][(string)$item->getId()] = [
                'id' => $item->getId(),
                'key' => $item->getFileName() ? $item->getFileName() : "Home",
                'type' => $item->getType(),
                'children' => $data,
                'icon' => SearchHelper::getIcon($item->getType()),
                'image' => $publicURL,
                'publish' => true,
                'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
            ];
        }
        // dd($datas);
        $datas['config'] = [
            'roots' => $datas['nodes'][1]['children'],
            'keyboardNavigation' => false,
            'dragAndDrop' => false,
            'checkboxes' => false,
            'editable' => false,
            'disabled' => false,
            'padding' => 35,
        ];

        return new JsonResponse($datas);
    }
}
