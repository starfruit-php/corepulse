<?php

namespace CorepulseBundle\Controller\Cms;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Bundle\CustomReportsBundle\Tool;
use CorepulseBundle\Services\ReportServices;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/report")
 */
class ReportController extends BaseController
{
    /**
     * @Route("/", name="cms_vuetify_report", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function viewAction(Request $request)
    {
        $id = $request->get('id', null);
        $viewData = ['metaTitle' => 'Report'];

        return $this->renderWithInertia('Pages/Report/View',[
            'id' => $id
        ], $viewData);
    }

    /**
     * @Route("/listing", name="cms_vuetify_report_listing", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listingAction(Request $request)
    {
        $data = [];

        $list = new Tool\Config\Listing();
        $items = $list->getDao()->loadList();

        foreach ($items as $report) {
            if ($report->getDataSourceConfig()) {
                $data[] = ReportServices::getReport($report);
            }
        }

        if ($data) {
            if ($request->isMethod('POST')) {
                $search = $request->get("search");

                $search = json_decode($search, true);

                if ($search) {
                    $search = array_filter($search, function ($value) {
                        return !($value === "" || $value === null);
                    });

                    if (count($search)) {
                        $dataOld = $data;
                        foreach ($search as $key => $value) {
                            $data = ReportServices::filterData($data, $key, $value);

                            if($key == 'id') {
                                if (!count($data)) {
                                    $data = $dataOld;
                                } else {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/detail", name="cms_vuetify_report_detail", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function detailAction(Request $request)
    {
        $id = $request->get('id');
        $report = Tool\Config::getByName( $id);

        $data = [];
        $fields = [];
        $reportJson = [];
        $chartData = [];
        $chart = [];
        $orderKey = $request->get('orderKey');
        $order = $request->get('order');
        $limit = $request->get("limit", 25);
        $page = $request->get("page", 1);

        if ($report) {
            $reportJson = [
                'name' => $report->getName()
            ];

            $data = ReportServices::getSql($report->getDataSourceConfig());

            if ($request->isMethod('POST')) {
                $search = $request->get("search");
                $search = json_decode($search, true);

                if ($search) {
                    $search = array_filter($search, function ($value) {
                        return !($value === "" || $value === null);
                    });

                    if (count($search)) {
                        foreach ($search as $key => $value) {
                            $data = ReportServices::filterData($data, $key, $value);
                        }
                    }
                }
            }

            $fields = ReportServices::getColumn($report->getColumnConfiguration());

            if ($data) {
                if($limit == '-1') {
                    $limit = count($data);
                }

                if ($orderKey && $order) {
                    $data = ReportServices::sortArrayByField($data, $orderKey, $order);
                }

                $datas = array_chunk($data, $limit);

                $chart = ReportServices::getChart($report);

                if ($chart) {
                    // $chartData = ReportServices::getChartData($datas[(int)$page - 1], $chart);
                    $chartData = ReportServices::getChartData($data, $chart);
                }

                if(count($datas)) {
                    return new JsonResponse([
                        'listing' => $datas[(int)$page - 1],
                        'fields' => $fields,
                        'totalItems' => count($data),
                        'reportJson' => $reportJson,
                        'chart' => $chart,
                        'chartData' => $chartData,
                        'limit' => $limit,
                    ]);
                }
            }
        }

        return new JsonResponse([
            'listing' => $data,
            'fields' => $fields,
            'totalItems' => count($data),
            'reportJson' => $reportJson,
            'chart' => $chart,
            'chartData' => $chartData,
            'limit' => $limit,
        ]);
    }

    /**
     * @Route("/export", name="cms_vuetify_report_export", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function exportAction(Request $request)
    {
        $report = Tool\Config::getByName($request->get('name'));
        $response = new Response();
        $filterData = [];

        $data = $request->get('data');

        if (is_array($data) && $report) {
            $fields = ReportServices::getFieldExport($report->getColumnConfiguration());
            foreach ($data as $key => $value) {
                $data[$key] = json_decode($value);
            }
            foreach ($data as $key => $value) {
                $result = [];
                foreach ($fields as $k => $v) {
                    $result[$v] = isset($value[$v]) ? $value[$v] : '';
                }
                $filterData[] = $result;
            }

            array_unshift($filterData, $fields);

            $spreadsheet = ReportServices::getExcel($filterData);

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $report->getName() . '.xlsx"');

            // Lưu tệp Excel
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            return $response;
        }

        return $response;
    }
}
