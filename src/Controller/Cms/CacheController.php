<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use CorepulseCacheBundle\Cache;

/**
 * @Route("/cache")
 */
class CacheController extends BaseController
{

    /**
     * @Route("/clear", name="cache_clear", methods={"POST"}, options={"expose"=true}))
     */
    public function clearAction(Request $request)
    {
        $cache = Cache::clearAll();

        return $cache;
    }

}