<?php

namespace CorepulseBundle\Controller\Cms;

use CorepulseBundle\Services\AssetServices;
use CorepulseBundle\Services\Helper\SearchHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ImageController extends BaseController
{
    /**
     * @Route("/save-image", name="save_image", methods={"POST"}, options={"expose"=true}))
     */
    public function saveImageAction(Request $request)
    {
        $param = $request->files->get('test');
        $infoFile = $request->get('test');

        if ($param->getError() == 0) {
            $infoFile = json_decode($infoFile);
            $folderId = $infoFile->parentId ?  $infoFile->parentId : 1;

            $image = $this->saveImage($param, $folderId);

            $data = '';
            $nameParent = '';
            if ($image) {
                $data = $image->getFullPath();
                $parent = Asset::getById((int)$image->getParentId());
                $nameParent = $parent ? $parent->getFilename() : '';
            }

            return new JsonResponse(['files' => $data, 'nameParent' => $nameParent]);
        }

        return new JsonResponse(['error' => 'Something went wrong, please check upload_max_filesize and post_max_size in your php.ini as well as the write permissions on the file system']);
    }

    static public function saveImage($value, $parentId = null)
    {
        if ($value && $value->getPath()) {
            $path = "/_default_upload_bucket";
            if ($parentId && ($parentId != 1)) {
                $item = Asset::getById($parentId);
                if ($item) {
                    $path = $item->getFullPath() . '/';
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
     * @Route("/get-asset", name="get_asset", methods={"POST"}, options={"expose"=true}))
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
            if ($item->getType() != "folder") {
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
            }
        };

        return new JsonResponse(['data' => $images, 'nameParent' => $nameParent, 'parentId' => (int)$parentId]);
    }

    /**
     * @Route("/get-folder", name="get_folder", methods={"POST"}, options={"expose"=true}))
     */
    public static function getFolderAction(Request $request)
    {
        $conditionQuery = 'id != 1 AND parentId = :parentId';
        $conditionParams = [
            'parentId' => 1,
        ];

        $checkType = $request->get('types');
        if (!$checkType) $checkType = 'image';

        $list = new \Pimcore\Model\Asset\Listing();
        $list->setCondition($conditionQuery, $conditionParams);
        $list->setOrderKey('creationDate');
        $list->setOrder('ASC');
        $list->load();

        $folders[] = [
            'id' => 1,
            'name' => 'Home',
            'icon' => '/bundles/pimcoreadmin/img/flat-color-icons/home-gray.svg',
        ];
        $images = [];
        foreach ($list as $item) {
            if ($item->getType() == "folder") {
                $folders[] = [
                    'id' => $item->getId(),
                    'name' => $item->getFilename(),
                    'icon' => "/bundles/pimcoreadmin/img/flat-color-icons/folder.svg",
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
     * @Route("/search-folder", name="search_folder", methods={"POST"}, options={"expose"=true}))
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
     * @Route("/tree-listing-asset", name="tree_listing_asset", methods={"GET", "POST"}, options={"expose"=true}))
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

    /**
     * @Route("/tree-children-asset", name="tree_children_asset", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeChildrenDocAction(Request $request)
    {
        $id = $request->get('id');
        $config = $request->get('config');

        $datas = [
            'nodes' => [],
            'children' => [],
        ];

        $conditions = '`parentId` = ? AND type = "folder"';
        $params = [ $id ];

        if($config) {
            $conditions .= ' AND (';

            foreach($config as $key) {
                $conditions .= ' `classId` = ? OR ';
                $params[] = $key;
            }

            $conditions .= ' `classId` IS NULL)';
        }

        $listing = new Asset\Listing();
        $listing->setCondition($conditions, $params);
        $listing->setOrderKey('mimetype');
        $listing->setOrder('ASC');

        foreach ($listing as $item) {
            $data = [];
            foreach ($item->getChildren() as $children) {
                if ($children->getType() == 'folder') {
                    $data[] = (string)$children->getId();
                }
            }
            $publicURL = AssetServices::getThumbnailPath($item);
            $datas['nodes'][(string)$item->getId()] = [
                'id' => $item->getId(),
                'key' => $item->getFileName(),
                'type' => $item->getType(),
                'children' => $data,
                'icon' => SearchHelper::getIcon($item->getType()),
                'publish' => true,
                'image' => $publicURL,
                'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
            ];

            $datas['children'][] = (string)$item->getId();
        }

        return new JsonResponse($datas);
    }
}
