<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414114547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD notif_new_auctions BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD notif_reminders BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD notif_results BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD notif_newsletter BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP notif_new_auctions');
        $this->addSql('ALTER TABLE "user" DROP notif_reminders');
        $this->addSql('ALTER TABLE "user" DROP notif_results');
        $this->addSql('ALTER TABLE "user" DROP notif_newsletter');
    }
}
