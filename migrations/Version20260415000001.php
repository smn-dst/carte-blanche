<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add abandoned_cart_email_sent_at to cart and reminder_email_sent_at to ticket for automated email campaigns.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart ADD abandoned_cart_email_sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD reminder_email_sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart DROP abandoned_cart_email_sent_at');
        $this->addSql('ALTER TABLE ticket DROP reminder_email_sent_at');
    }
}
