<?php

namespace CorepulseBundle\Services;

use Google\Service\AIPlatformNotebooks\Status;
use Pimcore\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ReportServices
{
    static public function getReport($report)
    {
        $data = [];
        if ($report->getDataSourceConfig() !== null) {
            $data = [
                'id' => htmlspecialchars($report->getName()),
                'niceName' => htmlspecialchars($report->getNiceName()),
                'iconClass' => htmlspecialchars($report->getIconClass()),
                'group' => htmlspecialchars($report->getGroup()),
                'groupIconClass' => htmlspecialchars($report->getGroupIconClass()),
                'menuShortcut' => $report->getMenuShortcut(),
                'reportClass' => htmlspecialchars($report->getReportClass()),
            ];
        }

        return $data;
    }

    static public function getSql($config, $where = '')
    {
        $data = [];
        $sql = '';

        $type = $config->type;
        if ($type == 'sql') {
            $sql = 'SELECT ' . $config->sql . ' FROM ' . $config->from;

            if ($where && $config->where) {
                $sql .= ' WHERE ' . $config->where . ' AND ' . $where;
            } elseif ($where) {
                $sql .= ' WHERE ' . $where;
            } elseif ($config->where) {
                $sql .= ' WHERE ' . $config->where;
            }

            // if ($orderkey && $order) {
            // dd($config->sql, $orderkey, $order);
            // $sql .= ' ORDER BY ' . $orderkey . ' ' . $order;
            // $sql .= ' ORDER BY date asc';
            // } else {
            if ($config->groupby) {
                $sql .= ' GROUP BY ' . $config->groupby;
            }
            // }
        }

        $db = Db::get();

        $data = $db->fetchAllAssociative($sql);

        if ($data) {
            foreach ($data as $key => $value) {
                $data[$key] = array_merge($value, ["unSelecte" => true]);
            }
        }

        return $data;
    }

    static public function getColumn($column)
    {
        $data = [];
        foreach ($column as $key => $value) {
            $data[] = [
                'key' => $value['name'],
                'tooltip' => '',
                'title' => $value['name'],
                'removable' => true,
                'searchType' => 'Input',
            ];
        }

        return $data;
    }

    static public function getChart($chart)
    {
        $data = [];
        if ($chart->getChartType() == 'bar' || $chart->getChartType() == 'line') {
            $data = [
                'type' => $chart->getChartType(),
                'label' => $chart->getXAxis(),
                'column' => $chart->getYAxis(),
            ];
        } elseif ($chart->getChartType() == 'pie') {
            $data = [
                'type' => $chart->getChartType(),
                'label' => $chart->getPieLabelColumn(),
                'column' => $chart->getPieColumn(),
            ];
        }

        return $data;
    }

    static public function getChartData($listing, $chart, $title = '')
    {
        $chartData = [];
        $labels = [];
        $datas = [];
        $colors = [];

        foreach ($listing as $key => $value) {
            if ($chart['label']) {
                $labels[] = $value[$chart['label']];
            }
        }

        if ($chart['type'] == 'pie') {
            foreach ($listing as $key => $value) {
                $datas[] = $value[$chart['column']];
            }

            $chartData['series'] =  $datas;
        } else {
            foreach ($chart['column'] as $k => $i) {
                // Tạo màu ngẫu nhiên
                $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                // Kiểm tra xem màu đã được sử dụng chưa, nếu đã sử dụng, tạo lại
                while (in_array($color, $colors)) {
                    $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                }

                // Thêm màu vào mảng
                $colors[] = $color;

                $data = [];
                foreach ($listing as $key => $value) {
                    $data[] = $value[$i];
                }

                $datas[] = [
                    'name' => $i,
                    // 'backgroundColor' => $color,
                    'data' => $data
                ];
            }

            $chartData['series'] =  $datas;
        }

        $chartData['categories'] = $labels;
        $chartData['colors'] = $colors;
        $chartData['text'] = $chart['label'];
        $chartData['title'] = $title;
        $chartData['labels'] = $labels;

        return $chartData;
    }

    public static function getFieldExport($column)
    {
        $data = [];
        foreach ($column as $key => $value) {
            $data[] = $value['name'];
        }

        return $data;
    }

    //xuất excel
    static public function getExcel($data)
    {
        ob_start();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Duyệt qua dữ liệu và ghi vào các ô tương ứng
        foreach ($data as $rowIndex => $row) {
            $i = 0;

            foreach ($row as $colIndex => $value) {
                $i++;
                $sheet->setCellValueByColumnAndRow($i, (int)$rowIndex + 1, $value);
            }
        }

        return $spreadsheet;
    }

    //xử lý filter
    public static function filterData($data, $key, $value)
    {
        if (count($data)) {
            $result = array_filter($data, function ($item) use ($key, $value) {
                return self::checkValue($item[$key], $value);
            });

            return $result;
        }
    }

    public static function checkValue($string, $value)
    {
        $lowercaseString = self::getPretty(strtolower($string));
        $lowercaseValue = self::getPretty(strtolower(ltrim($value)));

        return stripos($lowercaseString, $lowercaseValue) !== false;
    }

    public static function sortArrayByField($array, $field, $order = 'asc') {
        usort($array, function($a, $b) use ($field, $order) {
            $valueA = $a[$field];
            $valueB = $b[$field];

            if ($valueA == $valueB) {
                return 0;
            }

            if ($order == 'asc') {
                return ($valueA < $valueB) ? -1 : 1;
            } else {
                return ($valueA > $valueB) ? -1 : 1;
            }
        });

        return $array; // Trả về mảng đã được sắp xếp
    }

    public static function getPretty($text)
    {
        // to ASCII
        $text = trim(transliterator_transliterate('Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove', $text));

        $search = ['?', '\'', '"', '/', '-', '+', '.', ',', ';', '(', ')', ' ', '&', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', 'É', 'é', 'È', 'è', 'Ê', 'ê', 'E', 'e', 'Ë', 'ë',
                         'À', 'à', 'Á', 'á', 'Å', 'å', 'a', 'Â', 'â', 'Ã', 'ã', 'ª', 'Æ', 'æ', 'C', 'c', 'Ç', 'ç', 'C', 'c', 'Í', 'í', 'Ì', 'ì', 'Î', 'î', 'Ï', 'ï',
                         'Ó', 'ó', 'Ò', 'ò', 'Ô', 'ô', 'º', 'Õ', 'õ', 'Œ', 'O', 'o', 'Ø', 'ø', 'Ú', 'ú', 'Ù', 'ù', 'Û', 'û', 'U', 'u', 'U', 'u', 'Š', 'š', 'S', 's',
                         'Ž', 'ž', 'Z', 'z', 'Z', 'z', 'L', 'l', 'N', 'n', 'Ñ', 'ñ', '¡', '¿',  'Ÿ', 'ÿ', '_', ':' ];
        $replace = ['', '', '', '', '-', '', '', '-', '-', '', '', '-', '', 'ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e',
                         'A', 'a', 'A', 'a', 'A', 'a', 'a', 'A', 'a', 'A', 'a', 'a', 'AE', 'ae', 'C', 'c', 'C', 'c', 'C', 'c', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i',
                         'O', 'o', 'O', 'o', 'O', 'o', 'o', 'O', 'o', 'OE', 'O', 'o', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'S', 's', 'S', 's',
                         'Z', 'z', 'Z', 'z', 'Z', 'z', 'L', 'l', 'N', 'n', 'N', 'n', '', '', 'Y', 'y', '-', '-' ];

        $value = urlencode(str_replace($search, $replace, $text));

        return $value;
    }
}
