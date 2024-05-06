<?php

namespace CorepulseBundle\Services;

use Google\Service\AIPlatformNotebooks\Status;
use Pimcore\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;

class APIService
{
    //========================> SUPPORT METHOD <==============================
    static public function post($url, $method, $data = null, $header = null)
    {
        try {
            $response = self::process($url,$method,$data,$header);
            $response = json_decode($response, true);
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    static public function process($url, $method, $params = null,$header = null)
    {
        try {
            $client = new Client([
                'verify' => false
            ]);
            $response = $client->request($method, $url, [
                'headers' => $header,
                'json' => $params
            ]);
            
            return (string)$response->getBody();
        } catch (\Exception $e) {
            $response=['error'=>'errors.social.unauthenticated'];
            return $response = json_encode($response, true);
        }
    }
}