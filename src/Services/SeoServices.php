<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Services\APIService;
use Pimcore\Db;
use Starfruit\BuilderBundle\Tool\LanguageTool;
use Starfruit\BuilderBundle\Model\Option;
use CorepulseBundle\Model\Indexing;
use Starfruit\BuilderBundle\Sitemap\Setting;

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
        // $languageName = 'English';

        $keyword = str_replace('"', "", $keyword);

        $content = [];
        if ($type == 'sematic') {
            $content = [
                [
                    'role' => 'user',
                    'content' => "I have a blog post about '$keyword' Can you give me 10 keywords and semantic entities in  $languageName that
                    I can include in the content to make it better and more relevant so that Google understands my content faster and more accurately?
                    returns me the results in list style html",
                ]
            ];
        } else if ($type == 'outline') {
            $content = [
                [
                    'role' => 'user',
                    'content' => "Give the keyword '$keyword' Draw on EEAT principles or guidelines to analyze and compare them for depth and detail of content, demonstration of expertise and credibility, and how well they meet user intent. I want you to create an outline for the keyword content I have provided. Should the outline be better than the competitor's or at least as good as theirs? The returned language is $languageName and  format in a visual list style by html level only taking the body content h1 h2 h3 ol li.",
                ]
            ];
        }

        return $content;
    }

    static public function saveData($seo, $params)
    {
        $keyUpdate = ['keyword', 'title', 'slug', 'description', 'image', 'canonicalUrl', 'redirectLink',
        'nofollow', 'indexing', 'redirectType', 'destinationUrl', 'schemaBlock', 'image', 'imageAsset'];

        foreach ($params as $key => $value) {
            $function = 'set' . ucfirst($key);

            if (in_array($key, $keyUpdate) && method_exists($seo, $function)) {
                if ($key == 'nofollow' || $key == 'indexing' || $key == 'redirectLink') {
                   $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                $seo->$function($value);
            }
        }
        $seo->save();

        return $seo;
    }

    static public function saveMetaData($seo, $params)
    {
        $ogMeta = [];
        $twitterMeta = [];
        $customMeta = [];

        if (isset($params['ogMeta'])) {
            $ogMeta = $params['ogMeta'];
            $ogMeta = self::revertMetaData($ogMeta);
        }

        if (isset($params['twitterMeta'])) {
            $twitterMeta = $params['twitterMeta'];
            $twitterMeta = self::revertMetaData($twitterMeta);
        }

        if (isset($params['customMeta'])) {
            $customMeta = $params['customMeta'];
            $twitterMeta = self::revertMetaData($twitterMeta);
        }

        $seo->setMetaDatas($ogMeta, $twitterMeta, $customMeta);
        $seo->save();

        return $seo;
    }

    static public function revertMetaData($array)
    {
        $data = array_reduce($array, function ($carry, $item) {
            return array_merge($carry, $item);
        }, []);

        return $data;
    }

    static public function getSetting()
    {
        $setting = Option::getByName('seo_setting');
        if (!$setting) {
            $data = [
                'type' => null,
                'defaultValue' => null,
                'customValue' => null,
            ];

            $setting = new Option();
            $setting->setName('seo_setting');
            $setting->setContent(json_encode($data));
            $setting->save();
        }

        return $setting;
    }

    static public function saveSetting($setting, $params = [])
    {
        if (!empty($params)) {
            $data = [
                'type' => null,
                'defaultValue' => null,
                'customValue' => null,
            ];

            foreach ($data as $key => $value) {
                if (isset($params[$key])) {
                    $data[$key] = $params[$key];
                }
            }

            $setting->setContent(json_encode($data));
            $setting->save();
        }

        return $setting;
    }
}
