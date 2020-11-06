<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200930122741 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fee_greater_than_event (id INT NOT NULL, fee_excl_tax INT NOT NULL, fee_price_min INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE fee_greater_than_event ADD CONSTRAINT FK_7D45335DBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mandatary ADD fee_excl_tax TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN mandatary.fee_excl_tax IS \'(DC2Type:array)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE fee_greater_than_event');
        $this->addSql('ALTER TABLE mandatary DROP fee_excl_tax');
    }
}
