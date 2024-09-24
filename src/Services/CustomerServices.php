<?php

namespace CorepulseBundle\Services;

use Pimcore\Db;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject;
use CorepulseBundle\Services\Helper\ObjectHelper;

class CustomerServices
{
    static public function getData($customer)
    {
        $data = [];
        $params = ["gender", "active", "email", "phone", "fullName", "username", "firstname", "lastname", "city", "street", "zip", "countryCode", "customerLanguage"];
        foreach ($params as $key => $value) {
            $data[$value] = ObjectHelper::getMethodData($customer, $value);
        }

        return $data;
    }
}
