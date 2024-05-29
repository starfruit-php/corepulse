<?php

namespace CorepulseBundle\Controller\Cms;

use DateTime;
use Pimcore\File;
use Symfony\Component\Routing\Annotation\Route;
use CorepulseBundle\Controller\Cms\BaseController;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service as AssetService;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;
use Pimcore\Model\Asset\Image\Thumbnail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

use CorepulseBundle\Services\AssetServices;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 * @Route("/asset")
 */
class AssetController extends BaseController
{

    /**
     *
     * @Route("/listing", name="vuetify_asset", methods={"GET","POST"}, options={"expose"=true})
     */
    public function listing(Request $request)
    {

        if ($this->checkRole('assetsList')) {
            $permission = $this->getPermission();
            $nameParent = [];
            $list = new Asset\Listing();
            $conditionQuery = 'id is not NULL';
            $conditionParams = [];
            // load trang
            $id = $request->get('folderId') ? $request->get('folderId') : '';
            $parentId = 1;
            $nameFoldes = 'Homne';
            if ($id) {
                // lấy ra thông tin hiển thị trên breadcrumbs
                $parentInfo = Asset::getById($id);
                if ($parentInfo) {

                    $nameFoldes = $parentInfo?->getFileName();
                    $pathParent = $parentInfo?->getPath() . $parentInfo?->getFileName();
                    $nameParent = explode('/', $pathParent);
                    $result = array_filter($nameParent, function ($nameParent) {
                        return !empty($nameParent);
                    });
                    $nameParent = [];
                    $previousSubstring = '';
                    foreach ($result as $key => $val) {
                        $idChill = '';
                        $substring = '';
    
                        if (strpos($parentInfo->getPath(), $val) !== false) {
                            $substring = $previousSubstring . '/' . $val;
    
                            $asset = Asset::getByPath($substring);
                            if ($asset) {
                                $idChill = $asset->getId();
                            }
                        }
                        $nameParent[] = [
                            'id' => $idChill,
                            'name' => $val,
                            'end' =>  $key == array_key_last($result),
                        ];
                        $previousSubstring = $substring;
                    }
                    // thêm điều kiện query
                    $parentId = $id;
                    // $list->setCondition('parentId = ?', [$id]);
                }
            }
            $conditionQuery .= ' AND parentId = :parentId';
            $conditionParams['parentId'] = $parentId;
            $orderKey = "mimetype";
            $oederSet = "ASC";
            $limit = 25;
            $offset = 0;
            if ($request->getMethod() == "POST") {
                $keySort = $request->get('orderKey');
                if ($keySort) {
                    $orderKey = $keySort;
                    if ($keySort == "fomat") {
                        $orderKey = "mimetype";
                    }
                }
                $typeSort = $request->get('order');
                if ($typeSort) {
                    $oederSet = $typeSort;
                }

                $numberItem = $request->get('limit');
                if ($numberItem) {
                    $limit = (int)$numberItem;
                    $page = $request->get('page');
                    if ($page) {
                        $offset = ((int)$page - 1) * $limit;
                    }
                }

                // search
                $search = $request->get('search');
                if ($search) {
                    foreach (json_decode($search, true) as $key => $value) {
                        if ($key == "fomat") $key = "mimetype";
                        if ($key == "creationDate") {
                            if (is_array($value)) {
                                $dateFrom = strtotime($value[0]);
                                $dateTo = strtotime($value[1]);
                                $conditionQuery .= ' AND creationDate <= :dateTo AND creationDate > :dateFrom';
                                $conditionParams['dateFrom'] = $dateFrom;
                                $conditionParams['dateTo'] = $dateTo;
                            }

                            continue;
                        }
                        $conditionName = $key . " LIKE '%" . $value . "%'";
                        $conditionQuery .= ' AND ' . $conditionName;
                    }
                }

                // phân trang

            }
            $list->setOrderKey($orderKey);
            $list->setOrder($oederSet);
            $list->setCondition($conditionQuery, $conditionParams);
            $list->setOffset($offset);
            $list->setLimit($limit);
            $list->load();
            $totalItems = $list->count();

            // lấy danh sách folder

            $listAsset = [];
            $listFoldes = [];
            foreach ($list as $item) {

                $publicURL = AssetServices::getThumbnailPath($item);

                if ($item->getParentId() == 1 || $id) {
                    $fomat = '';
                    if ($item->getMimeType()) {
                        $fomat = explode('/', $item->getMimeType());
                        $fomat =  $fomat[1];
                    }

                    $filenName = "<div class='div-images'> <div class='tableCell--titleThumbnail preview--image d-flex align-center'>"
                        . "<img class='me-2 image-default' src=' " .  $publicURL . "'><span>" .
                        $item->getFileName() .
                        "</span></div>" . "<div class='image-preview'> <img src='" . $publicURL . "' alt='Preview Image'></div> </div>";


                    $listAsset[] = [
                        "noAction" => ($item->getType() == "folder") ? ['download'] : ['edit'],
                        'id' => $item->getId(),
                        'file' => $item->getFileName(),
                        'fileName' => ($item->getType() == "image") ? $filenName : "<div class='tableCell--titleThumbnail d-flex align-center'><img class='me-2' src=' " .  $publicURL . "'><span>" .  $item->getFileName() . "</span></div>",
                        'creationDate' => ($item->getType() != "folder") ? self::getTimeAgo($item->getCreationDate()) : '',
                        'size' => ($item->getType() != "folder") ? round((int)$item->getFileSize() / (1024 * 1024), 2) . "MB" : '',
                        'parenId' => $item->getParent()?->getId(),
                        'type' => ($item->getType() == "folder") ? 'folder' : $item->getType(),
                        'fomat' => $fomat,
                        'publicURL' => $publicURL,
                        'urlDownload' => $item->getPath() . $item->getFileName(),
                        'showPreview' =>  false,
                        'previewURL' => $publicURL
                    ];
                }
            }

            $noOrder = ['size'];

            $viewData = ['metaTitle' => 'Asset'];
            return $this->renderWithInertia('Pages/Asset/Asset', [
                'listAsset' => $listAsset,
                'nameParent' => $nameParent,
                'totalItems' => $totalItems,
                'permission' => $permission,
                'chips' => [],
                'nameFoldes' => $nameFoldes,
                'parentId' => $parentId,
                'noOrder' => $noOrder,
                'defaultAdmin' => $this->getUser()->getDefaultAdmin(),
            ]);
        } else {
            return $this->renderWithInertia('Pages/AccessDiend');
        }
    }

    /**
     *
     * @Route("/detail/{id}", name="vuetify_asset_detail", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function detail($id, Request $request)
    {
        if ($this->checkRole('assetsView')) {

            $permission = $this->getPermission();
            // dd($permission);
            $asset = Asset::getById($id);
            $languages = \Pimcore\Tool::getValidLanguages();

            if ($asset->getType() != 'folder') {
                $detailAsset = [];
                $nameParent = [];
                if ($asset) {
                    $asset = Asset::getById($id);

                    $domain = $_SERVER['HTTP_HOST'];
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $domain = $protocol . $_SERVER['HTTP_HOST'];

                    $fomat = '';
                    if ($asset->getMimeType()) {
                        $fomat = explode('/', $asset->getMimeType());
                        $fomat =  $fomat[1];
                    }

                    $width = 0;
                    $height = 0;
                    if ($asset->getType() == 'image') {
                        $width = $asset->getWidth();
                        $height = $asset->getHeight();
                    }

                    $language = $languages[0];
                    if ($request->get('language')) {
                        $language = $request->get('language');
                    }
                    $alt = '';
                    $caption = '';
                    $description = '';
                    $videoMov = '';
                    $videoWebm = '';

                    $metaData = $asset->getMetaData();
                    foreach ($metaData as $item) {
                        if (($item['name'] == 'alt') && ($item['language'] == $language)) {
                            $alt = $item['data'];
                        }
                        if (($item['name'] == 'caption') && ($item['language'] == $language)) {
                            $caption = $item['data'];
                        }
                        if (($item['name'] == 'description') && ($item['language'] == $language)) {
                            $description = $item['data'];
                        }
                        if (($item['name'] == 'mov') && ($item['language'] == $language)) {
                            $videoMov = $item['data']->getPath() . $item['data']->getFileName();
                        }
                        if (($item['name'] == 'webm') && ($item['language'] == $language)) {
                            $videoWebm = $item['data']->getPath() . $item['data']->getFileName();
                        }
                    }

                    $detailAsset = [
                        'id' => $asset->getId(),
                        'fileName' => $asset->getFileName(),
                        'size' => round((int)$asset->getFileSize() / (1024 * 1024), 3) . " MB",
                        'fomat' => $fomat,
                        'type' => $asset->getType(),
                        'width' => $width,
                        'dimensions' => $width . " x " . $height,
                        'uploadOn' => date("M j, Y  H:i", $asset->getModificationDate()),
                        'publicURL' => $domain . $asset->getPath() . $asset->getFileName(),
                        'path' =>  $asset->getPath() . $asset->getFileName(),
                        'data' => ($asset->getType() == 'text') ? $asset->getData() : '',
                        'alt' => $alt,
                        'caption' => $caption,
                        'description' => $description,
                        'videoMov' => $videoMov,
                        'videoWebm' => $videoWebm,
                        'languages' => $languages,
                        'parentId' => $asset->getParentId(),
                        'publish' => round((int)$asset->getFileSize() / (1024 * 1024), 3) == 0,
                        'mimetype' => $asset->getMimetype(),
                    ];

                    // lấy đường dẫn đến file

                    if ($asset->getParentId() != 1) {
                        $pathParent = $asset->getPath() . $asset->getFileName();
                        $nameParent = explode('/', $pathParent);
                        $result = array_filter($nameParent, function ($nameParent) {
                            return !empty($nameParent);
                        });
                        $nameParent = [];

                        foreach ($result as $val) {
                            $idChill = '';
                            if (strpos($asset->getPath(), $val) !== false) {
                                $substring = substr($asset->getPath(), 0, strpos($asset->getPath(), $val)) . $val;

                                $assetChill = Asset::getByPath($substring);
                                if ($assetChill) {
                                    $idChill = $assetChill->getId();
                                }
                            }
                            if (!($val == end($result))) {
                                $nameParent[] = [
                                    'id' => $idChill,
                                    'name' => $val,
                                    'end' =>  $val == end($result)
                                ];
                            }
                        }
                    }
                }

                $viewData = ['metaTitle' => 'Corepulse cms - ' . ($detailAsset['fileName'] ? $detailAsset['fileName'] : '')];
                return $this->renderWithInertia('Pages/Asset/AssetDetail', [
                    'detailAsset' => $detailAsset,
                    'nameParent' => $nameParent,
                ]);
            } else {
                $layout = $request->get("layout");

                if ($layout) {
                    $params = [
                        'folderId' => $asset->getId(),
                        'layout' => $layout,
                    ];
                } else {
                    $params = [
                        'folderId' => $asset->getId(),
                    ];
                }

                return $this->redirectToRoute('vuetify_asset', $params);
            }
        } else {
            return $this->renderWithInertia('Pages/AccessDiend');
        }
    }

    /**
     *
     * @Route("/upload-folder", name="vuetify_asset_upload_folder", methods={"GET","POST"}, options={"expose"=true})
     */
    public function uploadFolder(Request $request)
    {
        // tạo 1 folder mới
        // if ($this->checkRole('assetsCreate')) {
        $nameFolder = $request->get('nameFolder');
        $params = [];
        if ($nameFolder) {
            $parentId = $request->get('parentId');
            if ($parentId) {
                $folders = Asset::getById($parentId);
                if ($folders) {
                    $path = $folders->getPath() . $folders->getFileName();
                    AssetService::createFolderByPath($path . "/" . $nameFolder);
                    $params = [
                        'folderId' => $parentId,
                    ];
                } else {
                    AssetService::createFolderByPath("/" . $nameFolder);
                }
            } else {
                AssetService::createFolderByPath("/" . $nameFolder);
            }
            $this->addFlash("success", 'Upload Folder success');
        } else {
            $this->addFlash("error", 'Upload failure');
        }
        return $this->redirectToRoute('vuetify_asset', $params);
        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }

    /**
     *
     * @Route("/upload-file", name="vuetify_asset_upload_file", methods={"GET","POST"}, options={"expose"=true})
     */
    public function uploadFile(Request $request)
    {
        // if ($this->checkRole('assetsCreate')) {
        $files = $request->files->get("test");
        $infoFile = $request->get('test');

        if ($files && $infoFile) {
            $infoFile = json_decode($infoFile);
            $folderId = $infoFile->parentId ?  $infoFile->parentId : 1;

            $folder = Asset::getById(1);
            $checkFolder = Asset::getById((int)$folderId);
            if ($checkFolder) {
                $folder = $checkFolder;
            } 

            $path = property_exists($infoFile, 'path') ? $infoFile->path : '';
            if ($path) {
                $parentPath = $folder->getFullPath();
                $basePath = dirname($path);
                $fullPathNew = $parentPath . $basePath;

                $folder = Asset::getByPath($fullPathNew) ?? Asset\Service::createFolderByPath($fullPathNew);
            }

            $file = AssetServices::createFile($files, $folder);
            if ($file) {
                $this->addFlash("success", 'Upload success');
            } else {
                $this->addFlash("error", 'Upload false');
            }

            return new JsonResponse(['files' => $file]);
        }
        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }

    /**
     *
     * @Route("/delete", name="vuetify_asset_delete", methods={"GET","POST"}, options={"expose"=true})
     */
    public function delete(Request $request)
    {
        // delete từng asset
        // if ($this->checkRole('assetsDelete')) {

        $itemId = $request->get('id');
        if (is_array($itemId)) {
            try {
                foreach ($itemId as $item) {
                    $asset_detail = Asset::getById((int)$item);
                    if ($asset_detail) {
                        $asset_detail->delete();
                        $this->addFlash("success", "Delete " . $asset_detail->getFileName() . " success");
                    } else {
                        $this->addFlash("error", "Can not find photos or folders to be deleted");
                    }
                }
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }
            return new JsonResponse();
        } else {
            if ($itemId) {
                $asset_detail = Asset::getById((int)$itemId);
                if ($asset_detail) {
                    $asset_detail->delete();
                    $this->addFlash("success", "Delete " . $asset_detail->getFileName() . " success");
                } else {
                    $this->addFlash("error", "Can not find photos or folders to be deleted");
                }
            }
            $params = [];
            $folderId = $request->get("folderId");
            $folderId = $folderId != 'undefined' ?  $folderId : '';
            if ($folderId && ($folderId != 1)) {
                $params = [
                    'folderId' => $folderId,
                ];
            }
            // dd($params);
            return $this->redirectToRoute('vuetify_asset');
        }

        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }


    /**
     *
     * @Route("/replace-image", name="vuetify_asset_replace", methods={"GET","POST"}, options={"expose"=true})
     */
    public function replaceImage(Request $request)
    {
        $id = $request->get('id');

        $file = $request->files->get("test");
        $infoFile = $request->get('test');
        if ($file && $infoFile) {
            $infoFile = json_decode($infoFile);
            $id = property_exists($infoFile, 'id') ? $infoFile->id : '';

            $asset = Asset::getById($id);
            $asset->setData(file_get_contents($file));
            $asset->save();

            return new JsonResponse($asset);
        }
        return new JsonResponse();
    }

    /**
     *
     * @Route("/add-attribute", name="vuetify_asset_add_attribute", methods={"GET","POST"}, options={"expose"=true})
     */
    public function addAttribute(Request $request)
    {
        $itemId = $request->get('id');
        $alt = $request->get('alt');
        $caption = $request->get('caption');
        $description = $request->get('description');
        $language = $request->get('language');
        $fileName = $request->get('fileName');

        $videoMov = $request->get('videoMov');
        $videoWebm = $request->get('videoWebm');

        $asset_detail = Asset::getById((int)$itemId);

        if ($alt) {
            $asset_detail->addMetadata("alt", "input", $alt, $language);
        }
        if ($caption) {
            $asset_detail->addMetadata("caption", "input", $caption, $language);
        }
        if ($description) {
            $asset_detail->addMetadata("description", "textarea", $description, $language);
        }
        if ($fileName && $fileName !=  $asset_detail->getFileName()) {
            $asset_detail->setFileName($fileName);
        }

        $mov = Asset::getByPath($videoMov);
        $asset_detail->addMetadata("mov", "asset", $mov, $language);

        $webm = Asset::getByPath($videoWebm);
        $asset_detail->addMetadata("webm", "asset", $webm, $language);


        $asset_detail->save();

        $this->addFlash("success", 'Update successfully');

        $data = [
            'id' => $itemId
        ];

        if ($language != 'null') {
            $data['language'] = $language;
        }

        return $this->redirectToRoute("vuetify_asset_detail",  $data);
    }

    /**
     *
     * @Route("/add-video", name="vuetify_asset_add_video", methods={"GET","POST"}, options={"expose"=true})
     */
    public function addVideo(Request $request)
    {
        $itemId = $request->get('id');
        $file = $request->files->get('file');

        $filename = $file->getClientOriginalName();
        $path = $file->getPathname();

        $newAsset = new \Pimcore\Model\Asset();
        $newAsset->setFilename(time() . '-' . $filename);
        $newAsset->setData(file_get_contents($path));
        $newAsset->setParent(\Pimcore\Model\Asset::getByPath("/"));
        $newAsset->save();

        $data = [
            'id' => $itemId
        ];

        return $this->redirectToRoute("vuetify_asset_detail",  $data);
    }

    /**
     *
     * @Route("/get-data-lang", name="vuetify_asset_get_data_lang", methods={"GET","POST"}, options={"expose"=true})
     */
    public function getDataLang(Request $request)
    {
        $itemId = $request->get('id');
        $language = $request->get('language');
        return $this->redirectToRoute("vuetify_asset_detail", ['id' => $itemId, 'language' => $language]);
    }

    /**
     *
     * @Route("/move", name="vuetify_asset_move", methods={"GET","POST"}, options={"expose"=true})
     */
    public function moveAction(Request $request)
    {
        $itemId = $request->get('id');
        $parentId = $request->get('parentId');
        $asset_detail = Asset::getById((int)$itemId);
        $parentInfo = Asset::getById((int)$parentId);
        $path =  $parentInfo->getPath() . $parentInfo->getFileName();

        $asset_detail->setParent(\Pimcore\Model\Asset::getByPath($path));
        $asset_detail->save();

        $params = [];
        $folderId = $request->get("folderId");
        $folderId = $folderId != 'undefined' ?  $folderId : '';
        if ($folderId && ($folderId != 1)) {
            $params = [
                'folderId' => $folderId,
            ];
        }
        return $this->redirectToRoute('vuetify_asset', $params);
    }

    /**
     *
     * @Route("/extract-file", name="vuetify_asset_extract_file", methods={"GET","POST"}, options={"expose"=true})
     */
    public function extractFile(Request $request)
    {
        $id = $request->get('id');
        $asset_detail = Asset::getById((int)$id);

        $parth = '';
        $params = [];
        $folderId = $request->get("folderId");
        if ($folderId) {
            $pathParent = Asset::getById((int)$folderId);
            $parth =  $pathParent->getPath() . $pathParent->getFileName();
            $params = [
                'folderId' => $folderId,
            ];
        }
        $layout = $request->get("layout");
        if ($layout) {
            $params = [
                'layout' => $layout,
            ];
        }
        if ($layout && $folderId) {
            $params = [
                'folderId' => $folderId,
                'layout' => $layout,
            ];
        }
        if ($asset_detail && ($asset_detail->getType() == "archive")) {
            $zipData = $asset_detail->getData();

            $zip = new ZipArchive();
            // Tạo một tệp tin tạm thời để lưu nội dung zip
            $tempZipFile = tempnam(sys_get_temp_dir(), 'pimcore_extracted');
            file_put_contents($tempZipFile, $zipData);
            // Mở tệp tin zip tạm thời
            if ($zip->open($tempZipFile) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {

                    $filename = $zip->getNameIndex($i);

                    $arr = explode('/', $filename);
                    $filename = array_pop($arr);
                    $pathZip = '';
                    foreach ($arr as $val) {
                        $pathZip .= "/" . $val;
                    }

                    $fileData = $zip->getFromName($filename);

                    $nameFolder = strstr($asset_detail->getFileName(), ".", true);
                    $path = $parth . '/' . $nameFolder . $pathZip;

                    $newAsset = new \Pimcore\Model\Asset();
                    $filename = time() . $filename;
                    $filename = preg_replace('/[^a-zA-Z0-9.]/', '-', $filename);
                    $filename = preg_replace('/-+/', '-', $filename);
                    $filename = trim($filename, '-');
                    $newAsset->setFilename(time() . '-' . $filename);
                    $newAsset->setData($fileData);
                    AssetService::createFolderByPath($path);
                    $newAsset->setParent(\Pimcore\Model\Asset::getByPath($path));

                    $newAsset->save();

                    $this->addFlash("success", "Extract file " . $asset_detail->getFileName() . " success");
                }
            }
        }

        return $this->redirectToRoute('vuetify_asset', $params);
    }

    /**
     *
     * @Route("/edit-name", name="vuetify_asset_edit_name", methods={"GET","POST"}, options={"expose"=true})
     */
    public function editName(Request $request)
    {
        $data = $request->get("data");
        $folderId = $request->get("parentId");
        $folderId = $folderId != 'undefined' ?  $folderId : '';

        try {
            if ($data) {
                $data = json_decode($data);
                $asset = Asset::getById((int)$data->id);
                if ($asset) {
                    $asset->setFilename($data->file);
                    $asset->save();
                    $this->addFlash('success', 'Edit successfully');
                } else {
                    $this->addFlash('error', 'Update failed');
                }
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }
        return new JsonResponse();
    }

    public function getTimeAgo($timestamp)
    {
        // Create DateTime objects for the current time and the given timestamp
        $currentDateTime = new DateTime();
        $timestampDateTime = new DateTime("@$timestamp");

        // Calculate the difference between the current time and the given timestamp
        $interval = $currentDateTime->diff($timestampDateTime);

        // Format the result based on the difference
        if ($interval->y > 0) {
            return $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ago";
        } elseif ($interval->m > 0) {
            return $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ago";
        } elseif ($interval->d > 0) {
            return $interval->d . " day" . ($interval->d > 1 ? "s" : "") . " ago";
        } elseif ($interval->h > 0) {
            return $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
        } elseif ($interval->i > 0) {
            return $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
        } else {
            return "just now";
        }
    }


    // kiểm tra hình ảnh
    public static function valideImage($item)
    {
        $allowed_extensions = array('png', 'jpeg', 'jpg', 'avf');
        $nameImage = explode(".", $item->getClientOriginalName());
        $sizeImage = $item->getSize();

        if (in_array($nameImage[1], $allowed_extensions)) {
            return true;
        }
        return false;
    }

    // lưu hình ảnh
    public static function createImage($item, $path)
    {
        $newAsset = new \Pimcore\Model\Asset();
        $filename = time() . '-' . $item->getClientOriginalName();

        // convent filename
        $filename = preg_replace('/[^a-zA-Z0-9.]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');
        $newAsset->setFilename(time() . '-' . $filename);
        $newAsset->setData(file_get_contents($item));
        AssetService::createFolderByPath($path);
        $newAsset->setParent(\Pimcore\Model\Asset::getByPath($path));
        $newAsset->save();
        $image = Asset\Image::getById($newAsset->getId());

        if ($image) {
            return true;
        } else {
            return false;
        }

        return $image;
    }


    protected static function addThumbnailCacheHeaders(Response $response)
    {
        $lifetime = 300;
        $date = new \DateTime('now');
        $date->add(new \DateInterval('PT' . $lifetime . 'S'));

        $response->setMaxAge($lifetime);
        $response->setPublic();
        $response->setExpires($date);
        $response->headers->set('Pragma', '');
    }
}
