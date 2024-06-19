<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Services\APIService;
use Pimcore\Db;

class SeoServices
{
    static public function checkApi($key)
    {
        $content = [
            [
                "role" => "user",
                "content" => "Say this is a test!"
            ]
        ];

        $connect = self::sendCompletions($content, $key);
        if ($connect && isset($connect['error'])) {
            return false;
        }

        $item = self::getApiKey();
        if (!$item) {
            Db::get()->insert(
                'vuetify_settings',
                [
                    'config' => $key,
                    'type' => 'setting-ai',
                ]
            );
        } else {
            Db::get()->update(
                'vuetify_settings',
                [
                    'config' => $key,
                ],
                [
                    'type' => 'setting-ai',
                ]
            );
        }

        return true;
    }

    static public function getApiKey()
    {
        $item = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "setting-ai"', []);

        if ($item && isset($item['config'])) {
            return $item['config'];
        }

        return null;
    }

    static public function sendCompletions($content, $key = null)
    {
        if (!$key) {
            $key = self::getApiKey();
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $key,
        ];
        $params = [
            "model" => "gpt-3.5-turbo",
            "messages" => $content,
            "temperature" => 0.7
        ];

        return APIService::post($url, 'POST', $params, $header);
    }
}
