<?php

namespace CorepulseBundle\Services;

use Pimcore\Db;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject;

class EcommerceService
{
    public static function create($params)
    {
        $order = new OnlineShopOrder();

        foreach ($params as $key => $value) {
            $setValue = 'set' . ucfirst($key);
            if (method_exists($order, $setValue)) {
                $order->$setValue($value);
            }
        }
        $order->setUpdateAt(date('Y-m-d H:i:s'));
        $order->setCreateAt(date('Y-m-d H:i:s'));
        $order->save();

        return $order;
    }

    public static function edit($params, $order)
    {
        foreach ($params as $key => $value) {
            $setValue = 'set' . ucfirst($key);
            if (method_exists($order, $setValue)) {
                $order->$setValue($value);
            }
        }
        $order->setUpdateAt(date('Y-m-d H:i:s'));
        $order->save();

        return $order;
    }

    public static function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $i) {
                $order = OnlineShopOrder::getById($i);
                $order->delete();
            }
        } else {
            $order = OnlineShopOrder::getById($id);
            $order->delete();
        }

        return true;
    }
}
