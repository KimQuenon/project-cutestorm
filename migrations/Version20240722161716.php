<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240722161716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE follow_request (id INT AUTO_INCREMENT NOT NULL, sent_by_id INT NOT NULL, sent_to_id INT NOT NULL, status TINYINT(1) NOT NULL, INDEX IDX_6562D72FA45BB98C (sent_by_id), INDEX IDX_6562D72F3E89D3ED (sent_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE follow_request ADD CONSTRAINT FK_6562D72FA45BB98C FOREIGN KEY (sent_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE follow_request ADD CONSTRAINT FK_6562D72F3E89D3ED FOREIGN KEY (sent_to_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE follow_request DROP FOREIGN KEY FK_6562D72FA45BB98C');
        $this->addSql('ALTER TABLE follow_request DROP FOREIGN KEY FK_6562D72F3E89D3ED');
        $this->addSql('DROP TABLE follow_request');
    }
}
