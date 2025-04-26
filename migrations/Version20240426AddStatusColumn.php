<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240426AddStatusColumn extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to reservation_chambre table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation_chambre ADD status VARCHAR(20) DEFAULT \'en_attente\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation_chambre DROP COLUMN status');
    }
} 