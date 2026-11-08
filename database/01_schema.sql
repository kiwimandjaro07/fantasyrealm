-- =====================================================================
-- FantasyRealm Online - système de gestion de personnages
-- Schéma de la base relationnelle (MySQL 8, moteur InnoDB, utf8mb4)
-- Exécuté automatiquement par le conteneur MySQL à sa première création.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- Comptes : joueurs, employés et administrateur
-- ---------------------------------------------------------------------
CREATE TABLE utilisateurs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(190) NOT NULL,
    pseudo              VARCHAR(30)  NOT NULL,
    mot_de_passe        VARCHAR(255) NOT NULL COMMENT 'Hash argon2id, jamais le mot de passe en clair',
    role                ENUM('joueur', 'employe', 'admin') NOT NULL DEFAULT 'joueur',
    statut              ENUM('actif', 'suspendu') NOT NULL DEFAULT 'actif',
    doit_changer_mdp    BOOLEAN      NOT NULL DEFAULT FALSE COMMENT 'Vrai après une réinitialisation : changement imposé à la connexion',
    derniere_connexion  DATETIME     NULL,
    cree_le             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_utilisateurs_email UNIQUE (email),
    CONSTRAINT uq_utilisateurs_pseudo UNIQUE (pseudo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Personnages. Les traits physiques sont des valeurs prédéfinies (ENUM) :
-- la base refuse toute valeur inconnue, et le rendu de l'avatar reste maîtrisé.
-- ---------------------------------------------------------------------
CREATE TABLE personnages (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id   INT UNSIGNED NOT NULL,
    nom              VARCHAR(40)  NOT NULL,
    genre            ENUM('feminin', 'masculin', 'non_binaire') NOT NULL,
    forme_visage     ENUM('ovale', 'rond', 'carre', 'allonge') NOT NULL DEFAULT 'ovale',
    forme_yeux       ENUM('amande', 'ronds', 'plisses', 'grands') NOT NULL DEFAULT 'amande',
    forme_nez        ENUM('droit', 'retrousse', 'aquilin', 'large') NOT NULL DEFAULT 'droit',
    forme_bouche     ENUM('fine', 'pleine', 'souriante', 'severe') NOT NULL DEFAULT 'fine',
    coiffure         ENUM('courte', 'longue', 'tresse', 'chauve', 'crete') NOT NULL DEFAULT 'courte',
    couleur_peau     CHAR(7)      NOT NULL COMMENT 'Couleur hexadécimale #rrggbb choisie dans une palette',
    couleur_cheveux  CHAR(7)      NOT NULL,
    couleur_yeux     CHAR(7)      NOT NULL,
    statut           ENUM('en_attente', 'valide') NOT NULL DEFAULT 'en_attente'
                     COMMENT 'Un nom refusé entraîne la suppression du personnage',
    partage          BOOLEAN      NOT NULL DEFAULT FALSE,
    cree_le          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le       DATETIME     NULL,
    valide_le        DATETIME     NULL,
    CONSTRAINT uq_personnages_nom UNIQUE (nom),
    CONSTRAINT fk_personnages_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE,
    -- Un personnage ne peut être partagé que s'il est validé
    CONSTRAINT ck_personnages_partage CHECK (partage = FALSE OR statut = 'valide'),
    INDEX idx_personnages_galerie (partage, statut, genre, cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Bibliothèque d'articles de personnalisation, gérée par les employés
-- ---------------------------------------------------------------------
CREATE TABLE accessoires (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(60)  NOT NULL,
    categorie   ENUM('vetement', 'armure', 'arme', 'accessoire') NOT NULL,
    visuel      VARCHAR(30)  NOT NULL COMMENT 'Forme dessinée sur l avatar (liste fermée côté PHP)',
    couleur     CHAR(7)      NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    actif       BOOLEAN      NOT NULL DEFAULT TRUE COMMENT 'Un article désactivé n est plus proposé',
    cree_le     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_accessoires_nom UNIQUE (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Équipement : association plusieurs-à-plusieurs personnages / accessoires
-- La catégorie est recopiée pour imposer UN seul article par catégorie
-- (une seule arme, une seule armure...) grâce à la contrainte UNIQUE.
-- ---------------------------------------------------------------------
CREATE TABLE personnage_accessoires (
    personnage_id  INT UNSIGNED NOT NULL,
    accessoire_id  INT UNSIGNED NOT NULL,
    categorie      ENUM('vetement', 'armure', 'arme', 'accessoire') NOT NULL,
    PRIMARY KEY (personnage_id, accessoire_id),
    CONSTRAINT uq_equipement_categorie UNIQUE (personnage_id, categorie),
    CONSTRAINT fk_equipement_personnage FOREIGN KEY (personnage_id)
        REFERENCES personnages (id) ON DELETE CASCADE,
    CONSTRAINT fk_equipement_accessoire FOREIGN KEY (accessoire_id)
        REFERENCES accessoires (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Avis des joueurs sur les personnages partagés (modérés par un employé)
-- ---------------------------------------------------------------------
CREATE TABLE avis (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    personnage_id   INT UNSIGNED NOT NULL,
    utilisateur_id  INT UNSIGNED NOT NULL,
    note            TINYINT UNSIGNED NOT NULL,
    commentaire     VARCHAR(1000) NOT NULL,
    statut          ENUM('en_attente', 'valide', 'refuse') NOT NULL DEFAULT 'en_attente',
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modere_le       DATETIME     NULL,
    CONSTRAINT ck_avis_note CHECK (note BETWEEN 1 AND 5),
    -- Un joueur dépose un seul avis par personnage
    CONSTRAINT uq_avis_auteur UNIQUE (personnage_id, utilisateur_id),
    CONSTRAINT fk_avis_personnage FOREIGN KEY (personnage_id)
        REFERENCES personnages (id) ON DELETE CASCADE,
    CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE,
    INDEX idx_avis_statut (statut, cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tentatives de connexion échouées (protection contre la force brute)
-- ---------------------------------------------------------------------
CREATE TABLE tentatives_connexion (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email     VARCHAR(190) NOT NULL,
    ip        VARCHAR(45)  NOT NULL,
    tente_le  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tentatives_email (email, tente_le),
    INDEX idx_tentatives_ip (ip, tente_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
