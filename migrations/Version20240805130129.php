<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240805130129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, delivery_id INT NOT NULL, total_price DOUBLE PRECISION NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), INDEX IDX_F529939812136921 (delivery_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939812136921 FOREIGN KEY (delivery_id) REFERENCES delivery (id)');
        $this->addSql('ALTER TABLE cart_item ADD order_related_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527F2B83D54 FOREIGN KEY (order_related_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_F0FE2527F2B83D54 ON cart_item (order_related_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527F2B83D54');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939812136921');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP INDEX IDX_F0FE2527F2B83D54 ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP order_related_id');
    }
}
