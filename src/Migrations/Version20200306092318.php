<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200306092318 extends AbstractMigration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE indicator_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notification_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE app_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE indicator (id INT NOT NULL, mandatary_id INT NOT NULL, key VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D1349DB35938CE63 ON indicator (mandatary_id)');
        $this->addSql('CREATE TABLE boolean_indicator (id INT NOT NULL, value BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE training_program_indicator (id INT NOT NULL, completed_missions TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN training_program_indicator.completed_missions IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE administrative_indicator (id INT NOT NULL, valid_rsac BOOLEAN DEFAULT NULL, valid_siret BOOLEAN DEFAULT NULL, valid_rcp BOOLEAN DEFAULT NULL, valid_cci BOOLEAN DEFAULT NULL, valid_tva BOOLEAN DEFAULT NULL, valid_portage BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE notification (id INT NOT NULL, user_id INT NOT NULL, initiator_id INT DEFAULT NULL, message TEXT NOT NULL, url VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, read BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA7DB3B714 ON notification (initiator_id)');
        $this->addSql('CREATE TABLE app_user (id INT NOT NULL, email VARCHAR(180) NOT NULL, network VARCHAR(255) DEFAULT \'pp\' NOT NULL, imported BOOLEAN DEFAULT \'false\' NOT NULL, previously_imported_data TEXT NOT NULL, currently_imported_data TEXT NOT NULL, enabled BOOLEAN DEFAULT \'true\' NOT NULL, zimbra_password VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');
        $this->addSql('COMMENT ON COLUMN app_user.previously_imported_data IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN app_user.currently_imported_data IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE mandatary (id INT NOT NULL, animator_id INT DEFAULT NULL, tutor_id INT DEFAULT NULL, coach_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, civility VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, begin_date DATE NOT NULL, termination_date DATE DEFAULT NULL, zip_code VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, bareme VARCHAR(255) NOT NULL, contract INT DEFAULT NULL, activities TEXT NOT NULL, pack VARCHAR(255) DEFAULT NULL, care_level INT DEFAULT NULL, support_status INT NOT NULL, full_time_job BOOLEAN DEFAULT NULL, skilled BOOLEAN DEFAULT NULL, could_be_developer BOOLEAN DEFAULT NULL, could_be_animator BOOLEAN DEFAULT NULL, could_be_trainer BOOLEAN DEFAULT NULL, crm_url VARCHAR(255) NOT NULL, website_url VARCHAR(255) NOT NULL, freshdesk_user_id VARCHAR(255) DEFAULT NULL, tutoring_start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, tutoring_end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, sales_revenue_history TEXT NOT NULL, sales_revenue INT NOT NULL, crm_logins_count INT NOT NULL, last_crm_login_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, trades_dates TEXT NOT NULL, trades_count INT NOT NULL, exclusive_trades_count INT NOT NULL, compromises_dates TEXT NOT NULL, compromises_count INT NOT NULL, sales_dates TEXT NOT NULL, sales_count INT NOT NULL, trade_shortfall BOOLEAN NOT NULL, compromise_shortfall BOOLEAN NOT NULL, sale_shortfall BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2242B241989D9B62 ON mandatary (slug)');
        $this->addSql('CREATE INDEX IDX_2242B24170FBD26D ON mandatary (animator_id)');
        $this->addSql('CREATE INDEX IDX_2242B241208F64F1 ON mandatary (tutor_id)');
        $this->addSql('CREATE INDEX IDX_2242B2413C105691 ON mandatary (coach_id)');
        $this->addSql('COMMENT ON COLUMN mandatary.activities IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN mandatary.sales_revenue_history IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN mandatary.trades_dates IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN mandatary.compromises_dates IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN mandatary.sales_dates IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE coach (id INT NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE event (id INT NOT NULL, mandatary_id INT NOT NULL, coach_id INT DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deletable BOOLEAN DEFAULT \'false\' NOT NULL, sms_sent BOOLEAN DEFAULT \'false\' NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3BAE0AA75938CE63 ON event (mandatary_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA73C105691 ON event (coach_id)');
        $this->addSql('CREATE TABLE beginning_birthday_event (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE nth_compromise_event (id INT NOT NULL, nth INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE contract_update_event (id INT NOT NULL, old_contract VARCHAR(255) DEFAULT NULL, new_contract VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sale_shortfall_event (id INT NOT NULL, days_since_last_sale INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE activities_update_event (id INT NOT NULL, old_activities TEXT NOT NULL, new_activities TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN activities_update_event.old_activities IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN activities_update_event.new_activities IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE nth_sale_event (id INT NOT NULL, nth INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE training_program_mission_event (id INT NOT NULL, mission VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE nth_trade_event (id INT NOT NULL, nth INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE administrative_update_event (id INT NOT NULL, old_valid_rsac BOOLEAN DEFAULT NULL, new_valid_rsac BOOLEAN DEFAULT NULL, old_valid_siret BOOLEAN DEFAULT NULL, new_valid_siret BOOLEAN DEFAULT NULL, old_valid_rcp BOOLEAN DEFAULT NULL, new_valid_rcp BOOLEAN DEFAULT NULL, old_valid_cci BOOLEAN DEFAULT NULL, new_valid_cci BOOLEAN DEFAULT NULL, old_valid_tva BOOLEAN DEFAULT NULL, new_valid_tva BOOLEAN DEFAULT NULL, old_valid_portage BOOLEAN DEFAULT NULL, new_valid_portage BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE pack_update_event (id INT NOT NULL, old_pack VARCHAR(255) DEFAULT NULL, new_pack VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mandatary_reminder_event (id INT NOT NULL, way INT NOT NULL, content TEXT NOT NULL, sent BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sms_event (id INT NOT NULL, content TEXT NOT NULL, sent BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE freshdesk_feedback_event (id INT NOT NULL, rating INT NOT NULL, comment TEXT DEFAULT NULL, ticket_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE trade_shortfall_event (id INT NOT NULL, days_since_last_trade INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE beginning_event (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE birthday_event (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE compromise_shortfall_event (id INT NOT NULL, days_since_last_compromise INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE termination_event (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE comment_event (id INT NOT NULL, comment TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE appointment_event (id INT NOT NULL, subject VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, duration VARCHAR(255) NOT NULL, google_calendar_id VARCHAR(255) DEFAULT NULL, zimbra_calendar_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN appointment_event.duration IS \'(DC2Type:dateinterval)\'');
        $this->addSql('CREATE TABLE coach_reminder_event (id INT NOT NULL, way INT NOT NULL, content TEXT NOT NULL, sent BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE call_event (id INT NOT NULL, incoming BOOLEAN NOT NULL, report TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE indicator ADD CONSTRAINT FK_D1349DB35938CE63 FOREIGN KEY (mandatary_id) REFERENCES mandatary (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE boolean_indicator ADD CONSTRAINT FK_B4A8E47FBF396750 FOREIGN KEY (id) REFERENCES indicator (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE training_program_indicator ADD CONSTRAINT FK_9093D2BBF396750 FOREIGN KEY (id) REFERENCES indicator (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE administrative_indicator ADD CONSTRAINT FK_6F962FD4BF396750 FOREIGN KEY (id) REFERENCES indicator (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA7DB3B714 FOREIGN KEY (initiator_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mandatary ADD CONSTRAINT FK_2242B24170FBD26D FOREIGN KEY (animator_id) REFERENCES mandatary (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mandatary ADD CONSTRAINT FK_2242B241208F64F1 FOREIGN KEY (tutor_id) REFERENCES mandatary (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mandatary ADD CONSTRAINT FK_2242B2413C105691 FOREIGN KEY (coach_id) REFERENCES coach (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mandatary ADD CONSTRAINT FK_2242B241BF396750 FOREIGN KEY (id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE coach ADD CONSTRAINT FK_3F596DCCBF396750 FOREIGN KEY (id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA75938CE63 FOREIGN KEY (mandatary_id) REFERENCES mandatary (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA73C105691 FOREIGN KEY (coach_id) REFERENCES coach (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE beginning_birthday_event ADD CONSTRAINT FK_FE516863BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE nth_compromise_event ADD CONSTRAINT FK_77ED6EDDBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contract_update_event ADD CONSTRAINT FK_BE5DFDF1BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sale_shortfall_event ADD CONSTRAINT FK_B251170BBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE activities_update_event ADD CONSTRAINT FK_98D7156EBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE nth_sale_event ADD CONSTRAINT FK_2150BC3ABF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE training_program_mission_event ADD CONSTRAINT FK_E854D3E9BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE nth_trade_event ADD CONSTRAINT FK_EB66C5C9BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE administrative_update_event ADD CONSTRAINT FK_F8793BC0BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pack_update_event ADD CONSTRAINT FK_A4317488BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mandatary_reminder_event ADD CONSTRAINT FK_36629D71BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_event ADD CONSTRAINT FK_B91DD3FABF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE freshdesk_feedback_event ADD CONSTRAINT FK_B9AD84F1BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE trade_shortfall_event ADD CONSTRAINT FK_B482A239BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE beginning_event ADD CONSTRAINT FK_4506A536BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE birthday_event ADD CONSTRAINT FK_15BE3A86BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE compromise_shortfall_event ADD CONSTRAINT FK_6418327CBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE termination_event ADD CONSTRAINT FK_C9D2AADBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comment_event ADD CONSTRAINT FK_92349256BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointment_event ADD CONSTRAINT FK_D7DE3B49BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE coach_reminder_event ADD CONSTRAINT FK_B77E7A24BF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE call_event ADD CONSTRAINT FK_5944961FBF396750 FOREIGN KEY (id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @inheritdoc
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE boolean_indicator DROP CONSTRAINT FK_B4A8E47FBF396750');
        $this->addSql('ALTER TABLE training_program_indicator DROP CONSTRAINT FK_9093D2BBF396750');
        $this->addSql('ALTER TABLE administrative_indicator DROP CONSTRAINT FK_6F962FD4BF396750');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA7DB3B714');
        $this->addSql('ALTER TABLE mandatary DROP CONSTRAINT FK_2242B241BF396750');
        $this->addSql('ALTER TABLE coach DROP CONSTRAINT FK_3F596DCCBF396750');
        $this->addSql('ALTER TABLE indicator DROP CONSTRAINT FK_D1349DB35938CE63');
        $this->addSql('ALTER TABLE mandatary DROP CONSTRAINT FK_2242B24170FBD26D');
        $this->addSql('ALTER TABLE mandatary DROP CONSTRAINT FK_2242B241208F64F1');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA75938CE63');
        $this->addSql('ALTER TABLE mandatary DROP CONSTRAINT FK_2242B2413C105691');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA73C105691');
        $this->addSql('ALTER TABLE beginning_birthday_event DROP CONSTRAINT FK_FE516863BF396750');
        $this->addSql('ALTER TABLE nth_compromise_event DROP CONSTRAINT FK_77ED6EDDBF396750');
        $this->addSql('ALTER TABLE contract_update_event DROP CONSTRAINT FK_BE5DFDF1BF396750');
        $this->addSql('ALTER TABLE sale_shortfall_event DROP CONSTRAINT FK_B251170BBF396750');
        $this->addSql('ALTER TABLE activities_update_event DROP CONSTRAINT FK_98D7156EBF396750');
        $this->addSql('ALTER TABLE nth_sale_event DROP CONSTRAINT FK_2150BC3ABF396750');
        $this->addSql('ALTER TABLE training_program_mission_event DROP CONSTRAINT FK_E854D3E9BF396750');
        $this->addSql('ALTER TABLE nth_trade_event DROP CONSTRAINT FK_EB66C5C9BF396750');
        $this->addSql('ALTER TABLE administrative_update_event DROP CONSTRAINT FK_F8793BC0BF396750');
        $this->addSql('ALTER TABLE pack_update_event DROP CONSTRAINT FK_A4317488BF396750');
        $this->addSql('ALTER TABLE mandatary_reminder_event DROP CONSTRAINT FK_36629D71BF396750');
        $this->addSql('ALTER TABLE sms_event DROP CONSTRAINT FK_B91DD3FABF396750');
        $this->addSql('ALTER TABLE freshdesk_feedback_event DROP CONSTRAINT FK_B9AD84F1BF396750');
        $this->addSql('ALTER TABLE trade_shortfall_event DROP CONSTRAINT FK_B482A239BF396750');
        $this->addSql('ALTER TABLE beginning_event DROP CONSTRAINT FK_4506A536BF396750');
        $this->addSql('ALTER TABLE birthday_event DROP CONSTRAINT FK_15BE3A86BF396750');
        $this->addSql('ALTER TABLE compromise_shortfall_event DROP CONSTRAINT FK_6418327CBF396750');
        $this->addSql('ALTER TABLE termination_event DROP CONSTRAINT FK_C9D2AADBF396750');
        $this->addSql('ALTER TABLE comment_event DROP CONSTRAINT FK_92349256BF396750');
        $this->addSql('ALTER TABLE appointment_event DROP CONSTRAINT FK_D7DE3B49BF396750');
        $this->addSql('ALTER TABLE coach_reminder_event DROP CONSTRAINT FK_B77E7A24BF396750');
        $this->addSql('ALTER TABLE call_event DROP CONSTRAINT FK_5944961FBF396750');
        $this->addSql('DROP SEQUENCE indicator_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notification_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE app_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE event_id_seq CASCADE');
        $this->addSql('DROP TABLE indicator');
        $this->addSql('DROP TABLE boolean_indicator');
        $this->addSql('DROP TABLE training_program_indicator');
        $this->addSql('DROP TABLE administrative_indicator');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE mandatary');
        $this->addSql('DROP TABLE coach');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE beginning_birthday_event');
        $this->addSql('DROP TABLE nth_compromise_event');
        $this->addSql('DROP TABLE contract_update_event');
        $this->addSql('DROP TABLE sale_shortfall_event');
        $this->addSql('DROP TABLE activities_update_event');
        $this->addSql('DROP TABLE nth_sale_event');
        $this->addSql('DROP TABLE training_program_mission_event');
        $this->addSql('DROP TABLE nth_trade_event');
        $this->addSql('DROP TABLE administrative_update_event');
        $this->addSql('DROP TABLE pack_update_event');
        $this->addSql('DROP TABLE mandatary_reminder_event');
        $this->addSql('DROP TABLE sms_event');
        $this->addSql('DROP TABLE freshdesk_feedback_event');
        $this->addSql('DROP TABLE trade_shortfall_event');
        $this->addSql('DROP TABLE beginning_event');
        $this->addSql('DROP TABLE birthday_event');
        $this->addSql('DROP TABLE compromise_shortfall_event');
        $this->addSql('DROP TABLE termination_event');
        $this->addSql('DROP TABLE comment_event');
        $this->addSql('DROP TABLE appointment_event');
        $this->addSql('DROP TABLE coach_reminder_event');
        $this->addSql('DROP TABLE call_event');
    }
}
