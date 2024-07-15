<?php

namespace CorepulseBundle\Service;

use Pimcore\Db;

class DatabaseService
{
    const COREPULSE_INDEXING_TABLE = 'corepulse_indexing';
    const COREPULSE_NOTIFICATION_TABLE = 'corepulse_notification';

    public static function createTables()
    {
        self::createCorepulseIndexing();
        self::createCorepulseNotification();
    }

    public static function updateTables()
    {
        self::createTables();
        self::updateCorepulseIndexing();
    }

    public static function createCorepulseIndexing()
    {
        $query = "CREATE TABLE IF NOT EXISTS " . self::COREPULSE_INDEXING_TABLE . " (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `url` COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `type` COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `response` COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `createAt` timestamp DEFAULT current_timestamp(),
            `updateAt` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        Db::get()->executeQuery($query);
    }

    public static function createCorepulseNotification()
    {
        $query = "CREATE TABLE IF NOT EXISTS " . self::COREPULSE_NOTIFICATION_TABLE . " (
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
        $query = "ALTER TABLE " . self::COREPULSE_INDEXING_TABLE . "
            ADD COLUMN IF NOT EXISTS `id` int(11) NOT NULL AUTO_INCREMENT,
            ADD COLUMN IF NOT EXISTS `url` COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `type` COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `response` COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `createAt` timestamp DEFAULT current_timestamp(),
            ADD COLUMN IF NOT EXISTS `updateAt` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        ";

        Db::get()->executeQuery($query);
    }
}
