<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301184815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data LONGTEXT DEFAULT NULL');
    $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons LONGTEXT DEFAULT NULL, CHANGE timings timings LONGTEXT DEFAULT NULL, CHANGE extra extra LONGTEXT DEFAULT NULL');
}

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP coin_balance, DROP phone, DROP telegram_chat_id, DROP face_descriptor, DROP face_image');
        $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons LONGTEXT DEFAULT NULL, CHANGE timings timings LONGTEXT DEFAULT NULL, CHANGE extra extra LONGTEXT DEFAULT NULL');
    }
}
