<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les colonnes d'analyse de sentiment à la table complaint.
 *
 * Commande : php bin/console doctrine:migrations:migrate
 */
final class Version20250227AddSentimentToComplaint extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sentiment analysis columns to complaint table (SentimentServiceComplaints)';
    }

    public function up(Schema $schema): void
    {
        // Label : POSITIVE | NEUTRAL | NEGATIVE
        $this->addSql('ALTER TABLE complaint ADD sentiment_label VARCHAR(20) DEFAULT NULL');

        // Score de confiance 0.0 – 1.0
        $this->addSql('ALTER TABLE complaint ADD sentiment_score DOUBLE PRECISION DEFAULT NULL');

        // Source : api | fallback
        $this->addSql('ALTER TABLE complaint ADD sentiment_source VARCHAR(20) DEFAULT NULL');

        // Priorité suggérée par SentimentServiceComplaints : URGENT | HIGH | null
        $this->addSql('ALTER TABLE complaint ADD sentiment_priority_suggestion VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE complaint DROP COLUMN sentiment_label');
        $this->addSql('ALTER TABLE complaint DROP COLUMN sentiment_score');
        $this->addSql('ALTER TABLE complaint DROP COLUMN sentiment_source');
        $this->addSql('ALTER TABLE complaint DROP COLUMN sentiment_priority_suggestion');
    }
}
