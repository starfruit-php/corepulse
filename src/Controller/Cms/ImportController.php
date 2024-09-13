<?php

namespace CorepulseBundle\Controller\Cms;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Country;
use Pimcore\Model\DataObject\Tour;
use Pimcore\Model\DataObject\Scene;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject\Hotel;
use Pimcore\Model\DataObject;

use App\Controller\ApiController;

class ImportController extends BaseController
{
    /**
     * @Route("/import-csv", name="import_csv", methods={"POST"}, options={"expose"=true}))
     */
    public function importAction(Request $request)
    {
        $path = $request->get('path');
        $type = $request->get('type');

        if (!empty($path)) {
            if (substr($path, 0, 4) == "http") {
                $prefix = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['frontend_prefixes']['source'];
                if ($prefix) {
                    $path = substr($path, strlen($prefix)); 
                }
            }
        }

        $file = Asset::getByPath($path);
        $csvData = $file->getData();
        $lines = explode("\n", $csvData);
        $first = str_replace('"', '', $lines[0]);
        $first = str_replace("\r", "", $first);

        $keys = explode(',', $first);
        
        $result = [];
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) == '') continue;

            $values = str_getcsv($lines[$i]);
 
            if (count($keys) === count($values)) {
                $result[] = array_combine($keys, $values);
            } 
        }
        if ($type == "hotel") {
            foreach ($result as $item) {
                $point = new \Pimcore\Model\DataObject\Data\GeoCoordinates((float)$item['latitude'], (float)$item['longtitude']);

                $country = Country::getById((int)$item['country']);

                $fullPath = '/Hotel/' .  $item['name'];
                $hotel = Hotel::getByPath($fullPath);
                if (!$hotel) {
                    $hotel =  new Hotel;
                    $hotel->setKey(\Pimcore\Model\Element\Service::getValidKey(trim($item['name']), 'object'));
                    $folder = \Pimcore\Model\DataObject::getByPath("/Hotel/") ??
                    DataObjectService::createFolderByPath("/Hotel/");
                    $hotel->setParent(\Pimcore\Model\DataObject::getByPath($folder));
                }
                $hotel->setIdVtTour($item['id']);
                $hotel->setName($item['name']);
                $hotel->setLocation($item['address']);
                $hotel->setDescription($item['description']);
                $hotel->setMap($point);
                if ($country) {
                    $hotel->setCountry($country);
                }

                $hotel->setPublished(true);

                $hotel->save();

            }
            return new JsonResponse(['status' => 200, 'message' => 'Your data has been added, please go to the "Hotel" folder to check']);
        }

        if ($type == "booking_link") {
            foreach ($result as $item) {
                $hotelItem = Hotel::getByIdVtTour($item['property_id'], 1);

                if ($hotelItem) {
                    $hotelItem->setBooking($item['book_now_link']);
                    $hotelItem->save();
                }    
            }
            return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported BOOKING data']);
        }

        if ($type == "is_first") {
            foreach ($result as $item) {
                $hotelItem = Tour::getByTourId($item['property_id'], 1);

                if ($hotelItem) {
                    $scene = Scene::getBySceneId($item['id'], 1);
                    if ($scene) {
                        $hotelItem->setFirstScene($scene->getId());
                        $hotelItem->save();
                    }
                }    
            }
            return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported SCENE FIRST data']);
        }

        if ($type == "img_hotel") {
            foreach ($result as $item) {
                $hotelItem = Hotel::getByIdVtTour($item['id_prop'], 1);

                if ($hotelItem) {
                    $img = Asset::getById((int)$item['id_img']);
                    if ($img) {
                        $hotelItem->setImage($img);
                        $hotelItem->save();
                    }
                }    
            }
            return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported IMAGE HOTEL data']);
        }

        if ($type == "country") {
            foreach ($result as $item) {
                $country = Country::getById((int)$item['country']);

                $fullPath = '/Hotel/' .  $item['name'];
                $hotel = Hotel::getByPath($fullPath);
                if ($hotel) {
                    $hotel->setCountry($country);
                    $hotel->save();
                }
            }
            return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported BOOKING data']);
        }


        if ($type == "tour") {
            foreach ($result as $item) {
                $fullPath = '/Tours/' .  $item['name'];
                $tour = Tour::getByPath($fullPath);
                if (!$tour) {
                    $tour =  new Tour;
                    $tour->setKey(\Pimcore\Model\Element\Service::getValidKey(trim($item['name']), 'object'));
                    $folder = \Pimcore\Model\DataObject::getByPath("/Tours/") ??
                    DataObjectService::createFolderByPath("/Tours/");
                    $tour->setParent(\Pimcore\Model\DataObject::getByPath($folder));
                }
                $tour->setTourId($item['id']);
                $tour->setName($item['name']);
                $tour->setAddress($item['address']);
                $tour->setDescription($item['description']);
                $tour->setPublished(true);

                $tour->save();

                $hotelItem = Hotel::getByIdVtTour($item['id'], 1);
                if ($hotelItem) {
                    $hotelItem->setVtTour($tour);
                    $hotelItem->save();
                }
            }
            return new JsonResponse(['status' => 200, 'message' => 'Your data has been added, please go to the "Tour" folder to check']);

        } else {
            if ($type == "scene") {
                $dataScene = [];
                foreach ($result as $item) {
                    $idTour = $item['property_id'];
                    $tour = Tour::getByTourId($idTour, 1);
                    
                    if ($tour) {
                        $arrScene = $tour->getScenes() ? $tour->getScenes() : [];
                        $path = $tour->getFullPath() . '/' . $item['scene_name'];
                        $scene = Scene::getByPath($path);
                        if (!$scene) {
                            $scene =  new Scene;
                            $scene->setKey(\Pimcore\Model\Element\Service::getValidKey(trim($item['scene_name']), 'object'));
                            $folder = \Pimcore\Model\DataObject::getByPath($tour->getFullPath()) ??
                            DataObjectService::createFolderByPath($tour->getFullPath());
                            $scene->setParent(\Pimcore\Model\DataObject::getByPath($folder));
                        }

                        $scene->setTourId($item['property_id']);
                        $scene->setSceneId($item['id']);
                        $scene->setVtSlug($item['vt_slug']);
                        $scene->setName($item['scene_name']);
                        $scene->setTitle($item['scene_title']);
                        $scene->setUrl($item['scene_url']);
                        $scene->setMenuPath($item['menu_path']);
                        $scene->setMultires($item['is_multires']);
                        // $scene->setTitleByTour($item['scene_title_by_tour']);
                        $scene->setPublished(true);

                        $scene->save();

                        if (!isset($dataScene[$idTour])) {
                            $dataScene[$idTour] = [];
                        }
                    
                        $dataScene[$idTour][] = $scene;

                    }
                }

                return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported SCENE data']);
            }
            if ($type == "scene_view") {
                foreach ($result as $item) {
                    $idScene = $item['sceneId'];
                    $scene = Scene::getBySceneId($idScene, 1);
                    if ($scene) {
                        // $scene->setName($item['name']);
                        // $scene->setTitle($item['title']);
                        // $scene->setUrl($item['url']);
                        // $scene->setMenuPath($item['menuPath']);
                        // $scene->setMultires($item['multires']);

                        $scene->setHlookat($item['hlookat']);
                        $scene->setVlookat($item['vlookat']);
                        $scene->setFovtype($item['fovtype']);
                        $scene->setFov($item['fov']);
                        $scene->setFovmax($item['fovmax']);
                        $scene->setMaxpixelzoom($item['maxpixelzoom']);
                        $scene->setLimitview($item['limitview']);
                        $scene->setHlookatmin($item['hlookatmin']);
                        $scene->setHlookatmax($item['hlookatmax']);
                        $scene->setVlookatmin($item['vlookatmin']);
                        $scene->setVlookatmax($item['vlookatmax']);

                        $scene->save();
                    }
                }
                return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported SCENE_VIEW data']);

            }

            if ($type == "hotspot") {
                $data = [];
                foreach ($result as $item) {
                    $sceneId = $item['scene_id'];
                
                    if (!isset($data[$sceneId])) {
                        $data[$sceneId] = [];
                    }
                
                    $data[$sceneId][] = $item;
                }
                
                foreach ($data as $key => $value) {
                    $items = new DataObject\Fieldcollection();

                    $idScene = $key;
                    $scene = Scene::getBySceneId($idScene, 1);
                    if ($scene) {
                        foreach ($value as $val) {
                            $item = new DataObject\Fieldcollection\Data\Hotspot();
                            $item->setSceneId($val['scene_id']);
                            $item->setAtv($val['atv']);
                            $item->setAth($val['ath']);
                            $item->setLinkedscene($val['linkedscene']);
        
                            $items->add($item);
                        }
                      
                        $scene->setListHospot($items);
                        $scene->save();
                    }
                }
                return new JsonResponse(['status' => 200, 'message' => 'You have successfully imported HOTSPOT data']);

            }
        }

        return new JsonResponse(['status' => 500, 'message' => 'Error']);

    }
}