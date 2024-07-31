<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240731184406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report ADD reported_post_id INT DEFAULT NULL, ADD reported_user_id INT DEFAULT NULL, ADD reported_comment_id INT DEFAULT NULL, DROP reported_id');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784EC0086D7 FOREIGN KEY (reported_post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784E7566E FOREIGN KEY (reported_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F77849368B60F FOREIGN KEY (reported_comment_id) REFERENCES comment (id)');
        $this->addSql('CREATE INDEX IDX_C42F7784EC0086D7 ON report (reported_post_id)');
        $this->addSql('CREATE INDEX IDX_C42F7784E7566E ON report (reported_user_id)');
        $this->addSql('CREATE INDEX IDX_C42F77849368B60F ON report (reported_comment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784EC0086D7');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F7784E7566E');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_C42F77849368B60F');
        $this->addSql('DROP INDEX IDX_C42F7784EC0086D7 ON report');
        $this->addSql('DROP INDEX IDX_C42F7784E7566E ON report');
        $this->addSql('DROP INDEX IDX_C42F77849368B60F ON report');
        $this->addSql('ALTER TABLE report ADD reported_id INT NOT NULL, DROP reported_post_id, DROP reported_user_id, DROP reported_comment_id');
    }
}
