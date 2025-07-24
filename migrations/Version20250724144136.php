<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724144136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agent (id UUID NOT NULL, current_mission_id INT DEFAULT NULL, mentor_id UUID DEFAULT NULL, infiltrated_country_id INT DEFAULT NULL, code_name VARCHAR(50) NOT NULL, years_of_experience INT NOT NULL, status VARCHAR(255) NOT NULL, enrolement_date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_268B9C9D61D767E2 ON agent (current_mission_id)');
        $this->addSql('CREATE INDEX IDX_268B9C9DDB403044 ON agent (mentor_id)');
        $this->addSql('CREATE INDEX IDX_268B9C9D9FB86183 ON agent (infiltrated_country_id)');
        $this->addSql('COMMENT ON COLUMN agent.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN agent.mentor_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN agent.enrolement_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE country (id SERIAL NOT NULL, cell_leader_id UUID DEFAULT NULL, name VARCHAR(100) NOT NULL, danger VARCHAR(255) NOT NULL, number_of_agents INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5373C966984E18B1 ON country (cell_leader_id)');
        $this->addSql('COMMENT ON COLUMN country.cell_leader_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE message (id SERIAL NOT NULL, by_id UUID NOT NULL, title VARCHAR(100) NOT NULL, body VARCHAR(1000) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6BD307FAAE72004 ON message (by_id)');
        $this->addSql('COMMENT ON COLUMN message.by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE mission (id SERIAL NOT NULL, country_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, danger VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, description VARCHAR(500) NOT NULL, objectives VARCHAR(500) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9067F23CF92F3E70 ON mission (country_id)');
        $this->addSql('COMMENT ON COLUMN mission.start_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN mission.end_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE mission_user (mission_id INT NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(mission_id, user_id))');
        $this->addSql('CREATE INDEX IDX_A4D17A46BE6CAE90 ON mission_user (mission_id)');
        $this->addSql('CREATE INDEX IDX_A4D17A46A76ED395 ON mission_user (user_id)');
        $this->addSql('COMMENT ON COLUMN mission_user.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE mission_result (id SERIAL NOT NULL, mission_id INT NOT NULL, status VARCHAR(255) NOT NULL, summary VARCHAR(500) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B921B5DBE6CAE90 ON mission_result (mission_id)');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, roles JSON NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, dtype VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE agent_country (user_id UUID NOT NULL, country_id INT NOT NULL, PRIMARY KEY(user_id, country_id))');
        $this->addSql('CREATE INDEX IDX_8120ABCCA76ED395 ON agent_country (user_id)');
        $this->addSql('CREATE INDEX IDX_8120ABCCF92F3E70 ON agent_country (country_id)');
        $this->addSql('COMMENT ON COLUMN agent_country.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9D61D767E2 FOREIGN KEY (current_mission_id) REFERENCES mission (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9DDB403044 FOREIGN KEY (mentor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9D9FB86183 FOREIGN KEY (infiltrated_country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9DBF396750 FOREIGN KEY (id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE country ADD CONSTRAINT FK_5373C966984E18B1 FOREIGN KEY (cell_leader_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FAAE72004 FOREIGN KEY (by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23CF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mission_user ADD CONSTRAINT FK_A4D17A46BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mission_user ADD CONSTRAINT FK_A4D17A46A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mission_result ADD CONSTRAINT FK_4B921B5DBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE agent_country ADD CONSTRAINT FK_8120ABCCA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE agent_country ADD CONSTRAINT FK_8120ABCCF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE agent DROP CONSTRAINT FK_268B9C9D61D767E2');
        $this->addSql('ALTER TABLE agent DROP CONSTRAINT FK_268B9C9DDB403044');
        $this->addSql('ALTER TABLE agent DROP CONSTRAINT FK_268B9C9D9FB86183');
        $this->addSql('ALTER TABLE agent DROP CONSTRAINT FK_268B9C9DBF396750');
        $this->addSql('ALTER TABLE country DROP CONSTRAINT FK_5373C966984E18B1');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FAAE72004');
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT FK_9067F23CF92F3E70');
        $this->addSql('ALTER TABLE mission_user DROP CONSTRAINT FK_A4D17A46BE6CAE90');
        $this->addSql('ALTER TABLE mission_user DROP CONSTRAINT FK_A4D17A46A76ED395');
        $this->addSql('ALTER TABLE mission_result DROP CONSTRAINT FK_4B921B5DBE6CAE90');
        $this->addSql('ALTER TABLE agent_country DROP CONSTRAINT FK_8120ABCCA76ED395');
        $this->addSql('ALTER TABLE agent_country DROP CONSTRAINT FK_8120ABCCF92F3E70');
        $this->addSql('DROP TABLE agent');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE mission');
        $this->addSql('DROP TABLE mission_user');
        $this->addSql('DROP TABLE mission_result');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE agent_country');
    }
}
