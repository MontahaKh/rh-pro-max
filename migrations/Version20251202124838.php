<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202124838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename visitor table to candidate and visitor_id column to candidate_id';
    }

    public function up(Schema $schema): void
    {
        // First, drop the foreign key constraint
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY `FK_E8607AE70BEE6D`');
        $this->addSql('DROP INDEX IDX_E8607AE70BEE6D ON candidate_profile');

        // Rename the visitor table to candidate
        $this->addSql('RENAME TABLE visitor TO candidate');

        // Update the unique index name
        $this->addSql('ALTER TABLE candidate DROP INDEX UNIQ_VISITOR_EMAIL, ADD UNIQUE INDEX UNIQ_CANDIDATE_EMAIL (email)');

        // Rename the column in candidate_profile
        $this->addSql('ALTER TABLE candidate_profile CHANGE visitor_id candidate_id INT DEFAULT NULL');

        // Add the new foreign key constraint
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AE91BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id)');
        $this->addSql('CREATE INDEX IDX_E8607AE91BD8781 ON candidate_profile (candidate_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop the foreign key constraint
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AE91BD8781');
        $this->addSql('DROP INDEX IDX_E8607AE91BD8781 ON candidate_profile');

        // Rename the candidate table back to visitor
        $this->addSql('RENAME TABLE candidate TO visitor');

        // Restore the unique index name
        $this->addSql('ALTER TABLE visitor DROP INDEX UNIQ_CANDIDATE_EMAIL, ADD UNIQUE INDEX UNIQ_VISITOR_EMAIL (email)');

        // Rename the column back
        $this->addSql('ALTER TABLE candidate_profile CHANGE candidate_id visitor_id INT DEFAULT NULL');

        // Add the original foreign key constraint
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT `FK_E8607AE70BEE6D` FOREIGN KEY (visitor_id) REFERENCES visitor (id)');
        $this->addSql('CREATE INDEX IDX_E8607AE70BEE6D ON candidate_profile (visitor_id)');
    }
}
