<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240729154432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation ADD sent_to_id INT NOT NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E93E89D3ED FOREIGN KEY (sent_to_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E93E89D3ED ON conversation (sent_to_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E93E89D3ED');
        $this->addSql('DROP INDEX IDX_8A8E26E93E89D3ED ON conversation');
        $this->addSql('ALTER TABLE conversation DROP sent_to_id');
    }
}
