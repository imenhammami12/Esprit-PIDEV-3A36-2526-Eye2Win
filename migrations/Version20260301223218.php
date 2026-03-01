<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301223218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifie si la table existe déjà
        $tableExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tournoi_user'"
        )->fetchOne();

        if (!$tableExists) {
            $this->addSql('CREATE TABLE tournoi_user (tournoi_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D0703ACDF607770A (tournoi_id), INDEX IDX_D0703ACDA76ED395 (user_id), PRIMARY KEY (tournoi_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
            $this->addSql('ALTER TABLE tournoi_user ADD CONSTRAINT FK_D0703ACDF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE tournoi_user ADD CONSTRAINT FK_D0703ACDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        }

        // Vérifie si la colonne play_mode existe déjà dans matches
        $colExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'matches' AND column_name = 'play_mode'"
        )->fetchOne();

        if (!$colExists) {
            $this->addSql('ALTER TABLE matches ADD play_mode VARCHAR(20) DEFAULT \'En Ligne\' NOT NULL, ADD localisation VARCHAR(255) DEFAULT NULL');
        }

        // Vérifie si la colonne prix existe déjà dans tournoi
        $prixExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tournoi' AND column_name = 'prix'"
        )->fetchOne();

        if (!$prixExists) {
            $this->addSql('ALTER TABLE tournoi ADD prix DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }

        $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons LONGTEXT DEFAULT NULL, CHANGE timings timings LONGTEXT DEFAULT NULL, CHANGE extra extra LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi_user DROP FOREIGN KEY FK_D0703ACDF607770A');
        $this->addSql('ALTER TABLE tournoi_user DROP FOREIGN KEY FK_D0703ACDA76ED395');
        $this->addSql('DROP TABLE IF EXISTS tournoi_user');
        $this->addSql('ALTER TABLE matches DROP play_mode, DROP localisation');
        $this->addSql('ALTER TABLE tournoi DROP prix');
        $this->addSql('ALTER TABLE valorant_match CHANGE raw_data raw_data LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE valorant_statistique CHANGE weapons weapons LONGTEXT DEFAULT NULL, CHANGE timings timings LONGTEXT DEFAULT NULL, CHANGE extra extra LONGTEXT DEFAULT NULL');
    }
}