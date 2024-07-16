<?php

namespace CorepulseBundle\EventListener\Indexing;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\DocumentEvent;
use CorepulseBundle\Services\SeoServices;
use Starfruit\BuilderBundle\Config\ObjectConfig;
use Starfruit\BuilderBundle\Tool\LanguageTool;

class SubmitListener
{
    private function isSaveVersion($event)
    {
        $args = $event->getArguments();
        $saveVersionOnly = isset($args['saveVersionOnly']) && $args['saveVersionOnly'];

        return $saveVersionOnly;
    }

    public function postObjectUpdate(DataObjectEvent $event)
    {
        if (!$this->isSaveVersion($event)) {
            $this->generateObject($event->getObject(), 'update');
        }
    }

    public function postObjectAdd(DataObjectEvent $event)
    {
        if (!$this->isSaveVersion($event)) {
            $this->generateObject($event->getObject(), 'create');
        }
    }

    public function postObjectDelete(DataObjectEvent $event)
    {
        $this->generateObject($event->getObject(), 'delete');
    }

    public function postDocumentAdd(DocumentEvent $event)
    {
        if (!$this->isSaveVersion($event)) {
            $this->generateDocument($event->getDocument(), 'create');
        }
    }

    public function postDocumentUpdate(DocumentEvent $event)
    {
        if (!$this->isSaveVersion($event)) {
            $this->generateDocument($event->getDocument(), 'update');
        }
    }

    public function postDocumentDelete(DocumentEvent $event)
    {
        $this->generateDocument($event->getDocument(), 'delete');
    }

    private function generateObject($object, $type)
    {
        $setting = SeoServices::getIndexSetting(true);
        if ($setting['data']) {
            $className = $object->getClassName();
            $action = array_filter($setting['classes'], function($item) use ($className) {
                return $item['name'] == $className;
            });

            if (isset($action) && $first = reset($action)) {
                if ($first['check']) {
                    $languages = LanguageTool::getList();
                    foreach ($languages as $language) {
                        $objectConfig = new ObjectConfig($object);
                        $url = $objectConfig->getSlug(['locale' => $language]);
                        SeoServices::submitIndex($url, $type);
                    }
                }
            }
        }
    }

    private function generateDocument($document, $type)
    {
        $setting = SeoServices::getIndexSetting(true);
        if ($setting['data']) {
            $id = $document->getId();
            $action = array_filter($setting['documents'], function($item) use ($id) {
                return $item['id'] == $id;
            });

            if (isset($action) && $first = reset($action)) {
                if ($first['generateSitemap']) {
                    $url = $document->getPrettyUrl();
                    if (!$url) {
                        $url = $document->getPath() . $document->getKey();
                    }
                    SeoServices::submitIndex($url, $type);
                }
            }
        }
    }
}
