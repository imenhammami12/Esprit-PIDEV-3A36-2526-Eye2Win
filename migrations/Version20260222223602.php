<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222223602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_invite DROP FOREIGN KEY `FK_39812AA972F5A1AA`');
        $this->addSql('ALTER TABLE channel_invite CHANGE channel_id channel_id INT NOT NULL');
        $this->addSql('ALTER TABLE channel_invite ADD CONSTRAINT FK_39812AA972F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE channel_join_request DROP FOREIGN KEY `FK_A5422E5E72F5A1AA`');
        $this->addSql('ALTER TABLE channel_join_request CHANGE channel_id channel_id INT NOT NULL');
        $this->addSql('ALTER TABLE channel_join_request ADD CONSTRAINT FK_A5422E5E72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE channel_member DROP FOREIGN KEY `FK_8E87C00672F5A1AA`');
        $this->addSql('ALTER TABLE channel_member CHANGE channel_id channel_id INT NOT NULL');
        $this->addSql('ALTER TABLE channel_member ADD CONSTRAINT FK_8E87C00672F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_invite DROP FOREIGN KEY FK_39812AA972F5A1AA');
        $this->addSql('ALTER TABLE channel_invite CHANGE channel_id channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE channel_invite ADD CONSTRAINT `FK_39812AA972F5A1AA` FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $this->addSql('ALTER TABLE channel_join_request DROP FOREIGN KEY FK_A5422E5E72F5A1AA');
        $this->addSql('ALTER TABLE channel_join_request CHANGE channel_id channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE channel_join_request ADD CONSTRAINT `FK_A5422E5E72F5A1AA` FOREIGN KEY (channel_id) REFERENCES channel (id)');
        $this->addSql('ALTER TABLE channel_member DROP FOREIGN KEY FK_8E87C00672F5A1AA');
        $this->addSql('ALTER TABLE channel_member CHANGE channel_id channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE channel_member ADD CONSTRAINT `FK_8E87C00672F5A1AA` FOREIGN KEY (channel_id) REFERENCES channel (id)');
    }
}
