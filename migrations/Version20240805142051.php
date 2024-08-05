<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240805142051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527F2B83D54');
        $this->addSql('DROP INDEX IDX_F0FE2527F2B83D54 ON cart_item');
        $this->addSql('ALTER TABLE cart_item CHANGE order_related_id order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25278D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_F0FE25278D9F6D38 ON cart_item (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25278D9F6D38');
        $this->addSql('DROP INDEX IDX_F0FE25278D9F6D38 ON cart_item');
        $this->addSql('ALTER TABLE cart_item CHANGE order_id order_related_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527F2B83D54 FOREIGN KEY (order_related_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F0FE2527F2B83D54 ON cart_item (order_related_id)');
    }
}
