<?php

namespace CorepulseBundle\Controller\Cms;

use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Document\Editable\Relation;
use Pimcore\Model\Document\Editable\Relations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Carbon\Carbon;
use CorepulseBundle\Services\AssetServices;
use CorepulseBundle\Services\DocumentServices;
use CorepulseBundle\Services\FieldServices;
use Pimcore\Document as PimcoreDocument;
use Pimcore\Model\Asset\Document as AssetDocument;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Document\Editable\Image as EditableImage;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Editable\Loader\EditableLoader;
use Pimcore\Model\Document\Editable\Wysiwyg;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\Element;
use Pimcore\Model\DataObject\Concrete;

/**
 * @Route("/document")
 */
class DocumentController extends BaseController
{
    /**
     * @Route("/listing", name="vuetify_doc_listing", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function listingAction(
        Request $request,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ) {
        // if ($this->checkRole('homeDocumentList')) {
            $permission = $this->getPermission();
            date_default_timezone_set('Asia/Bangkok');
            $orderKey = "index";
            $orderSet = "ASC";
            $limit = 25;
            $offset = 0;
            $nameParent = [];

            $document = new \Pimcore\Model\Document\Listing();
            $document->setUnpublished(true);
            $conditionQuery = 'id != 1';
            $conditionParams = [];

            $id = $request->get('folderId') ? $request->get('folderId') : '';
            $parentId = 1;
            if ($id) {
                $parentInfo = Document::getById($id);
                $pathParent = $parentInfo->getPath() . $parentInfo->getKey();
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
                        
                        $doc = Document::getByPath($substring);
                        if ($doc) {
                            $idChill = $doc->getId();
                        }
                    }
                    $nameParent[] = [
                        'id' => $idChill,
                        'name' => $val,
                        'end' =>  $key == array_key_last($result),
                    ];
                    $previousSubstring = $substring;
                }

                $parentId = $id;
            }
            $conditionQuery .= ' AND parentId = :parentId';
            $conditionParams['parentId'] = $parentId;

            if ($request->getMethod() == "POST") {
                $keySort = $request->get('orderKey');
                if ($keySort) {
                    if ($keySort == "name") $keySort = "key";
                    if ($keySort == "status") $keySort = "published";
                    $orderKey = $keySort;
                }
                $typeSort = $request->get('order');
                if ($typeSort) {
                    $orderSet = $typeSort;
                }

                $numberItem = $request->get('limit');
                if ($numberItem) {
                    $limit = (int)$numberItem;
                    $page = $request->get('page');
                    if ($page) {
                        $offset = ((int)$page - 1) * $limit;
                    }
                }

                $search = $request->get('search');
                if ($search) {
                    foreach (json_decode($search, true) as $key => $value) {
                        if ($key == "name") $key = "key";
                        if ($key == "status") {
                            if ($value && $value != "-1") {
                                $conditionQuery .= ' AND published = :published';
                                $conditionParams['published'] = $value;
                            }
                            continue;
                        }
                        if (is_array($value)) {
                            if (array_key_exists('type', $value)) {
                                if ($value['type'] == "picker") {
                                    $startDate = strtotime(date('Y-m-d', $value['date']) . '00:00:00');
                                    $endDate = strtotime(date('Y-m-d', $value['date']) . '23:59:59');

                                    $conditionQuery .= ' AND ' . $value['key'] . ' <= :dateTo AND ' . $value['key'] . ' > :dateFrom';
                                    $conditionParams['dateFrom'] = $startDate;
                                    $conditionParams['dateTo'] = $endDate;

                                    continue;
                                }
                            }

                            continue;
                        }
                        $conditionName = "`" . $key . "`" . " LIKE '%" . $value . "%'";
                        $conditionQuery .= ' AND ' . $conditionName;
                    }
                }
            }

            $document->setCondition($conditionQuery, $conditionParams);
            $document->setOrderKey($orderKey);
            $document->setOrder($orderSet);
            $document->setOffset($offset);
            $document->setLimit($limit);
            $document->load();
            $totalItems = $document->count();

            $data = [];
            foreach ($document as $doc) {
                $publicURL = DocumentServices::getThumbnailPath($doc);

                $draft = $this->checkLastest($doc);
                if ($draft) {
                    $status = 'Draft';
                } else {
                    if ($doc->getPublished()) {
                        $status = 'Publish';
                    } else {
                        $status = 'Draft';
                    }
                }

                if ($doc->getId() != 1 || $id) {
                    $listChills = new \Pimcore\Model\Document\Listing();
                    $listChills->setCondition("parentId = :parentId", ['parentId' => $doc->getId()]);
                    $chills = [];
                    foreach ($listChills as $item) {
                        $chills[] = $item;
                    }

                    $data[] = [
                        'id' => $doc->getId(),
                        'name' => "<div class='tableCell--titleThumbnail d-flex align-center'><img class='me-2' src=' " .  $publicURL ."'><span>" . $doc->getKey() . "</span></div>",
                        'type' => '<div class="chip">' . $doc->getType() . '</div>',
                        'status' => $status,
                        'createDate' => DocumentServices::getTimeAgo($doc->getCreationDate()),
                        'modificationDate' => DocumentServices::getTimeAgo($doc->getModificationDate()),
                        'parent' => $chills ? true : false,
                        'noMultiEdit' => [
                            'name' => $chills ? [] : ['name'],
                        ],
                        "noAction" =>  $chills ? [] : ['seeMore'],
                    ];
                }
            }

            if ($parentId == 1) {
                $home = Document::getById($parentId);
                $infoHome = [];
                if ($home) {
                    $publicURL = DocumentServices::getThumbnailPath($home);

                    $draft = $this->checkLastest($home);
                    if ($draft) {
                        $status = 'Draft';
                    } else {
                        if ($home->getPublished()) {
                            $status = 'Publish';
                        } else {
                            $status = 'Draft';
                        }
                    }

                    $infoHome[] = [
                        'id' => $home->getId(),
                        'name' => "<div class='tableCell--titleThumbnail d-flex align-center'><img class='me-2' src=' " .  $publicURL ."'><span>" . " Home </span></div>",
                        'type' => '<div class="chip">' . $home->getType() . '</div>',
                        'status' => $status,
                        'createDate' => DocumentServices::getTimeAgo($home->getCreationDate()),
                        'modificationDate' => DocumentServices::getTimeAgo($home->getModificationDate()),
                        'parent' => false,
                        'noMultiEdit' => [
                            'name' =>  ['name'] ,
                        ],
                        "noAction" => ['seeMore'],
                    ];
                }
                // array_push($data, $infoHome);
                array_unshift($data, ...$infoHome);
            }
            $chipsColor = [
                'status' => [
                    'Publish' => 'primary',
                    'Draft' => 'red',
                ],
            ];

            $listing = [];
            $listing = $data;
            $viewData = ['metaTitle' => 'Document'];
            return $this->renderWithInertia('Pages/Document/Listing', [
                'listing' => $listing,
                'totalItems' => $totalItems,
                // 'permission' => $permission,
                'nameParent' => $nameParent,
                'chips' => ['status'],
                'chipsColor' => $chipsColor,
            ]);
        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }

    /**
     * @Route("/add", name="vuetify_doc_add", methods={"POST"}, options={"expose"=true}))
     */
    public function addAction(Request $request)
    {
        // if ($this->checkRole('homeDocumentCreate')) {
            $title = $request->get('title');
            $folderId = $request->get('folderId');
            $parentId = ($folderId != 'null') ? (int)$folderId : 1;

            if ($title) {
                $checkPage = Document::getByPath("/" . $title);
                if (!$checkPage) {
                    
                    $key = $request->get('key');
                    $type = $request->get('type');

                    $page = DocumentServices::createDoc($key, $title, $type, $parentId);
                    if ($page)
                        $this->addFlash("success", "Create document ". $page ->getKey() ." successfully");
                    else
                        $this->addFlash('error', 'Create failed');
                } else {
                    $this->addFlash('error', 'Create failed');
                }
            }

            $params = [];
            if ($parentId && ($parentId != 1)) {
                $params = [
                    'folderId' => $parentId,
                ];
            }
            
            $page = $request->get('page');
            if ($page && ($page == "catalog")) {
                return $this->redirectToRoute('cms_document_catalog');
            }
            return $this->redirectToRoute('vuetify_doc_listing', $params);

        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }

    function getVersionDoc($document)
    {
        $versions = $document->getVersions();
        $reversedVersionsArray = array_reverse($versions);

        $previousVersion = [];
        foreach ($reversedVersionsArray as $version) {
            $previousVersion[] = [
                'id' => $version->getId(),
                'date' => DocumentServices::getTimeAgo($version->getDate()),
                'user' => $version->getUser()->getName(),
                'published' => $version->getPublic()
            ];
        }

        return $previousVersion;
    }

    function listingDoc()
    {
        $documentListing = new \Pimcore\Model\Document\Listing();
        $documentListing->setCondition("id != 1");
        $dataDocListing = [];
        foreach ($documentListing as $doc) {
            if ($doc->getType() != 'folder') {
                // dd($doc->getType());
                $dataDocListing[] = [
                    'id' => $doc->getId(),
                    'name' => $doc->getKey(),
                    'subtype' => $doc->getType(),
                    'type' => 'document'
                ];
            }
        }
        return $dataDocListing;
    }

    function listingAsset()
    {
        $assetListing = new \Pimcore\Model\Asset\Listing();
        $assetListing->setCondition("id != 1");
        $dataAssetListing = [];
        foreach ($assetListing as $asset) {
            if ($asset->getType() != 'folder') {
                // dd($asset);
                if ($asset->getType() == 'image') {
                    $linkImg = $asset->getPath() . $asset->getKey();
                } elseif ($asset->getType() == 'folder') {
                    $linkImg = '/bundles/pimcoreadmin/img/flat-color-icons/folder.svg';
                } else {
                    $linkImg = '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
                }
                $dataAssetListing[] = [
                    'id' => $asset->getId(),
                    'name' => $asset->getKey(),
                    'subtype' => $asset->getType(),
                    'type' => 'asset',
                    'linkImg' => $linkImg,
                ];
            }
        }

        return $dataAssetListing;
    }

    function listingObject()
    {
        $objectListing = new \Pimcore\Model\DataObject\Listing();
        $objectListing->setCondition("id != 1");
        $dataObjectListing = [];
        foreach ($objectListing as $object) {
            if ($object->getType() != 'folder') {
                $dataObjectListing[] = [
                    'id' => $object->getId(),
                    'name' => $object->getKey(),
                    'subtype' => $object->getType(),
                    'type' => 'dataobject',
                    'class' => ($object->getType() != 'folder') ? $object?->getClass()->getName() : '',
                ];
            }
        }
        return $dataObjectListing;
    }

    /**
     * @Route("/get-item-chill", name="vuetify_doc_get_item_chill", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function getItemChill(Request $request, $abc = null)
    {
        $id = $request->get('id');
        $document = Document::getById((int)$id);
        if ($document) {
            $listChills = new \Pimcore\Model\Document\Listing();
            $listChills->setCondition("parentId = :parentId", ['parentId' => $id]);
            $listChills->current();
            $data = [];
            foreach ($listChills as $item) {
                $data[] = $item;
            }
            if ($data) {
                $params = [
                    'folderId' => $document->getId(),
                ];
                return $this->redirectToRoute('vuetify_doc_listing', $params);
            } else {
                $params = [
                    'id' => $id
                ];
                return $this->redirectToRoute('vuetify_doc_detail', $params );
            }
        }
        return $this->redirectToRoute('vuetify_doc_listing');
    }

    /**
     * @Route("/detail/{id}", name="vuetify_doc_detail", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function detailAction(Request $request, $abc = null)
    {
        // if ($this->checkRole('homeDocumentView')) {
            date_default_timezone_set('Asia/Bangkok');
            $id = $request->get('id');
            $document = Document::getById($id);
            // dd($document);
            if ($document->getType() != 'folder') {
             
                $previousVersion = self::getVersionDoc($document);

                $listingDoc = self::listingDoc();
                $listingAsset = self::listingAsset();
                $listingObject = self::listingObject();
                $editable = [];
                $listRelation = [];
                $listRelations = [];
                $listBlock = [];
                $snippetChoosed = [];
                $listScheduled = [];
                $pdfInfo = '';
                $href = '';
                $dataVideos['default'] = [
                    'id' => '',
                    'type' => 'asset',
                    'title' => '',
                    'description' => '',
                    'path' => '/bundles/pimcoreadmin/img/filetype-not-supported.svg',
                    'poster' => '',
                ];
                $blocksItem = [];
                $allTypeSchedule = [];
                $schedulesItem = [];
                $listAreaBlock = [];
                $areaBlocksItem = [];

                if ($document->getType() == 'link') {
                    $href = $document->getHref();
                } else {
                    $imagesList = []; 
                    // dd($document->getEditables());
                    foreach ($document->getEditables() as $keyEditable => $valueEditable) {
                        if ($document->getEditable($keyEditable)->getType() == 'relation') {
                            $valueRelation = $document->getEditable($keyEditable)->getData();
                            $elementRelation = $document->getEditable($keyEditable)->getElement();
                            $name = $document->getEditable($keyEditable)->getName();

                            $listRelation[$name] = [
                                0 => ucfirst($valueRelation['type']),
                                1 => $valueRelation['subtype'],
                                2 => $valueRelation['id'],
                            ];
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'relations') {
                            $valueRelations = $document->getEditable($keyEditable)->getData();
                            $nameReltions = $document->getEditable($keyEditable)->getName();
                            foreach ($valueRelations as $item) {
                                if ($item->getType() == "object") {
                                    $listRelations[$nameReltions][] = [
                                        0 => 'Object',
                                        1 => $item->getType(),
                                        2 => $item->getId(),
                                    ];
                                } elseif  (
                                    $item->getType() == "image" || 
                                    $item->getType() == "video" || 
                                    $item->getType() == "document" ||
                                    $item->getType() == "docx" ||
                                    $item->getType() == "xlsx" ||
                                    $item->getType() == "text" 
                                ) {
                                    $linkImg = AssetServices::getThumbnailPath($item);
                                    $listRelations[$nameReltions][] = [
                                        0 => 'Asset',
                                        1 => $item->getType(),
                                        2 => $item->getId(),
                                    ];
                                } else {
                                    $linkImg = DocumentServices::getThumbnailPath($item);
                                    $listRelations[$nameReltions][] = [
                                        0 => 'Document',
                                        1 => $item->getType(),
                                        2 => $item->getId(),
                                    ];
                                }
                            }
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'block') {

                            $arrTypeChillBlock = DocumentServices::getEditableBlock($document, $keyEditable);
                            $valueRelations = $document->getEditable($keyEditable)->getData();
                            $nameBlock = $document->getEditable($keyEditable)->getName();
                            $notType = ['snippet', "renderlet", "block", "video", "pdf", "date"];

                            $listBlock = DocumentServices::getDataBlock($document, $arrTypeChillBlock, $nameBlock, $valueRelations);

                            $adc = [];
                            $arrName = explode(':', $nameBlock);
                            $nameCheck = is_array($arrName) ? $arrName[0] : $arrName;
                            foreach ($valueRelations as $item) {
                                $keyCheck = $nameCheck . ":" . $item;
                                $adc[] = $keyCheck;
                            }
                            $blocksItem[$nameCheck] = [];

                            foreach ($adc as $val) {
                                foreach ($listBlock as $key => $item) {
                                    if (strpos($key, $val) !== false) {
                                        $blocksItem[$nameBlock][$val][$key] = $item;
                                    }
                                }
                            }
                            // dd($blocksItem, $listBlock, $arrTypeChillBlock, $nameBlock);
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'snippet') {
                            $valueRelations = $document->getEditable($keyEditable)->getData();

                            $snippet = $document->getEditable($keyEditable)->getSnippet();

                            if ($snippet) {
                                $snippeted = Document::getById((int)$snippet->getId());
                                $fullPath = DocumentServices::getThumbnailPath($snippeted);

                                $snippetChoosed = [
                                    0 => 'Document',
                                    1 => $snippet->getType(),
                                    2 => $snippet->getId(),
                                ];
                            }
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'scheduledblock') {
                            $arrTypeChillBlock = DocumentServices::getEditableBlock($document, $keyEditable);
                            $nameBlock = $document->getEditable($keyEditable)->getName();

                            $allTypeSchedule['name'] = $nameBlock;
                            foreach ($arrTypeChillBlock as $key => $val) {
                                $nameField = explode('.', $key);
                                $nameSet = '';
                                if (is_array($nameField) && isset($nameField[1])) {
                                    $nameSet = $nameField[1];
                                }
                                $allTypeSchedule[$val] = ["value" => $nameSet];
                            }


                            $valueScheduled = $document->getEditable($keyEditable)->getData();
                            $notType = ['snippet', "renderlet", "block", "scheduledblock"];

                            $listScheduled = DocumentServices::getDataBlock($document, $arrTypeChillBlock, $nameBlock, $valueScheduled);

                            $arrName = explode(':', $nameBlock);
                            $nameCheck = is_array($arrName) ? $arrName[0] : $arrName;
                            foreach ($valueScheduled as $item) {
                                $keyCheck = $nameCheck . ":" . $item['key'];
                                $abc[] = [
                                    'keyCheck' => $keyCheck,
                                    'time' => $item['date'] ? date('Y-m-d H:i:s', $item['date']) : '',
                                ];
                            }
                            $schedulesItem = [];
                            foreach ($abc as $val) {
                                foreach ($listScheduled as $key => $item) {
                                    if (strpos($key, $val['keyCheck']) !== false) {
                                        $schedulesItem[$val['time']][$key] = $item;
                                    }
                                }
                            }
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'pdf') {
                            $valuePdf = $document->getEditable($keyEditable)->getData();
                            $id = $valuePdf ? $valuePdf['id'] : 0;
                            $asset = Asset::getById((int)$id);
                            $pdfInfo = $asset ? $asset->getFullPath() : '';
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'video') {
                            $valueVideo = $document->getEditable($keyEditable)->getData();
                            $nameVideo = $document->getEditable($keyEditable)->getName();
                            $dataVideos[$nameVideo] = $valueVideo;
                        }
                        if ($document->getEditable($keyEditable)->getType() == 'areablock') {
                            $arrTypeChillBlock = DocumentServices::getEditableBlock($document, $keyEditable);
                            $valueRelations = $document->getEditable($keyEditable)->getData();
                            $nameBlock = $document->getEditable($keyEditable)->getName();

                            $listAreaBlock = DocumentServices::getDataBlock($document, $arrTypeChillBlock, $nameBlock, $valueRelations);

                            $sss = [];
                            $arrName = explode(':', $nameBlock);
                            $nameCheck = is_array($arrName) ? $arrName[0] : $arrName;
                            foreach ($valueRelations as $key => $item) {
                                if ($key != 'hidden') {
                                    $typeItem = $item['type'];
                                    if ($item['type'] == 'image-hotspot-marker') {
                                        $typeItem = 'image';
                                    }
                                    $keyCheck = $nameCheck . ":" . $item['key'] . "." . $typeItem;
                                    $sss[] = $keyCheck;
                                }
                            }

                            foreach ($sss as $val) {
                                $v = $val;
                                if (strpos($val, 'featurette') !== false) {
                                    $v = str_replace("featurette", "block", $val);
                                }
                                foreach ($listAreaBlock as $key => $item) {
                                    if (strpos($key, $v) !== false) {
                                        $item["key"] = $key;
                                        $areaBlocksItem[$val][] = $item;
                                    }
                                }
                            }

                            // dd( $arrTypeChillBlock, $valueRelations, $sss, $listAreaBlock, $areaBlocksItem); 
                        }
                        
                        $editable[] = [
                            'title' => $keyEditable,
                            'value' => self::getValue($document->getEditable($keyEditable)->getData()),
                            'fieldType' => $valueEditable->getType()
                        ];
                    }
                }

                $listSnippet = [];
                foreach ($listingDoc as $item) {
                    if ($item['subtype'] == 'snippet') {
                        $listSnippet[] = $item;
                    }
                }

                $listImage = [];
                foreach ($listingAsset as $item) {
                    if ($item['subtype'] == 'image') {
                        $listImage[] = $item;
                    }
                }

                $assetListings = new \Pimcore\Model\Asset\Listing();
                $assetListings->setCondition("id != 1");
                $assetChillList = [];
                foreach ($assetListings as $asset) {
                    if ($asset->getType() == 'image') {
                        $linkImg = '/bundles/pimcoreadmin/img/flat-color-icons/asset.svg';
                    } elseif ($asset->getType() == 'folder') {
                        $linkImg = '/bundles/pimcoreadmin/img/flat-color-icons/folder.svg';
                    } elseif ($asset->getType() == 'video') {
                        $linkImg = '/bundles/pimcoreadmin/img/flat-color-icons/video_file.svg';
                    } elseif ($asset->getType() == 'text') {
                        $linkImg = '/bundles/pimcoreadmin/img/flat-color-icons/text.svg';
                    } elseif ($asset->getType() == 'document') {
                        $linkImg = '/bundles/pimcoreadmin/img/flat-color-icons/pdf.svg';
                    } else {
                        $linkImg = '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
                    }
                    if ($asset->getType() == 'folder') {

                    } else {
                        $assetChillList[] = [
                            'id' => $asset->getId(),
                            'name' => $asset->getKey(),
                            'subtype' => $asset->getType(),
                            'parentId' => $asset->getParentId(),
                            'linkImg' => $linkImg,
                        ];
                    }
                }

                $dataDocs = self::listingDoc();
                $dataAssets = self::listingAsset();
                $dataObjects = self::listingObject();
                $dataAllItem = array_merge($dataDocs, $dataAssets, $dataObjects);

                // dd($document);
                $data = [
                    'id' => $document->getId() ?? '',
                    'title' => $document->getKey(),
                    'prettyUrl' =>  method_exists($document, 'getPrettyUrl') ?  $document->getPrettyUrl() : '',
                    'description' => method_exists($document, 'getDescription') ? $document->getDescription() : '',
                    'controller' => method_exists($document, 'getController') ? $document->getController() : '',
                    'template' => method_exists($document, 'getTemplate') ? $document->getTemplate() : '',
                    'staticGeneratorEnabled' => method_exists($document, 'getStaticGeneratorEnabled') ? $document->getStaticGeneratorEnabled() : '',
                    'staticGeneratorLifetime' => method_exists($document, 'getStaticGeneratorLifetime') ? $document->getStaticGeneratorLifetime() : '',
                    'path' => method_exists($document, 'getPath') ? $document->getPath() : '',
                    'key' => method_exists($document, 'getKey') ? $document->getKey() : '',
                    'type' => method_exists($document, 'getType') ? $document->getType() : '',
                    'prettyUrl' => method_exists($document, 'getPrettyUrl') ? $document->getPrettyUrl() : '',
                    'editable' => $editable ?? '',
                    'valueRelation' => $listRelation ?? '',
                    'version' => $previousVersion ?? [],
                    'listingDoc' => $listingDoc ?? '',
                    'listingAsset' => $listingAsset ?? '',
                    'listingObject' => $listingObject ?? '',
                    'listRelations' => $listRelations ?? '',
                    'listBlock' => $blocksItem ?? '',
                    'snippetChoosed' => $snippetChoosed ?? '',
                    'listScheduled' => $listScheduled ?? '',
                    'listSnippet' => $listSnippet ?? '',
                    'listImage' => $listImage ?? '',
                    'assetChillList' => $assetChillList ?? '',
                    'pdfInfo' => $pdfInfo,
                    'href' => $href,
                    'dataVideos' => $dataVideos,
                    'allTypeSchedule' => $allTypeSchedule,
                    'schedulesItem' => $schedulesItem,
                    'dataAllItem' => $dataAllItem,
                    'listAreaBlock' => $areaBlocksItem,
                ];

                $nameParent = [];
                // lấy thông tin đường dẫn đến folder
                if ($document->getParentId() != 1) {
                    $pathParent = $document->getPath() . $document->getKey();
                    $nameParent = explode('/', $pathParent);
                    $result = array_filter($nameParent, function ($nameParent) {
                        return !empty($nameParent);
                    });
                    $nameParent = [];
                    $previousSubstring = '';
                    foreach ($result as $key => $val) {
                        $idChill = '';
                        $substring = '';

                        if (strpos($document->getPath(), $val) !== false) {
                            $substring = $previousSubstring . '/' . $val;
                            $doc = Document::getByPath($substring);
                            if ($doc) {
                                $idChill = $doc->getId();
                            }
                        }
                        $nameParent[] = [
                            'id' => $idChill,
                            'name' => $val,
                            'end' =>  $key == array_key_last($result),
                        ];
                        $previousSubstring = $substring;
                    }
                }

                // lấy thông tin sidebar
                $sidebar = [];
                $sidebar = DocumentServices::getJson($document);

                return $this->renderWithInertia('Pages/Document/Detail', [
                    'data' => $data,
                    'nameParent' => $nameParent,
                    'sidebar' => $sidebar,
                ]);
            } else {
                $params = [
                    'folderId' => $document->getId(),
                ];
                return $this->redirectToRoute('vuetify_doc_listing', $params);
            }
        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }

    /**
     * @Route("/edit", name="vuetify_doc_edit", methods={"POST", "GET"}, options={"expose"=true}))
     */
    public function editAction(Request $request)
    {
        $data = $request->request->all();

        $document = Document::getById((int)$data['id']);

        try {
            if ($document) {
                $errors = [];
                if ($document->getType() == 'page') {
                    $document->setTitle($data['title']);
                    $document->setDescription($data['description']);
                    $document->setPrettyUrl($data['prettyUrl']);
                    $document->setStaticGeneratorEnabled($data['enabled']);
                    if ($data['lifetime'] && ($data['lifetime'] != "null")) {
                        $document->setStaticGeneratorLifetime($data['lifetime']);
                    }
                }
                if ($document->getType() == 'link' && isset($data['href'])) {
                    $repornse = FieldServices::setHref($document, $data['href']);

                    if (isset($repornse['status']) && ($repornse['status'] == 500)){
                        return new JsonResponse(['error' => $repornse['messsage']]);
                    }
                } else {
                    $document->setController($data['controller']);
                    $document->setTemplate($data['template']);
                }
                $document->save();

                $arrNoSaveInFor = ['title', 'description', 'prettyUrl', 'controller', 'template', 'enabled', 'lifetime', 'id', 'href', 'linktype', 'internalType', 'internal'];
                $arrss = [];
                foreach ($data as $key => $value) {
                    if (!in_array($key, $arrNoSaveInFor)) {
                        $decode = json_decode($value);
                        $arrKey = explode('_', $key);
                        
                        $function = is_array($arrKey) ? 'set'. ucwords($arrKey[0]) :  'set'. ucwords($arrKey);
                        // $arrss[] =  $arrKey;
                        $repornse = FieldServices::{$function}($document, $decode, $value);

                        if (isset($repornse['status']) && ($repornse['status'] == 500)){
                            $errors[] =  $repornse['messsage'];
                        }
                    }
                }

                if ($errors) {
                    $messError = '';
                    foreach ($errors as $item) {
                        $messError = $messError + $item + '||';
                    }
                    return new JsonResponse(['error' => $messError]);
                }
                return new JsonResponse(['success' => 'Update successfully']);
            } else {
                return new JsonResponse(['error' => 'Update failed']);
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }
    
        return new JsonResponse();
    }

    public function abcAction()
    {
        return $this->render('default/post.html.twig');
    }

    public function postAction()
    {
        return $this->render('default/content.html.twig');
    }

    public function abcdAction()
    {
        return $this->render('default/testTemplate.html.twig');
    }

    /**
     * @Route("/delete", name="vuetify_doc_delete", methods={"POST", "GET"}, options={"expose"=true}))
     */
    public function deleteAction(
        Request $request,
    ) {
        // if ($this->checkRole('homeDocumentDelete')) {
            $ids = $request->get('id');
            if (is_array($ids)) {
                try {
                    foreach ($ids as $id) {
                        $document = Document::getById((int) $id);

                        if ($document) {
                            $document->delete();
                            $this->addFlash("success", "Delete ". $document->getKey() ." success");
                        } else {
                            $this->addFlash("error", "Can not find document to be deleted");
                        }
                    }
                } catch (\Throwable $th) {
                    return new JsonResponse(['warning' => $th->getMessage()]);
                }
                return new JsonResponse();
            } else {
                $document = Document::getById((int) $ids);

                if ($document) {
                    $folderId = $document->getParentId();
                    $document->delete();
                    $this->addFlash("success", "Delete ". $document->getKey() ." success");
                } else {
                    $this->addFlash("error", "Can not find document to be deleted");
                }

                $params = [];
                if ($folderId && ($folderId != 1)) {
                    $params = [
                        'folderId' => $folderId,
                    ];
                }
                $page = $request->get('page');
                if ($page && ($page == "catalog")) {
                    return $this->redirectToRoute('cms_document_catalog');
                }
                return $this->redirectToRoute('vuetify_doc_listing', $params);
            }

        // } else {
        //     return $this->renderWithInertia('Pages/AccessDiend');
        // }
    }

    /**
     * @Route("/edit-mode/{id}", name="vuetify_doc_edit_mode", methods={"GET"}, options={"expose"=true}))
     */
    public function editModeAction(Request $request)
    {
        $document = Document::getById((int) $request->get('id'));
        // dd($document->getController());
        if ($document->getTemplate()) {
            return $this->render($document->getTemplate(), [
                'editmode' => true
            ]);
        } else {
            return $this->forward($document->getController());
        }
    }

    /**
     * @Route("/renderlet", methods={"POST", "GET"})
     */
    public function renderletAction(Request $request): Response
    {
        $document = Document::getById((int) $request->get('id'));
        $option = json_decode($request->get('option'), true);
        $render = $this->renderRenderlet($document, 'renderlet', $request->get('name'), $option, false);

        if ($request->get('changeId')) {
            $render->setDataFromEditmode([
                'id' => (int) $request->get('changeId'),
                'type' => $request->get('type'),
                'subtype' => $request->get('subtype'),
            ]);
        }

        return $this->json(['data' => $render->frontend()]);
    }

    #[Template('content/my_gallery.html.twig')]
    public function myGalleryAction(Request $request): array
    {
        if ('asset' === $request->get('type')) {
            $asset = Asset::getById((int) $request->get('id'));
            if ('folder' === $asset->getType()) {
                return [
                    'assets' => $asset->getChildren()
                ];
            } elseif ('image' === $asset->getType()) {
                return [
                    'assets' => $asset
                ];
            }
        }

        // if ('object' === $request->get('type')) {
        //     $object = DataObject::getById((int) $request->get('id'));
        //     if ('folder' === $object->getType()) {
        //         return [
        //             'objects' => $object->getChildren()
        //         ];
        //     } elseif ('object' === $object->getType()) {
        //         return [
        //             'objects' => $object
        //         ];
        //     }
        // }

        return ['assets' => $asset];
    }

    // $previousVersion = $versions[1];

    // $previousObject = $previousVersion->getData();

    // $version = [
    //     'id' => $previousObject->getId(),
    //     'title' => $previousObject->getTitle(),
    //     'description' => $previousObject->getDescription(),
    //     'controller' => $previousObject->getController(),
    //     'template' => $previousObject->getTemplate(),
    //     'path' => $previousObject->getPath(),
    //     'key' => $previousObject->getKey(),
    //     'type' => $previousObject->getType(),
    //     'prettyUrl' => $previousObject->getPrettyUrl(),
    //     // 'editable' => $editable
    // ];

    public static function getValue($data)
    {

        if (is_string($data)) {
            return $data;
        } elseif (is_array($data)) {
            // foreach ($data as $item) {
            //     if ($item->getType() == "object") {
            //         $path = $item->getUserName();
            //     } else {
            //         $path = $item->getPath() . $item->getFileName();
            //     }
            //     return $path;
            // }
        }
        return null;
    }


    /**
     * @Route("/get-class", name="vuetify_get_class", methods={"POST", "GET"}, options={"expose"=true}))
    */
    public function getClass(Request $request)
    {
        $type = $request->get('type');

        $listClass = [];
        
        if ($type) {
            $listClass = DocumentServices::getListClass($type, $request);
        }
        return new JsonResponse(['data' => $listClass]);
    }


    /**
     * @Route("/get-list-options", methods={"POST", "GET"})
     */
    public function getListOptions(Request $request): Response
    {
        $type = $request->get('type');
        $subTypes = $request->get('subTypes');
        $arrTypes = $subTypes ? json_decode($subTypes, true) : [];

        $types = [];
        if (($arrTypes != null) && is_array($arrTypes)) {
            foreach ($arrTypes as $key => $value) {
                if ($key == 'asset') {
                    foreach ($value as $v) {
                        $types[$key][] = ['assetTypes' => $v];
                    }
                }
                if ($key == 'document') {
                    foreach ($value as $v) {
                        $types[$key][] = ['documentTypes' => $v];
                    }
                }
                if ($key == 'object') {
                    foreach ($value as $v) {
                        $types[$key][] = ['classes' => $v];
                    }
                }
            }
        } elseif ($arrTypes == 'snippet') {
            $types['document'][] = [
                'documentTypes' => 'snippet'
            ];
        } else {
            $types = [];
        }

        $options = DocumentServices::getOptions($type, $types);
        // dd($options);
        return $this->json(['data' => $options]);
    }
}
