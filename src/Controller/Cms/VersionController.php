<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject;
use Knp\Component\Pager\PaginatorInterface;
use CorepulseBundle\Security\Hasher\CorepulseUserPasswordHasher;
use Symfony\Component\HttpFoundation\JsonResponse;

class VersionController extends BaseController
{

    /**
     *
     * @Route("/version/listing", name="version_listing", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listing(Request $request, PaginatorInterface $paginator)
    {
        $currentObject = DataObject::getById(1193);
        $versions = $currentObject->getVersions();
        $previousVersion = $versions[count($versions) - 2];
        $previousObject = $previousVersion->getData();
        dd($previousObject);


        return $this->renderWithInertia('Pages/User/Listing', ['data' => $data, 'totalItems' => $dataLength]);
    }
}
