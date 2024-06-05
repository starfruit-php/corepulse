<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use CorepulseBundle\Model\Notification;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @Route("/notification")
 */
class NotificationController extends BaseController
{
    /**
     * @Route("/", name="corepulse_notification_index", options={"expose"=true}))
     */
    public function index(Request $request)
    {
        return $this->renderWithInertia('Pages/Notification/Layout', [

        ]);
    }

    /**
     * @Route("/listing", name="corepulse_notification_listing", options={"expose"=true}))
     */
    public function listing(Request $request, PaginatorInterface $paginator)
    {
        $limit = $request->get("limit", 10);
        $page = $request->get("page", 1);
        $order = $request->get('order', 'desc');
        $orderKey = $request->get('orderKey', 'createAt');
        $search = $request->get('search');

        $listing = new Notification\Listing();
        $listing->setOrderKey($orderKey);
        $listing->setOrder($order);

        $listing = $paginator->paginate(
            $listing,
            $page,
            $limit
        );

        $datas = [];
        foreach ($listing as $value) {
            $datas[] = $value->getDataJson();
        }

        return new JsonResponse([
            'data' => $datas,
            'limit' => $limit,
            'page' => $page,
            'order' => $order,
            'orderKey' => $orderKey,
            'search' => $search,
        ]);
    }

    /**
     * @Route("/detail", name="corepulse_notification_detail", options={"expose"=true}))
     */
    public function detail(Request $request)
    {
        $object = Notification::getById($request->get('id'));
        $object->getDataJson();


        dd($object);
    }

    /**
     * @Route("/rule/create", name="corepulse_notification_rule_create", options={"expose"=true}))
     */
    public function ruleCreate(Request $request)
    {
        $object = Notification::getById($request->get('id'));
        $object->getDataJson();


        dd($object);
    }
}
