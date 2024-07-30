<?php

namespace CorepulseBundle\Services;

use Pimcore\Db;

class DatabaseServices
{
    const COREPULSE_INDEXING_TABLE = 'corepulse_indexing';
    const COREPULSE_NOTIFICATION_TABLE = 'corepulse_notification';
    const COREPULSE_PLAUSIBLE_TABLE = 'corepulse_plausible';


    public static function createTables()
    {
        self::createCorepulseIndexing();
        self::createCorepulseNotification();
    }

    public static function updateTables()
    {
        self::createTables();
        self::updateCorepulseIndexing();
        self::updateCorepulseUser();
        self::updateCorepulsePlausible();
    }

    public static function createCorepulseIndexing()
    {
        $query = "CREATE TABLE IF NOT EXISTS " . self::COREPULSE_INDEXING_TABLE . " (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `url` varchar(255) DEFAULT NULL,
            `type` varchar(255) DEFAULT NULL,
            `response` varchar(255) DEFAULT NULL,
            `createAt` timestamp DEFAULT current_timestamp(),
            `updateAt` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        Db::get()->executeQuery($query);
    }

    public static function createCorepulseNotification()
    {
        $query = " CREATE TABLE IF NOT EXISTS " . self::COREPULSE_NOTIFICATION_TABLE . " (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255)  DEFAULT NULL,
            `description` varchar(255)  DEFAULT NULL,
            `user` int(11) NOT NULL,
            `sender` int(11) NOT NULL,
            `type` varchar(255)  DEFAULT NULL,
            `action` varchar(255)  DEFAULT NULL,
            `actionType` varchar(255)  DEFAULT NULL,
            `active` tinyint(1) DEFAULT 0,
            `createAt` timestamp DEFAULT current_timestamp(),
            `updateAt` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        COMMIT;";

        Db::get()->executeQuery($query);
    }

    public static function updateCorepulseIndexing()
    {
        $query = " ALTER TABLE " . self::COREPULSE_INDEXING_TABLE . "
            ADD COLUMN IF NOT EXISTS `id` int(11) NOT NULL AUTO_INCREMENT,
            ADD COLUMN IF NOT EXISTS `url` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `type` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `response` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `internalType` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `internalValue` int(11) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `status` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `result` longtext DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `language` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `createAt` timestamp DEFAULT current_timestamp(),
            ADD COLUMN IF NOT EXISTS `updateAt` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp();
            ADD COLUMN IF NOT EXISTS `time` varchar(255) DEFAULT NULL,
        ";

        Db::get()->executeQuery($query);
    }

    public static function updateCorepulseUser()
    {
        $query = "ALTER TABLE `corepulse_users`
            ADD COLUMN IF NOT EXISTS `authToken` longtext DEFAULT NULL";

        Db::get()->executeQuery($query);
    }

    public static function updateCorepulsePlausible()
    {
        $query = " ALTER TABLE " . self::COREPULSE_PLAUSIBLE_TABLE . "
            ADD COLUMN IF NOT EXISTS `id` int(11) NOT NULL AUTO_INCREMENT,
            ADD COLUMN IF NOT EXISTS `domain` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `siteId` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `apiKey` varchar(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `username` varchar(190) NOT NULL,
            ADD COLUMN IF NOT EXISTS `password` varchar(255) NOT NULL,
            ADD COLUMN IF NOT EXISTS `link` varchar(255) DEFAULT NULL;
        ";

        Db::get()->executeQuery($query);
    }
}
