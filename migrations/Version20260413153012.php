<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413153012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice ALTER currency SET NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER currency SET NOT NULL');
        $this->addSql('ALTER TABLE subscription ALTER currency_snapshot SET NOT NULL');
        $this->addSql('ALTER TABLE subscription_plan ALTER currency SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice ALTER currency DROP NOT NULL');
        $this->addSql('ALTER TABLE payment ALTER currency DROP NOT NULL');
        $this->addSql('ALTER TABLE subscription ALTER currency_snapshot DROP NOT NULL');
        $this->addSql('ALTER TABLE subscription_plan ALTER currency DROP NOT NULL');
    }
}
