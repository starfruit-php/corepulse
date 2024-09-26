<?php

namespace CorepulseBundle\EventListener\DataObject;

use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use CorepulseBundle\Services\ClassServices;

class ClassDefinitionListener
{
    public function postClassUpdate(ClassDefinitionEvent $event)
    {
        // try {/
            $classDefinition = $event->getClassDefinition();
            $params = ClassServices::examplesAction($classDefinition->getId());
            $update = ClassServices::updateTable($classDefinition->getId(), $params);
        // } catch (\Throwable $th) {
        // }
    }
}
