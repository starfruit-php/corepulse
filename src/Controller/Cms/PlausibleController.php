<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use CorepulseBundle\Services\Helper\TreeHelper;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use CorepulseBundle\Services\Helper\SearchHelper;
use Knp\Component\Pager\PaginatorInterface;
use CorepulseBundle\Controller\Cms\ObjectController;
use CorepulseBundle\Services\APIService;
use CorepulseBundle\Services\ReportServices;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;

class PlausibleController extends BaseController
{
     /**
     * @Route("/setting", name="cms_setting_plausible", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function settingAction(Request $request)
    {
        return $this->renderWithInertia('Pages/Plausible/Setting');
    }

    /**
    * @Route("/widget", name="cms_widget_plausible", methods={"GET", "POST"}, options={"expose"=true}))
    */
    public function widgetAction(Request $request)
    {
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
        $devices = [];
        $sources = [];

        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));
        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $url_api = $url . '/api/v1/stats/timeseries?site_id=' . $siteId . '&period=custom&date=' . $start_date . ',' . $current_date;
            $header['Authorization'] = "Bearer " . $apiKey;
            $responseView = APIService::post($url_api, 'GET',  null, $header);
            if ($responseView && isset($responseView['results'])) {
                $visitors = $responseView['results'];
            }

            // realtime
            $url_api1 = $url . '/api/v1/stats/aggregate?site_id=' . $siteId . '&period=custom&date=' . $start_date . ',' . $current_date . '&metrics=visitors,pageviews,bounce_rate,visit_duration,views_per_visit,visits';
            $responseTotal = APIService::post($url_api1, 'GET',  null, $header);
            if ($responseTotal && isset($responseTotal['results'])) {
                $totalArr = $responseTotal['results'];
            }

            // toppages
            $url_api_top_pages = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . '&period=custom&date=' . $start_date . ',' . $current_date .'&property=event:page&limit=5';
            $responseTopPage = APIService::post($url_api_top_pages, 'GET',  null, $header);
            if ($responseTopPage && isset($responseTopPage['results'])) {
                $topPages = $responseTopPage['results'];
            }

            // Devices
            $url_api_devices = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . '&period=6mo&property=visit:browser&metrics=visitors,bounce_rate&limit=5';
            $responseDevices = APIService::post($url_api_devices, 'GET',  null, $header);
            if ($responseDevices && isset($responseDevices['results'])) {
                $devices = $responseDevices['results'];
            }

            // topSoucres
            $url_api_sources = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . '&period=6mo&property=visit:source&metrics=visitors,bounce_rate&limit=5';
            $responseSources = APIService::post($url_api_sources, 'GET',  null, $header);
            if ($responseSources && isset($responseSources['results'])) {
                $sources = $responseSources['results'];
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
            $chartVisitors['title'] = ['Visitors'];
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
            $chartTopPages['title'] = ['Page'];
        }

        // devices
        $chartDevi = [
            'type' => 'bar',
            'label' => 'browser',
            'column' => [
                'visitors',
            ]
        ];
        $chartDevices = [];
        if ($devices) {
            $chartDevices = ReportServices::getChartData($devices, $chartDevi);
            $chartDevices['title'] = ['Browser'];
        }

        // sources
        $chartSour = [
            'type' => 'bar',
            'label' => 'source',
            'column' => [
                'visitors',
            ]
        ];

        $chartSources = [];
        if ($sources) {
            $chartSources = ReportServices::getChartData($sources, $chartSour);
            $chartSources['title'] = ['Source'];
        }

        return $this->renderWithInertia('Pages/Plausible/Widget', [
            'chartVisitors' => $chartVisitors,
            'totalArr' => $totalArr,
            'chartTopPages' => $chartTopPages,
            'chartDevices' => $chartDevices,
            'chartSources' => $chartSources,
            'arrChart' => [],
        ]);
    }

    /**
     * @Route("/acccount", name="vuetify_plausible_account", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function accountLogin(Request $request)
    {
        if ($request->isMethod('POST')) {
            $this->addFlash("success", 'Save Success');
        }

        return $this->renderWithInertia('Pages/Plausible/Setting');
    }

    /**
     * @Route("/setup", name="vuetify_plausible_setup", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function setupAction(Request $request)
    {
        $item = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $item = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]);
        }
        $plausibleSet = [];
        if ($item) {
            $plausibleSet = $item;
        }
        if ($request->isMethod('POST')) {
            $uniqueIdentifier = 1;
            if ($request->get('domain')) {
                $domain = $request->get('domain');
                $plausibleSet['domain'] = $domain;
            }

            if ($request->get('siteId')) {
                $siteId = $request->get('siteId');
                $plausibleSet['siteId'] = $siteId;
            }

            if ($request->get('apiKey')) {
                $apiKey = $request->get('apiKey');
                $plausibleSet['apiKey'] = $apiKey;
            }

            if ($request->get('link')) {
                $link = $request->get('link');
                $plausibleSet['link'] = $link;
            }

            if ($request->get('domain') || $request->get('siteId') || $request->get('apiKey')) {
                if ($item) {
                    Db::get()->update(
                        'corepulse_plausible',
                        $plausibleSet,
                        ['id' => $uniqueIdentifier]
                    );
                } else {
                    Db::get()->insert(
                        'corepulse_plausible',
                        [
                            'domain' => $plausibleSet['domain'],
                            'siteId' => $plausibleSet['siteId'],
                            'apiKey' => $plausibleSet['apiKey'],
                            'link' => $plausibleSet['link'],
                        ]
                    );
                }
            }

            $this->addFlash("success", 'Save Success');
        }

        return $this->renderWithInertia('Pages/Plausible/Setting', ['plausibleSetting' => $plausibleSet]);
    }

    /**
     * @Route("/get-list-views-plausible", methods={"POST", "GET"})
     */
    public function getListView(Request $request): Response
    {
        $plasibleSet = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $plasibleSet = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]); 
        }
        $visitors = [];
        $metrics = 'visitors';
        if ($request->get('metrics')) {
            $metrics = $request->get('metrics');
        }

        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));

        if ($request->get('current_date')) {
            $current_date = $request->get('current_date');
        }
        if ($request->get('start_date')) {
            $start_date = $request->get('start_date');
        }

        $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;

        $time = $request->get('time');
        if ($time && $time == 'Today') {
            $dateTime = '&period=day';
        }

        if ($time && $time == '7 day') {
            $dateTime = '&period=7d';
        }

        if ($time && $time == '30 day') {
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '6 month') {
            $dateTime = '&period=6mo';
        }

        if ($time && $time == '12 month') {
            $dateTime = '&period=12mo';
        }

        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $url_api = $url . '/api/v1/stats/timeseries?site_id=' . $siteId . $dateTime .'&metrics=' . $metrics;
        
            $header['Authorization'] = "Bearer " . $apiKey;
            $responseView = APIService::post($url_api, 'GET',  null, $header);
            if ($responseView && isset($responseView['results'])) {
                $visitors = $responseView['results'];
               
            }

        }
        $arr = [];
        foreach ($visitors as $item) {
            $arr[] = [
                $metrics => $item[$metrics] ? $item[$metrics] : 0,
                'date' => $item['date']
            ];
        }
        $chartVi = [
            'type' => 'line',
            'label' => 'date',
            'column' => [
                $metrics,
            ]
        ];
        $chartVisitors = [];
        if ($arr) {
            $chartVisitors = ReportServices::getChartData($arr, $chartVi);
        }
        $chartVisitors['colors'] = ['#8B4500'];
        $chartVisitors['title'] = [ucwords($metrics)];

        return $this->json(['data' => $chartVisitors]);
    }

    /**
     * @Route("/get-list-views-plausible-devi", methods={"POST", "GET"})
     */
    public function getListDevi(Request $request): Response
    {
        $plasibleSet = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $plasibleSet = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]); 
        }

        $devices = [];
        $property = 'browser';
        if ($request->get('property')) {
            $property = $request->get('property');
            if ($property == "size") {
                $property = 'device';
            }
        }

        $metrics = 'visitors';
        if ($request->get('metrics')) {
            $metrics = $request->get('metrics');
        }

        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
        
        $dateTime = '&period=6mo';

        $time = $request->get('time');
        if ($time && $time == 'Today') {
            $dateTime = '&period=day';
        }

        if ($time && $time == '7 day') {
            $dateTime = '&period=7d';
        }

        if ($time && $time == '10 day') {
            $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '30 day') {
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '12 month') {
            $dateTime = '&period=12mo';
        }

        if ($request->get('current_date') || $request->get('start_date')) {
            $dateTime = '&period=custom&date=' . $request->get('start_date') . ',' . $request->get('current_date'); 
        }

        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $header['Authorization'] = "Bearer " . $apiKey;
            $url_api_devices = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . $dateTime . '&property=visit:' . $property . '&metrics=' . $metrics . '&limit=5';
            $responseDevices = APIService::post($url_api_devices, 'GET',  null, $header);
            if ($responseDevices && isset($responseDevices['results'])) {
                $devices = $responseDevices['results'];
            }

        }
        
        $chartDevi = [
            'type' => 'bar',
            'label' => $property,
            'column' => [
                $metrics,
            ]
        ];
        $chartDevices = [];
        if ($devices) {
            $chartDevices = ReportServices::getChartData($devices, $chartDevi);
        }
        $chartDevices['title'] = [ucwords($property)];

        return $this->json(['data' => $chartDevices]);
    }

    /**
     * @Route("/get-list-views-plausible-pages", methods={"POST", "GET"})
     */
    public function getListPages(Request $request): Response
    {
        $plasibleSet = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $plasibleSet = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]); 
        }

        $topPages = [];
        $property = 'event:page';
        if ($request->get('property')) {
            $property = $request->get('property');
            if ($property == "Top Pages") {
                $property = 'event:page';
            }
            if ($property == "Entry Pages") {
                $property = 'visit:entry_page';
            }
            if ($property == "Exit Pages") {
                $property = 'visit:exit_page';
            }
        }


        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));
        
        $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date; 

        $time = $request->get('time');
        if ($time && $time == 'Today') {
            $dateTime = '&period=day';
        }

        if ($time && $time == '7 day') {
            $dateTime = '&period=7d';
        }

        if ($time && $time == '30 day') {
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '6 month') {
            $dateTime = '&period=6mo';
        }

        if ($time && $time == '12 month') {
            $dateTime = '&period=12mo';
        }

        if ($request->get('current_date') || $request->get('start_date')) {
            $dateTime = '&period=custom&date=' . $request->get('start_date') . ',' . $request->get('current_date'); 
        }

        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $header['Authorization'] = "Bearer " . $apiKey;
            $url_api_top_pages = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . $dateTime .'&property=' . $property . '&limit=5';
            $responseTopPage = APIService::post($url_api_top_pages, 'GET',  null, $header);
            if ($responseTopPage && isset($responseTopPage['results'])) {
                $topPages = $responseTopPage['results'];
            }

        }

        // toppages
        $chartPages = [
            'type' => 'bar',
            'label' => explode(':', $property)[1],
            'column' => [
                'visitors',
            ]
        ];
        $chartTopPages = [];
        if ($topPages) {
            $chartTopPages = ReportServices::getChartData($topPages, $chartPages);
            $chartTopPages['title'] = [$request->get('property')];
        }

        return $this->json(['data' => $chartTopPages]);
    }

    /**
     * @Route("/add-arr-chart", methods={"POST", "GET"})
     */
    public function addArrChart(Request $request): Response
    {
        $plasibleSet = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $plasibleSet = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]); 
        }
        $visitors = [];
        $metrics = 'visitors';
        if ($request->get('metrics')) {
            $metrics = $request->get('metrics');
        }

        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));

        if ($request->get('current_date')) {
            $current_date = $request->get('current_date');
        }
        if ($request->get('start_date')) {
            $start_date = $request->get('start_date');
        }

        $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;

        $time = $request->get('time');
        if ($time && $time == 'Today') {
            $dateTime = '&period=day';
        }

        if ($time && $time == '7 day') {
            $dateTime = '&period=7d';
        }

        if ($time && $time == '30 day') {
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '6 month') {
            $dateTime = '&period=6mo';
        }

        if ($time && $time == '12 month') {
            $dateTime = '&period=12mo';
        }

        if ($request->get('start_date')) {
            $time = date('D/m', strtotime($start_date)) . ' - ' . date('D/m', strtotime($current_date));
        }

        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $url_api = $url . '/api/v1/stats/timeseries?site_id=' . $siteId . $dateTime .'&metrics=' . $metrics;
        
            $header['Authorization'] = "Bearer " . $apiKey;
            $responseView = APIService::post($url_api, 'GET',  null, $header);
            if ($responseView && isset($responseView['results'])) {
                $visitors = $responseView['results'];
               
            }

        }
        $arr = [];
        foreach ($visitors as $item) {
            $arr[] = [
                $metrics => $item[$metrics] ? $item[$metrics] : 0,
                'date' => $item['date']
            ];
        }
        $chartVi = [
            'type' => 'line',
            'label' => 'date',
            'column' => [
                $metrics,
            ]
        ];
        $chartVisitors = [];
        if ($arr) {
            $chartVisitors = ReportServices::getChartData($arr, $chartVi);
        }
        $chartVisitors['colors'] = [$request->get('color')];
        $chartVisitors['title'] = [ucwords($metrics)];

        $arr = [];

        $arr[] = [
            'title' => $request->get('title'),
            'time' => $time,
            'type' => 'metric',
            'chart' => $chartVisitors,
        ];
        // dd($arr);
        return $this->json(['data' => $arr]);
    }

    /**
     * @Route("/add-arr-chart-pro", methods={"POST", "GET"})
     */
    public function addArrChartPro(Request $request): Response
    {
        $plasibleSet = null;
        $checkTableExistQuery = "SHOW TABLES LIKE 'corepulse_plausible'";
        $checkTableExistResult = Db::get()->fetchFirstColumn($checkTableExistQuery);
        if ($checkTableExistResult) {
            $plasibleSet = Db::get()->fetchAssociative('SELECT * FROM `corepulse_plausible`', ['id' => 1]); 
        }

        $devices = [];
        $property = $request->get('property') ? $request->get('property') : 'event:page';

        $metrics = $request->get('metrics') ? $request->get('metrics') : 'visitors';

        $current_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));
        
        $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;

        $time = $request->get('time');
        if ($time && $time == 'Today') {
            $dateTime = '&period=day';
        }

        if ($time && $time == '7 day') {
            $dateTime = '&period=7d';
        }

        if ($time && $time == '10 day') {
            $start_date = date('Y-m-d', strtotime('-10 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '30 day') {
            $start_date = date('Y-m-d', strtotime('-30 days', strtotime($current_date)));
            $dateTime = '&period=custom&date=' . $start_date . ',' . $current_date;
        }

        if ($time && $time == '6 month') {
            $dateTime = '&period=6mo';
        }

        if ($time && $time == '12 month') {
            $dateTime = '&period=12mo';
        }

        if ($request->get('current_date') || $request->get('start_date')) {
            $dateTime = '&period=custom&date=' . $request->get('start_date') . ',' . $request->get('current_date'); 
            $time = date('D/m', strtotime($request->get('start_date'))) . ' - ' . date('D/m', strtotime($request->get('current_date')));
        }

        if ($plasibleSet) {
            $url = $plasibleSet['domain'];
            $siteId = $plasibleSet['siteId'];
            $apiKey = $plasibleSet['apiKey'];

            $header['Authorization'] = "Bearer " . $apiKey;
            $url_api_devices = $url . '/api/v1/stats/breakdown?site_id=' . $siteId . $dateTime . '&property=' . $property . '&metrics=' . $metrics . '&limit=5';
            $responseDevices = APIService::post($url_api_devices, 'GET',  null, $header);
            if ($responseDevices && isset($responseDevices['results'])) {
                $devices = $responseDevices['results'];
            }

        }
        
        $chartDevi = [
            'type' => 'bar',
            'label' => explode(':', $property)[1],
            'column' => [
                $metrics,
            ]
        ];
        $chartDevices = [];
        if ($devices) {
            $chartDevices = ReportServices::getChartData($devices, $chartDevi);
        }
        $chartDevices['colors'] = [$request->get('color')];
        $chartDevices['title'] = [ucwords(explode(':', $property)[1])];
        $arr = [];

        $arr[] = [
            'title' => $request->get('title'),
            'time' => $time,
            'type' => 'pro',
            'chart' => $chartDevices,
        ];
        return $this->json(['data' => $arr]);
    }


    // api create table plausible
    protected ?Schema $schema = null;
    protected BufferedOutput $output;


    /**
     * @Route("/create-table-plausible", methods={"POST", "GET"})
     */
    public function createTable(Request $request, Connection $db): Response
    {
        $this->output = new BufferedOutput(Output::VERBOSITY_NORMAL, true);
        $response = '';
        $tablesToInstall = ['corepulse_plausible' => 'CREATE TABLE `corepulse_plausible` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `siteId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `apiKey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `username` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
            `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'];
        foreach ($tablesToInstall as $name => $statement) {
            if ($this->getSchema($db)->hasTable($name)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping table "%s" as it already exists',
                    $name
                ));

                $response = "Table " . $name. " already exists";
                continue;
            }

            $db->executeQuery($statement);
            $response = "Tables " . $name. " created successfully";
        }
        return new Response($response, Response::HTTP_OK);
    }

    protected function getSchema($db): Schema
    {
        return $this->schema ??= $db->createSchemaManager()->introspectSchema();
    }

        /**
     * @Route("/add-column-table-plausible", methods={"POST", "GET"})
     */
    public function addColumn(Request $request, Connection $db): Response
    {

        Db::get()->query("ALTER TABLE corepulse_plausible ADD COLUMN linkPublic varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");

        return new Response('Oke', Response::HTTP_OK);
    }
}