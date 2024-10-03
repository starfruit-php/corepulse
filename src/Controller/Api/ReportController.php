<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Services\Helper\ArrayHelper;
use CorepulseBundle\Services\ReportServices;
use Pimcore\Bundle\CustomReportsBundle\Tool;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/report")
 */
class ReportController extends BaseController
{
    /**
     * @Route("/listing", name="corepulse_api_report_listing", methods={"GET","POST"}))
     */
    public function listing()
    {
        try {
            $data = [];

            $list = new Tool\Config\Listing();
            $items = $list->getDao()->loadList();

            foreach ($items as $report) {
                if ($report->getDataSourceConfig()) {
                    $data[] = ReportServices::getReport($report);
                }
            }

            if ($data && $search = $this->request->get("search")) {
                $dataOld = $data;

                $data = ArrayHelper::filterData($data, 'id', $search);

                $data = array_merge($data, ArrayHelper::filterData($dataOld, 'niceName', $search));

                if(!empty($data)) $data = array_values($data);
            }

            return $this->sendResponse($data);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), 500);
        }
    }

    /**
     * @Route("/detail", name="corepulse_api_report_detail", methods={"GET","POST"}))
     */
    public function detail()
    {
        try {
            $conditions = $this->getPaginationConditions($this->request, []);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'id' => 'required',
            ]);
            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $id = $this->request->get('id');
            $report = Tool\Config::getByName( $id);

            $data = [];
            $fields = [];
            $reportJson = [];
            $chartData = [];
            $chart = [];
            $orderKey = $this->request->get('order_by');
            $order = $this->request->get('order');

            if ($report) {
                $reportJson = [
                    'name' => $report->getName()
                ];

                $data = ReportServices::getSql($report->getDataSourceConfig());

                if ($this->request->isMethod('POST')) {
                    $search = $this->request->get("search");
                    $search = json_decode($search, true);

                    if ($search) {
                        $search = array_filter($search, function ($value) {
                            return !($value === "" || $value === null);
                        });

                        if (count($search)) {
                            foreach ($search as $key => $value) {
                                $data = ArrayHelper::filterData($data, $key, $value);
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
                        $data = ArrayHelper::sortArrayByField($data, $orderKey, $order);
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

            $result = [
                'listing' => $data,
                'fields' => $fields,
                'totalItems' => count($data),
                'reportJson' => $reportJson,
                'chart' => $chart,
                'chartData' => $chartData,
                'limit' => $limit,
            ];

            return $this->sendResponse($result);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), 500);
        }
    }
}
