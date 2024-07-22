<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240722120305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification ADD related_user_id INT DEFAULT NULL, CHANGE post_id post_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA98771930 FOREIGN KEY (related_user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA98771930 ON notification (related_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA98771930');
        $this->addSql('DROP INDEX IDX_BF5476CA98771930 ON notification');
        $this->addSql('ALTER TABLE notification DROP related_user_id, CHANGE post_id post_id INT NOT NULL');
    }
}
