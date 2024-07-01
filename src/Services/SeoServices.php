<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Services\APIService;
use Pimcore\Db;
use Starfruit\BuilderBundle\Tool\LanguageTool;

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

        $result = [
            'success' => false,
            'data' => '',
        ];

        $response = APIService::post($url, 'POST', $params, $header);

        if ($response && isset($response['choices'])) {
            $data = $response['choices'][0]['message']['content'];

            $result = [
                'success' => true,
                'data' => $data,
            ];
        }

        return $result;
    }

    static public function choicesContent($keyword, $type, $language = null)
    {
        if (!$language) {
            $language = LanguageTool::getDefault();
        }
        $languageName = \Locale::getDisplayLanguage($language);

        $content = [];
        if ($type == 'sematic') {
            $content = [
                [
                    'role' => 'user',
                    'content' => 'I have a blog post about ' . $keyword . '. Can you give me 10 keywords and semantic entities in ' . $languageName . ' that
                    I can include in the content to make it better and more relevant so that Google understands my content faster and more accurately?
                    returns me the results in list style html',
                ]
            ];
        } else if ($type == 'outline') {
            $content = [
                [
                    'role' => 'user',
                    'content' => "Give the keyword '" . $keyword . "'. Draw on EEAT principles or guidelines to analyze and compare them for depth and detail of content, demonstration of expertise and credibility, and how well they meet user intent. I want you to create an outline for the keyword content I have provided. Should the outline be better than the competitor's or at least as good as theirs? The returned language is" .  $languageName . " and  format in a visual list style by html level only taking the body content h1 h2 h3 ol ul li.",
                ]
            ];
        }

        return $content;
    }
}
