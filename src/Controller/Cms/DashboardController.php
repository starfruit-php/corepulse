<?php

namespace CorepulseBundle\Controller\Cms;

use CorepulseBundle\Services\APIService;
use DateTime;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Db;
use CorepulseBundle\Services\ReportServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends BaseController
{

    /**
     *
     * @Route("/dashboard", name="vuetify_dashboard", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function dashboard(Request $request)
    {
        $this->isGrantedBase('role_permissions_dashboard');
        $this->addFlash('success', '111');

        // get total
        $conditionQuery = 'id != 1 AND type != "folder"';
        $conditionParams = [];

        $list = new Asset\Listing();
        $list->setCondition($conditionQuery, $conditionParams);
        $totalAsset = $list->count();

        $document = new \Pimcore\Model\Document\Listing();
        $document->setCondition($conditionQuery, $conditionParams);
        $totalDoc = $document->count();

        $object = new \Pimcore\Model\DataObject\Listing();
        $object->setCondition($conditionQuery, $conditionParams);
        $totalObject = $object->count();

        $users = new \Pimcore\Model\User\Listing();
        $users->setCondition('id != 0');
        $totalUser = $users->count();

        //get list changes
        $query = 'ctype = "object" AND date >= :dateForm AND date <= :dateTo';
        $params = [
            'dateForm' => time() - 604800,
            'dateTo' => time(),
        ];

        $version = new \Pimcore\Model\Version\Listing();
        $version->setCondition($query, $params);
        $version->setOrderKey('date');
        $version->setOrder('DESC');
        $version->setLimit(20);
        $version->load();
        $totalItems = $version->count();

        $listing = [];
        foreach ($version as $item) {
            $objectDetail = DataObject::getById($item->getCid());
            $infoUser = User::getById($item->getUserId());

            $listing[] = [
                'id' => $item->getCid(),
                'name' => $objectDetail?->getKey(),
                'type' => $objectDetail?->getClassName(),
                'userName' => $infoUser?->getName(),
                'date' => self::getTimeAgo($item->getDate()),
            ];
        }

        // get chart
        $dataChange = new \Pimcore\Model\Version\Listing();
        $dataChange->setCondition(
            'date >= :dateForm AND date <= :dateTo',
            [
                'dateForm' => time() - 2592000,
                'dateTo' => time(),
            ]
        );
        $dataChange->setOrderKey('date');
        $dataChange->setOrder('DESC');
        $listChange = [];
        foreach ($dataChange as $item) {
            $listChange[] = [
                'ctype' => $item->getCtype(),
                'date' => date('Y/m/d', $item->getDate()),
            ];
        }

        $countByDateAndCtype = [];
        foreach ($listChange as $item) {
            $date = $item["date"];
            $ctype = $item["ctype"];

            if (!isset($countByDateAndCtype[$date])) {
                $countByDateAndCtype[$date] = ["asset" => 0, "object" => 0, 'document' => 0];
            }

            $countByDateAndCtype[$date][$ctype]++;
        }
        $resultArray = [];

        foreach ($countByDateAndCtype as $date => $counts) {
            $dateUpdate = ['date' => $date];
            $resultArray[] =  array_merge($counts, $dateUpdate);
        }

        $chart = [
            'type' => 'line',
            'label' => 'date',
            'column' => [
                'asset',
                'object',
                'document'
            ]
        ];

        $chartData = [];
        if ($resultArray) {
            $chartData = ReportServices::getChartData($resultArray, $chart);
        }
        $chartData['colors'] = ['#F44336', '#2196F3', '#FFCB7F'];

        //get folder object
        $objectSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "object"', []);
        $data = [];
        if ($objectSetting !== null && $objectSetting) {
            // lấy danh sách bảng
            $query = 'SELECT * FROM `classes`';
            $classListing = Db::get()->fetchAllAssociative($query);
            $dataObjectSetting = json_decode($objectSetting['config']) ?? [];
            foreach ($classListing as $class) {
                if (in_array($class['id'], $dataObjectSetting)) {

                    $newData["id"] = $class["id"];
                    $newData["name"] = $class["name"];
                    $newData["fullPath"] = '/bundles/pimcoreadmin/img/flat-color-icons/folder.svg';
                    $data[] = $newData;
                }
            }
        }

        // get overview in plausible
        $plasibleSet = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $plasibleSet = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]); 
        }
        $visitors = [];
        $totalArr = [];
        $topPages = [];
        
        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $url_api = $url . '/api/v1/stats/timeseries?site_id=' . $siteId . '&start_date=' . $start_date . '&end_date=' . $current_date;
            $header['Authorization'] = "Bearer " . $apiKey;
            $responseView = APIService::post($url_api, 'GET',  null, $header);
            if ($responseView && isset($responseView['results'])) {
                $sliceVisi = $responseView['results'];
                if (count($sliceVisi) > 20) {
                    $visitors = array_slice($sliceVisi, 19);
                }
            }

            // realtime
            $url_api1 = $url . '/api/v1/stats/aggregate?site_id=' . $siteId . '&start_date=' . $start_date . '&end_date=' . $current_date . '&metrics=visitors,pageviews,bounce_rate,visit_duration';
            $responseTotal = APIService::post($url_api1, 'GET',  null, $header);
            if ($responseTotal && isset($responseTotal['results'])) {
                $totalArr = $responseTotal['results'];
            }

            // toppages
            $url_api_top_pages = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . '&start_date=' . $start_date . '&end_date=' . $current_date .'&property=event:page&limit=5';
            $responseTopPage = APIService::post($url_api_top_pages, 'GET',  null, $header);
            if ($responseTopPage && isset($responseTopPage['results'])) {
                $topPages = $responseTopPage['results'];
            }
        }
        
        $chartVi = [
            'type' => 'line',
            'label' => 'date',
            'column' => [
                'visitors',
            ]
        ];
        $chartVisitors = [];
        if ($visitors) {
            $chartVisitors = ReportServices::getChartData($visitors, $chartVi);
            $chartVisitors['colors'] = ['#8B4500'];
        }
        // toppages
        $chartPages = [
            'type' => 'bar',
            'label' => 'page',
            'column' => [
                'visitors',
            ]
        ];
        $chartTopPages = [];
        if ($topPages) {
            $chartTopPages = ReportServices::getChartData($topPages, $chartPages);
        }

        return $this->renderWithInertia('Pages/Dashboard', [
            'totalAsset' => $totalAsset,
            'totalObject' => $totalObject,
            'totalDoc' => $totalDoc,
            'totalUser' => $totalUser,
            'listing' => $listing,
            'totalItems' => $totalItems,
            'data' => $data,
            'chartData' => $chartData,
            'chartVisitors' => $chartVisitors,
            'totalArr' => $totalArr,
            'chartTopPages' => $chartTopPages,
        ]);
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
}
