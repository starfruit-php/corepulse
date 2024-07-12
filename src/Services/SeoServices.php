<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Services\APIService;
use Pimcore\Db;
use Starfruit\BuilderBundle\Tool\LanguageTool;
use Starfruit\BuilderBundle\Model\Option;
use CorepulseBundle\Model\Indexing;

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

    static public function setIndexSetting($file, $type = 'file')
    {
        $upload = false;
        $data = [
            'success' => false,
            'message' => null,
        ];

        if (!is_uploaded_file($file)) {
            $jsonFile = file_get_contents($file);
            $filePath = PIMCORE_PROJECT_ROOT . '/public/' . $file->getClientOriginalName();
        } else {
            if (!file_exists($file)) {
                $data['message'] = sprintf('File "%s" does not exist', $file);
                return $data;
            }

            $jsonFile = file_get_contents($file);
            $filePath = PIMCORE_PROJECT_ROOT . '/public/' . $file->getClientOriginalName();
            $upload = true;
        }

        $configFile = json_decode($jsonFile, true);
        if (!$configFile) {
            $data['message'] = 'Invalid JSON for auth config';
            return $data;
        }

        try {
            $client = new \Google\Client();
            $client->setAuthConfig($configFile);
            $client->addScope(\Google\Service\Indexing::INDEXING);
            $service = new \Google\Service\Indexing($client);

            $setting = Option::getByName('indexing-setting') ?: new Option();
            $setting->setName('indexing-setting');
            $setting->setContent(json_encode(['type' => $type, 'data' => $filePath]));
            $setting->save();

            $data = [
                'success' => true,
                'message' => 'Indexing setting success.',
            ];

            if ($upload) {
                if (!move_uploaded_file($file, $filePath)) {
                    $data = [
                        'success' => false,
                        'message' => 'Indexing setting error.',
                    ];
                }
            }
        } catch (\Throwable $th) {
            $data['message'] = $th->getMessage();
        }

        return $data;
    }

    static public function getIndexSetting()
    {
        $data = null;
        $setting = Option::getByName('indexing-setting');
        if ($setting) {
            $content = json_decode($setting->getContent(), true);
            $data = $content['data'];
            if ($content['type'] == 'json') {
                $data = json_decode($data, true);
            }
        }

        return $data;
    }

    static public function connectGoogleIndex()
    {
        $client = new \Google\Client();
        $client->setAuthConfig(self::getIndexSetting());

        $client->addScope(\Google\Service\Indexing::INDEXING);

        $httpClient = $client->authorize();
        
        return $httpClient;
    }

    static public function submitIndex($url, $type = 'create')
    {
        $data = [];

        $domain = Option::getMainDomain();
        $domain = 'https://cbs.starfruit.com.vn';

        $httpClient = self::connectGoogleIndex();

        $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

        $typeUrl = 'URL_UPDATED';
        if ($type == 'delete') {
            $typeUrl = "URL_DELETED";
        }

        $content = json_encode([
            "url" => $domain . $url,
            "type" => $typeUrl
        ]);

        $response = $httpClient->post($endpoint, [ 'body' => $content ]);
       
        $data = self::getResponData($response, $content);
        // $data = self::getResponData($httpClient->get('https://indexing.googleapis.com/v3/urlNotifications/metadata?url=' . urlencode($domain . $url)), $content);
        
        if (isset($data['status'])) {
            $data["url"] = $domain . $url;
            $data["type"] = $data['status'];
        } else {
            $data['success'] = true;
            $data['message'] = 'Submit indexing success';
            $data["type"] = $type;
        }

        self::saveIndex($data);

        return $data;
    }

    static public function getResponData($response, $content)
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $bodyData = json_decode($body, true);

        if (isset($bodyData['error'])) {
            $data = $bodyData['error'];
        } else {
            $metadata = isset($bodyData['urlNotificationMetadata']) ? $bodyData['urlNotificationMetadata'] : [];

            $key = 'latestUpdate';
    
            if (isset($metadata['latestUpdate']) && isset($metadata['latestRemove'])) {
                $latestUpdateTime = new \DateTime($metadata['latestUpdate']['notifyTime']);
                $latestRemoveTime = new \DateTime($metadata['latestRemove']['notifyTime']);
    
                if ($latestUpdateTime < $latestRemoveTime ) {
                    $key = 'latestRemove';
                }
            } else if (isset($metadata['latestRemove'])) {
                $key = 'latestRemove';
            }
    
            $data = self::responArray($metadata[$key]);
        }
       
        $data['response'] = $statusCode;
        
        return $data;
    }

    static public function responArray($item)
    {
        $localTimezone = new \DateTimeZone(date_default_timezone_get());

        $notifyDateTime = new \DateTime($item["notifyTime"], new \DateTimeZone('UTC'));
        $notifyDateTime->setTimezone($localTimezone);

        $time = $notifyDateTime->format('Y-m-d H:i:s');

        $data = [
            'url' => $item['url'],
            'type' => $item['type'],
            'time' => $time,
        ];

        return $data;
    }

    static public function saveIndex($params)
    {
        $object = isset($params['url']) ? Indexing::getByUrl($params['url']) : '';
        if (!$object) {
            $object = new Indexing();
        }

        foreach ($params as $key => $value) {
            $function = 'set' . ucfirst($key);
    
            if (method_exists($object, $function)) {
                $object->$function($value);
            }
        }

        $object->save();

        return $object;
    }
}
