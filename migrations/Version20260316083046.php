<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316083046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image ALTER file_name DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD notif_new_auctions BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE "user" ADD notif_reminders BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE "user" ADD notif_results BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE "user" ADD notif_newsletter BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN notif_new_auctions DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN notif_reminders DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN notif_results DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN notif_newsletter DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image ALTER file_name SET NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP notif_new_auctions');
        $this->addSql('ALTER TABLE "user" DROP notif_reminders');
        $this->addSql('ALTER TABLE "user" DROP notif_results');
        $this->addSql('ALTER TABLE "user" DROP notif_newsletter');
    }
}
