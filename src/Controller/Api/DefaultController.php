<?php

namespace CorepulseBundle\Controller\Api;

use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Bundle\AdminBundle\Controller\Admin\LoginController;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends FrontendController
{

    protected $params;

    private array $objectData = [];

    private array $metaData = [];

    private array $classFieldDefinitions = [];

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function defaultAction(Request $request): Response
    {
        return $this->render('default/default.html.twig');
    }

    /**
     * Forwards the request to admin login
     */
    public function loginAction(): Response
    {

        return $this->forward(LoginController::class . '::loginCheckAction');
    }

    /**
     * @Route("/api/v1/chapters", methods={"GET"})
     */
    public function chaptersAction(Request $request, PaginatorInterface $paginator, ContainerBuilder $container)
    {
        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'corepulse');
        // $objectId = $request->query->getInt('id');
        // dd($containerConfig);
        $objectId = 6;

        $objectFromDatabase = DataObject\Concrete::getById($objectId);

        $objectFromDatabase = clone $objectFromDatabase;

        // set the latest available version for editmode
        $draftVersion = null;
        $object = $this->getLatestVersion($objectFromDatabase, $draftVersion);

        $objectFromVersion = $object !== $objectFromDatabase;

        $objectData = [];

        if ($draftVersion && $objectFromDatabase->getModificationDate() < $draftVersion->getDate()) {
            $objectData['draft'] = [
                'id' => $draftVersion->getId(),
                'modificationDate' => $draftVersion->getDate(),
                'isAutoSave' => $draftVersion->isAutoSave(),
            ];
        }

        try {
            $this->getDataForObject($object, $objectFromVersion);
        } catch (\Throwable $e) {
            $object = $objectFromDatabase;
            $this->getDataForObject($object, false);
        }

        $objectData['data'] = $this->objectData;
        $objectData['metaData'] = $this->metaData;
        $layout = DataObject\Service::getSuperLayoutDefinition($object);
        $objectData['layout'] = $layout;
        // $token = $this->get('security.token_storage')->getToken();
        // dd($token);
        // dd($service->generateClassDefinitionJson($a->getClass()));
        // dd(json_decode(ClassDefinitionService::generateClassDefinitionJson($a->getClass())));

        return $this->json($objectData);
    }

    protected function getLatestVersion(DataObject\Concrete $object,  ? DataObject\Concrete &$draftVersion = null) : DataObject\Concrete
    {
        $latestVersion = $object->getLatestVersion();
        if ($latestVersion) {
            $latestObj = $latestVersion->loadData();
            if ($latestObj instanceof DataObject\Concrete) {
                $draftVersion = $latestVersion;

                return $latestObj;
            }
        }

        return $object;
    }

    private function getDataForObject(DataObject\Concrete $object, bool $objectFromVersion = false): void
    {
        foreach ($object->getClass()->getFieldDefinitions(['object' => $object]) as $key => $def) {
            $this->getDataForField($object, $key, $def, $objectFromVersion);
        }
    }

    /**
     * Gets recursively attribute data from parent and fills objectData and metaData
     */
    private function getDataForField(DataObject\Concrete $object, string $key, DataObject\ClassDefinition\Data $fielddefinition, bool $objectFromVersion, int $level = 0): void
    {
        $parent = DataObject\Service::hasInheritableParentObject($object);
        $getter = 'get' . ucfirst($key);

        // Editmode optimization for lazy loaded relations (note that this is just for AbstractRelations, not for all
        // LazyLoadingSupportInterface types. It tries to optimize fetching the data needed for the editmode without
        // loading the entire target element.
        // ReverseObjectRelation should go in there anyway (regardless if it a version or not),
        // so that the values can be loaded.
        if (
            (!$objectFromVersion && $fielddefinition instanceof AbstractRelations)
            || $fielddefinition instanceof ReverseObjectRelation
        ) {
            $refId = null;

            if ($fielddefinition instanceof ReverseObjectRelation) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = DataObject\ClassDefinition::getByName($fielddefinition->getOwnerClassName());
                if ($refClass) {
                    $refId = $refClass->getId();
                }
            } else {
                $refKey = $key;
            }

            $relations = $object->getRelationData($refKey, !$fielddefinition instanceof ReverseObjectRelation, $refId);

            if ($fielddefinition->supportsInheritance() && empty($relations) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $data = [];

                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\ManyToOneRelation) {
                    if (isset($relations[0])) {
                        $data = $relations[0];
                        $data['published'] = (bool) $data['published'];
                    } else {
                        $data = null;
                    }
                } elseif (
                    ($fielddefinition instanceof DataObject\ClassDefinition\Data\OptimizedAdminLoadingInterface && $fielddefinition->isOptimizedAdminLoading())
                    || ($fielddefinition instanceof ManyToManyObjectRelation && !$fielddefinition->getVisibleFields() && !$fielddefinition instanceof DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation)
                ) {
                    foreach ($relations as $rkey => $rel) {
                        $index = $rkey + 1;
                        $rel['fullpath'] = $rel['path'];
                        $rel['classname'] = $rel['subtype'];
                        $rel['rowId'] = $rel['id'] . AbstractRelations::RELATION_ID_SEPARATOR . $index . AbstractRelations::RELATION_ID_SEPARATOR . $rel['type'];
                        $rel['published'] = (bool) $rel['published'];
                        $data[] = $rel;
                    }
                } else {
                    $fieldData = $object->$getter();
                    $data = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
                }
                $this->objectData[$key] = $data;
                $this->metaData[$key]['objectid'] = $object->getId();
                $this->metaData[$key]['inherited'] = $level != 0;
            }
        } else {
            $fieldData = $object->$getter();
            $isInheritedValue = false;

            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\CalculatedValue) {
                $fieldData = new DataObject\Data\CalculatedValue($fielddefinition->getName());
                $fieldData->setContextualData('object', null, null, null, null, null, $fielddefinition);
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            } else {
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            }

            // following some exceptions for special data types (localizedfields, objectbricks)
            if ($value && ($fieldData instanceof DataObject\Localizedfield || $fieldData instanceof DataObject\Classificationstore)) {
                // make sure that the localized field participates in the inheritance detection process
                $isInheritedValue = $value['inherited'];
            }
            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Objectbricks && is_array($value)) {
                // make sure that the objectbricks participate in the inheritance detection process
                foreach ($value as $singleBrickData) {
                    if (!empty($singleBrickData['inherited'])) {
                        $isInheritedValue = true;
                    }
                }
            }

            if ($fielddefinition->isEmpty($fieldData) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
                // exception for classification store. if there are no items then it is empty by definition.
                // consequence is that we have to preserve the metadata information
                // see https://github.com/pimcore/pimcore/issues/9329
                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Classificationstore && $level == 0) {
                    $this->objectData[$key]['metaData'] = $value['metaData'] ?? [];
                    $this->objectData[$key]['inherited'] = true;
                }
            } else {
                $isInheritedValue = $isInheritedValue || ($level != 0);
                $this->metaData[$key]['objectid'] = $object->getId();

                $this->objectData[$key] = $value;
                $this->metaData[$key]['inherited'] = $isInheritedValue;

                if ($isInheritedValue && !$fielddefinition->isEmpty($fieldData) && !$fielddefinition->supportsInheritance()) {
                    $this->objectData[$key] = null;
                    $this->metaData[$key]['inherited'] = false;
                    $this->metaData[$key]['hasParentValue'] = true;
                }
            }
        }
    }

    public function test()
    {
        $objectId = 6;

        $objectFromDatabase = DataObject\Concrete::getById($objectId);

        $objectFromDatabase = clone $objectFromDatabase;

        // set the latest available version for editmode
        $draftVersion = null;
        $object = $this->getLatestVersion($objectFromDatabase, $draftVersion);

        $objectFromVersion = $object !== $objectFromDatabase;

        $objectData = [];

        if ($draftVersion && $objectFromDatabase->getModificationDate() < $draftVersion->getDate()) {
            $objectData['draft'] = [
                'id' => $draftVersion->getId(),
                'modificationDate' => $draftVersion->getDate(),
                'isAutoSave' => $draftVersion->isAutoSave(),
            ];
        }

        try {
            $this->getDataForObject($object, $objectFromVersion);
        } catch (\Throwable $e) {
            $object = $objectFromDatabase;
            $this->getDataForObject($object, false);
        }

        $objectData['data'] = $this->objectData;
        $objectData['metaData'] = $this->metaData;
        $layout = DataObject\Service::getSuperLayoutDefinition($object);
        $objectData['layout'] = $layout;
    }
}
