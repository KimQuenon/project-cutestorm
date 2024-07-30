<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240730102250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E93E89D3ED');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A45BB98C');
        $this->addSql('DROP INDEX IDX_8A8E26E9A45BB98C ON conversation');
        $this->addSql('DROP INDEX IDX_8A8E26E93E89D3ED ON conversation');
        $this->addSql('ALTER TABLE conversation ADD sender_id INT NOT NULL, ADD recipient_id INT NOT NULL, DROP sent_by_id, DROP sent_to_id');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9F624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E9F624B39D ON conversation (sender_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E9E92F8F78 ON conversation (recipient_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9F624B39D');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9E92F8F78');
        $this->addSql('DROP INDEX IDX_8A8E26E9F624B39D ON conversation');
        $this->addSql('DROP INDEX IDX_8A8E26E9E92F8F78 ON conversation');
        $this->addSql('ALTER TABLE conversation ADD sent_by_id INT NOT NULL, ADD sent_to_id INT NOT NULL, DROP sender_id, DROP recipient_id');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E93E89D3ED FOREIGN KEY (sent_to_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A45BB98C FOREIGN KEY (sent_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8A8E26E9A45BB98C ON conversation (sent_by_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E93E89D3ED ON conversation (sent_to_id)');
    }
}
