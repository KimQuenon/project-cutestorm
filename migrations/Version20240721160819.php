<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240721160819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE following (id INT AUTO_INCREMENT NOT NULL, follower_user_id INT NOT NULL, followed_user_id INT NOT NULL, INDEX IDX_71BF8DE370FC2906 (follower_user_id), INDEX IDX_71BF8DE3AF2612FD (followed_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE following ADD CONSTRAINT FK_71BF8DE370FC2906 FOREIGN KEY (follower_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE following ADD CONSTRAINT FK_71BF8DE3AF2612FD FOREIGN KEY (followed_user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE following DROP FOREIGN KEY FK_71BF8DE370FC2906');
        $this->addSql('ALTER TABLE following DROP FOREIGN KEY FK_71BF8DE3AF2612FD');
        $this->addSql('DROP TABLE following');
    }
}
