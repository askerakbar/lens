<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240501CreateQueryLogTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create lens_logs table for Laminas Lens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `lens_logs` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `batch_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
            `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
            `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=497 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `lens_logs`;');
    }
} 