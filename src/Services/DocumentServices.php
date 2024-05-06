<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Controller\Cms\FieldController;
use Google\Service\AIPlatformNotebooks\Status;
use Pimcore\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\User as AdminUser;

class DocumentServices
{
    const KEY_DOCUMENT = 'Document';
    const KEY_OBJECT = 'DataObject';
    const KEY_ASSET = 'Asset';

    public static function createDoc($key, $title, $type, $parentId)
    {
        $page = new \Pimcore\Model\Document\Page();

        if ($type == 'Snippet') {
            $page = new \Pimcore\Model\Document\Snippet();
        } elseif ($type == 'Link') {
            $page = new \Pimcore\Model\Document\Link();
        } elseif ($type == 'Email') {
            $page = new \Pimcore\Model\Document\Email();
        } elseif ($type == 'Hardlink') {
            $page = new \Pimcore\Model\Document\Hardlink();
        } elseif ($type == 'Folder') {
            $page = new \Pimcore\Model\Document\Folder();
        } elseif ($type == 'Page') {
            $page->setTitle($title);
        }

        $page->setKey($key);
        $page->setParentId($parentId);

        $page->save();

        return $page;
    }

    public static function getListClass($type, $request)
    { 
        $search = $request->get('search');

        $conditionQuery = "id != 1 AND type != 'folder'";
        $conditionParams = [];

        $listClass = [];
        if ($type == "document") {
            if ($search) {
                $conditionQuery .= ' AND `key` LIKE :search';
                $conditionParams['search'] = '%' . $search . '%';
            }
            $documentListing = new \Pimcore\Model\Document\Listing();
            $documentListing->setCondition($conditionQuery, $conditionParams);
            foreach ($documentListing as $doc) {
                    $linkImg = self::getThumbnailPath($doc);
                    $listClass[] = [
                        'id' => $doc->getId(),
                        'name' => $doc->getKey(),
                        'subtype' => $doc->getType(),
                        'type' => 'document',
                        'fullPath' => $linkImg,
                    ];
            }
        } 
        if ($type == "asset") {
            if ($search) {
                $conditionQuery .= ' AND filename LIKE :search';
                $conditionParams['search'] = '%' . $search . '%';
            }
            $assetListing = new \Pimcore\Model\Asset\Listing();
            $assetListing->setCondition($conditionQuery, $conditionParams);
            foreach ($assetListing as $asset) {
                $linkImg = AssetServices::getThumbnailPath($asset);
                $listClass[] = [
                    'id' => $asset->getId(),
                    'name' => $asset->getKey(),
                    'subtype' => $asset->getType(),
                    'type' => 'asset',
                    'fullPath' => $linkImg,
                ];
            }
        } 
        if ($type == "object") {
            if ($search) {
                $conditionQuery .= ' AND `key` LIKE :search';
                $conditionParams['search'] = '%' . $search . '%';
            }
            $objectListing = new \Pimcore\Model\DataObject\Listing();
            $objectListing->setCondition($conditionQuery, $conditionParams);
            foreach ($objectListing as $object) {
                $listClass[] = [
                    'id' => $object->getId(),
                    'name' => $object->getKey(),
                    'subtype' => $object->getType(),
                    'type' => 'object',
                    'class' => ($object->getType() != 'folder') ? $object?->getClass()->getName() : '',
                    'fullPath' => "/bundles/pimcoreadmin/img/flat-color-icons/object.svg",
                ];
            }
        }

        if ($type == "snippet") {
            $conditionQuery .= ' AND `type` = :type';
            $conditionParams['type'] = $type;

            if ($search) {
                $conditionQuery .= ' AND `key` LIKE :search';
                $conditionParams['search'] = '%' . $search . '%';
            }
            $documentListing = new \Pimcore\Model\Document\Listing();
            $documentListing->setCondition($conditionQuery, $conditionParams);
            foreach ($documentListing as $doc) {
                    $linkImg = self::getThumbnailPath($doc);
                    $listClass[] = [
                        'id' => $doc->getId(),
                        'name' => $doc->getKey(),
                        'subtype' => $doc->getType(),
                        'type' => 'document',
                        'fullPath' => $linkImg,
                    ];
            }
        } 

        return $listClass;
    }

    // lấy dữ liệu đổ vào cái field tương ứng
    public static function getDataDocument($document) {
        $data = [];

        if ($document) {
            foreach ($document->getEditables() as $key => $value) {
                $type = $value->getType();
                $function = 'get'. ucwords($type);
                $data[$key] = FieldServices::{$function}($document, $value);
            }
        }
        return $data;
    }

    public static function getThumbnailPath($doc)
    {
        if ($doc->getType() == "folder") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/folder.svg';
        } elseif ($doc->getType() == "page" && ($doc->getId() != 1)) {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/page.svg';
        } elseif ($doc->getType() == "link") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/link.svg';
        } elseif ($doc->getType() == "snippet") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/snippet.svg';
        } elseif ($doc->getType() == "email") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/email.svg';
        } elseif ($doc->getType() == "hardlink") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/hardlink.svg';
        } elseif ($doc->getType() == "printpage") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/print.svg';
        } elseif ($doc->getType() == "printcontainer") {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/book.svg';
        } elseif ($doc->getId() == 1) {
            $publicURL = '/bundles/pimcoreadmin/img/flat-color-icons/home-gray.svg';
        } else {
            $publicURL = '';
        }

        return $publicURL;
    }

    public static function getTimeAgo($timestamp)
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

    static public function getJson($document)
    {
        $userOwner =  AdminUser::getById($document->getUserOwner());
        $userModification = AdminUser::getById($document->getUserModification());

        $data = [
            'id' => $document->getId(),
            'key' => $document->getKey(),
            'published' => $document->getPublished(),
            'fullPath' => $document->getFullPath(),
            'creationDate' => $document->getCreationDate() ? date('d-m-Y', $document->getCreationDate()) : '',
            'modificationDate' => $document->getModificationDate() ? date('d-m-Y', $document->getModificationDate()) : '',
            'type' => $document->getType(),
            // 'template' => $document->getTemplate(),
            // 'controller' => $document->getController(),
            'userOwner' => $userOwner ? $userOwner->getName() : '',
            'userModification' => $userModification ? $userModification->getName() : '',
        ];

        return $data;
    }

    // lấy danh sách các field xuất hiện trong block được user set
    public static function filterArray($inputArray, $filterKey) {
        $filteredValues = [];

        foreach ($inputArray as $key => $value) {
            if (strpos($key, $filterKey) === 0) {
                // Remove the filter key prefix from the current key
                $cleanKey = substr($key, strlen($filterKey) + 1);

                // Add the cleaned key to the result array
                $filteredValues[$key] = $cleanKey;
            }
        }

        return $filteredValues;
    }
    
    // lấy danh sách name các field xuất hiện trong block được user set
    public static function getEditableBlock($document, $nameBlock) {
        $filteredArray = self::filterArray($document->getEditables(), $nameBlock);
        $arrTypeChillBlock = [];
        foreach ($filteredArray as $k => $v) {
            $type = explode('.', $v);
            $arrTypeChillBlock[$k] = $document->getEditable($k)->getType();
        }

        return $arrTypeChillBlock;
    }


    // lấy dữ liệu của các field trong block
    public static function getDataBlock($document, $arrTypeChillBlock) {
        $list = [];
        $arrType = ['input', "wysiwyg", "select", "textarea"];
        foreach ($arrTypeChillBlock as $keyBlockT => $type) {
            $titleBlock = '';
            if (in_array($type, $arrType)) {
                $titleBlock = $document->getEditable($keyBlockT)?->getText();
            }

            $contentField = $titleBlock;
            if ($type == 'image' || $type == 'video' || $type == 'pdf') {
                $idImage = $document->getEditable($keyBlockT)?->getId();
                $asset = Asset::getById((int)$idImage);
                if ($asset) {
                    $contentField = $asset->getFullPath();
                }
            }
            if ($type == "video") {
                $contentField =  $document->getEditable($keyBlockT)->getData();
            }
            if ($type == "date") {
                $contentField = $document->getEditable($keyBlockT)?->getDate()?->format('Y-m-d');
            }
            if ($type == "checkbox") {
                $contentField = $document->getEditable($keyBlockT)?->getValue();
            }
            if ($type == 'link') {
                $contentField = $document->getEditable($keyBlockT)?->getData();
            }
            if ($type == 'numeric') {
                $contentField = $document->getEditable($keyBlockT)?->getNumber();
            }
            if ($type == 'multiselect') {
                $contentField = $document->getEditable($keyBlockT)?->getValues();
            }
            if ($type == 'table') {
                $contentField = $document->getEditable($keyBlockT)?->getData();
            }
            if ($type == 'snippet') {
                $idSnippet = $document->getEditable($keyBlockT)?->getId();
                $snippeted = Document::getById((int)$idSnippet);
                $fullPath = DocumentServices::getThumbnailPath($snippeted);

                $contentField = [
                    'id' => $snippeted->getId(),
                    'name' => $snippeted->getKey(),
                    'subtype' => $snippeted->getType(),
                    'type' => 'documment',
                    'fullPath' => $fullPath,
                ];
            }

            $list[$keyBlockT] = 
            [
                'type' => $type,
                'value' => $contentField
            ];
        }

        return $list;
    }

    static public function getOptions($type, $arrTypes)
    {
        $options = [];
        $allowedFieldTypes = ['manyToOneRelation', 'manyToManyRelation', 'advancedManyToManyRelation'];

        if (in_array($type, $allowedFieldTypes)) {

            if (!$arrTypes) {
                $arrTypes = [
                    'object' => [],
                    'document' => [],
                    'asset' => [],
                ];
            } 
 
            foreach ($arrTypes as $key => $value) {
                if ($key == 'object') {
                    $classes = $value;
                    $blackList = ["user", "role"];
                    $listObject = FieldController::getClassList($blackList);
    
                    $options[] = FieldController::getRelationType($classes, self::KEY_OBJECT, 'classes', $listObject);
                }
    
                if ($key == 'document') {
                    $document = $value;
                    $listDocument = ['email', 'link', 'hardlink', 'snippet', 'folder', 'page'];
    
                    $options[] = FieldController::getRelationType($document, self::KEY_DOCUMENT, 'documentTypes', $listDocument);
                }
    
                if ($key == 'asset') {
                    $asset = $value;
                    $listAsset = ['archive', 'image', 'audio', 'document', 'text', 'folder', 'video', 'unknown'];
    
                    $options[] = FieldController::getRelationType($asset, self::KEY_ASSET, 'assetTypes', $listAsset);
                }
            }

        }

        return $options;
    }
}