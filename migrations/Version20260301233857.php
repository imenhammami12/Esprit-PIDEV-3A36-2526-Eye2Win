<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301233857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournoi CHANGE prix prix DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons JSON DEFAULT NULL, CHANGE timings timings JSON DEFAULT NULL, CHANGE extra extra JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournoi CHANGE prix prix DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons LONGTEXT DEFAULT NULL, CHANGE timings timings LONGTEXT DEFAULT NULL, CHANGE extra extra LONGTEXT DEFAULT NULL');
    }
}
