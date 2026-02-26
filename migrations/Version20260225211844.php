<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Performance migration — corrigée.
 *
 * Doctrine voulait faire RENAME INDEX (non supporté sur MySQL < 5.7.7 ou
 * MariaDB ancienne). On DROP l'ancien index auto-généré et on recrée avec
 * le nom explicite + on ajoute les 2 nouveaux index composites.
 */
final class Version20260225211844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes to complaint table (fixed for MySQL)';
    }

    public function up(Schema $schema): void
    {
        // IDX_5F2732B5F4BD7827 (assigned_to_id) est lié à une contrainte FK :
        // MySQL interdit de le supprimer tant que la FK existe — on le laisse.
        // Il couvre déjà les JOINs sur assigned_to_id.

        // Index composite pour ORDER BY priority DESC, created_at DESC
        // et les filtres WHERE status = ?
        $this->addSql('CREATE INDEX idx_complaint_list_sort ON complaint (status, priority, created_at)');

        // Index pour AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
        $this->addSql('CREATE INDEX idx_complaint_resolved_at ON complaint (resolved_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_complaint_list_sort ON complaint');
        $this->addSql('DROP INDEX idx_complaint_resolved_at ON complaint');
    }
}