<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250207143208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(150) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE logement (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(150) NOT NULL, surface INT NOT NULL, image_path VARCHAR(255) NOT NULL, nb_personne INT NOT NULL, emplacement INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE logement_equipement (logement_id INT NOT NULL, equipement_id INT NOT NULL, INDEX IDX_85F9697158ABF955 (logement_id), INDEX IDX_85F96971806F0F5C (equipement_id), PRIMARY KEY(logement_id, equipement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rental (id INT AUTO_INCREMENT NOT NULL, users_id INT DEFAULT NULL, logement_id INT DEFAULT NULL, date_start DATETIME NOT NULL, date_end DATETIME NOT NULL, nb_adulte INT NOT NULL, nb_child INT NOT NULL, INDEX IDX_1619C27D67B3B43D (users_id), INDEX IDX_1619C27D58ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE saison (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(150) NOT NULL, date_s DATE NOT NULL, date_e DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, firstname VARCHAR(150) NOT NULL, lastname VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE logement_equipement ADD CONSTRAINT FK_85F9697158ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE logement_equipement ADD CONSTRAINT FK_85F96971806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rental ADD CONSTRAINT FK_1619C27D67B3B43D FOREIGN KEY (users_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rental ADD CONSTRAINT FK_1619C27D58ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE logement_equipement DROP FOREIGN KEY FK_85F9697158ABF955');
        $this->addSql('ALTER TABLE logement_equipement DROP FOREIGN KEY FK_85F96971806F0F5C');
        $this->addSql('ALTER TABLE rental DROP FOREIGN KEY FK_1619C27D67B3B43D');
        $this->addSql('ALTER TABLE rental DROP FOREIGN KEY FK_1619C27D58ABF955');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE logement_equipement');
        $this->addSql('DROP TABLE rental');
        $this->addSql('DROP TABLE saison');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
