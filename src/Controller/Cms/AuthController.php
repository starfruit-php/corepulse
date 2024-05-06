<?php

namespace CorepulseBundle\Controller\Cms;

use Pimcore\Db;
use Pimcore\Model\Asset;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends BaseController
{

    /**
     * @Route("/login", name="vuetify_login", methods={"GET"}))
     * @Route("/login", name="vuetify_login_attempt", methods={"POST"}))
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {

        $currentUser = $this->getUser();

        if ($currentUser !== null) {

            return $this->redirectToRoute('vuetify_dashboard');
        }

        $loginSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "login"', []);
        if ($loginSetting && $loginSetting['config']) {
            $config = json_decode($loginSetting['config'], true);
            if ($config['background']) {
                $image = Asset::getByPath($config['background']);
                $config['background'] = $image ? $config['background'] : '/bundles/pimcoreadmin/img/login/pc11.svg';
            }
        } else {
            $config = [];
        }

        return $this->renderWithInertia('Auth/Login', ["data" => $config]);
    }

    /**
     * @Route("/logout", name="vuetify_logout", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function logout()
    {
    }
}
