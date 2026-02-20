<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260220155201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE "user" ADD picture VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP token');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP picture');
    }
}
