<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413212058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vendor_request ADD motivation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE vendor_request ADD id_card_file_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vendor_request DROP motivation');
        $this->addSql('ALTER TABLE vendor_request DROP id_card_file_name');
    }
}
