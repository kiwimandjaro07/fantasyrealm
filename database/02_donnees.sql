-- =====================================================================
-- FantasyRealm Online - données de démonstration (environnement de développement)
--
-- Comptes (mots de passe hachés en argon2id) :
--   admin@pixelverse-studios.local    / Admin#Fantasy2026    administrateur (créé ici : impossible depuis l'application)
--   jose@pixelverse-studios.local     / Employe#Fantasy2026  employé
--   lyra@exemple.fr   (Lyra)           / Joueur#Fantasy2026   joueuse
--   thorin@exemple.fr (Thorin)         / Joueur#Fantasy2026   joueur
--   kael@exemple.fr   (Kael)           / Joueur#Fantasy2026   joueur
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO utilisateurs (id, email, pseudo, mot_de_passe, role) VALUES
 (1, 'admin@pixelverse-studios.local', 'Admin',
     '$argon2id$v=19$m=65536,t=4,p=1$dWJwc2FuSUs3MVBjSzVMdQ$7hh2OeEqKOxeYqywSz9M7bm+72JVWchdsUVBAWGA9wo', 'admin'),
 (2, 'jose@pixelverse-studios.local', 'Jose',
     '$argon2id$v=19$m=65536,t=4,p=1$aVRYaGQ5WDJiQWdrU0htMA$lDUu8qn0ln+HT1zdJHXvIFdJK1efRFxrZOHzs83jKg0', 'employe'),
 (3, 'lyra@exemple.fr', 'Lyra',
     '$argon2id$v=19$m=65536,t=4,p=1$U2hGeW5tSU9ySnpaM3VTTg$kUTAjI/Ijzzq++plPu3uQL1Z89smTYKM4Cx5rw88i6Y', 'joueur'),
 (4, 'thorin@exemple.fr', 'Thorin',
     '$argon2id$v=19$m=65536,t=4,p=1$U2hGeW5tSU9ySnpaM3VTTg$kUTAjI/Ijzzq++plPu3uQL1Z89smTYKM4Cx5rw88i6Y', 'joueur'),
 (5, 'kael@exemple.fr', 'Kael',
     '$argon2id$v=19$m=65536,t=4,p=1$U2hGeW5tSU9ySnpaM3VTTg$kUTAjI/Ijzzq++plPu3uQL1Z89smTYKM4Cx5rw88i6Y', 'joueur');

-- Bibliothèque d'articles
INSERT INTO accessoires (id, nom, categorie, visuel, couleur, description) VALUES
 (1,  'Tunique du voyageur',   'vetement',   'tunique',    '#6b8e4e', 'Lin solide et ceinture de cuir.'),
 (2,  'Robe d''archimage',      'vetement',   'robe',       '#3b2a66', 'Tissée de fils d''étoile.'),
 (3,  'Cape de l''ombre',       'vetement',   'cape',       '#2a2a3a', 'Discrète à la nuit tombée.'),
 (4,  'Robe des moissons',      'vetement',   'robe',       '#c9772f', 'Couleurs d''automne.'),
 (5,  'Plastron de Fer-Royal',  'armure',     'plastron',   '#9aa3ad', 'Forgé dans les montagnes du Nord.'),
 (6,  'Cotte de mailles elfique','armure',    'cotte',      '#c8d6d9', 'Légère comme une plume.'),
 (7,  'Épaulières du dragon',   'armure',     'epaulieres', '#8c2f2f', 'Écailles de dragon rouge.'),
 (8,  'Épée runique',           'arme',       'epee',       '#cfd8e3', 'Ses runes brillent face au danger.'),
 (9,  'Hache des clans',        'arme',       'hache',      '#7d858f', 'Lourde, mais redoutable.'),
 (10, 'Arc sylvestre',          'arme',       'arc',        '#7a5230', 'Bois d''if centenaire.'),
 (11, 'Bâton d''émeraude',      'arme',       'baton',      '#2fbf71', 'Canalise la magie de la forêt.'),
 (12, 'Couronne d''or',         'accessoire', 'couronne',   '#d4a63a', 'Réservée aux héros.'),
 (13, 'Amulette de lune',       'accessoire', 'amulette',   '#a7c7e7', 'Protège des malédictions.'),
 (14, 'Bouclier du gardien',    'accessoire', 'bouclier',   '#3d6fb6', 'Arbore le blason du royaume.'),
 (15, 'Ailes de fée',           'accessoire', 'ailes',      '#e6b3ff', 'Scintillent au soleil.'),
 (16, 'Masse d''armes rouillée', 'arme',      'hache',      '#8a5a3b', 'Article retiré de la boutique.');
UPDATE accessoires SET actif = FALSE WHERE id = 16;

-- Personnages (dates de création réparties pour tester les filtres)
INSERT INTO personnages (id, utilisateur_id, nom, genre, forme_visage, forme_yeux, forme_nez, forme_bouche, coiffure,
                         couleur_peau, couleur_cheveux, couleur_yeux, statut, partage, cree_le, valide_le) VALUES
 (1, 3, 'Aelwen',        'feminin',     'ovale',   'amande',  'droit',     'souriante', 'longue', '#9fbf8f', '#e3c16f', '#3f7d3a', 'valide', TRUE,  '2026-09-02 10:00:00', '2026-09-02 15:00:00'),
 (2, 3, 'Sylvara',       'feminin',     'allonge', 'grands',  'retrousse', 'pleine',    'tresse', '#d9d2e9', '#6b3fa0', '#7a3fb0', 'valide', TRUE,  '2026-10-12 09:30:00', '2026-10-12 11:00:00'),
 (3, 4, 'Thorgrim',      'masculin',    'carre',   'plisses', 'large',     'severe',    'crete',  '#c68e63', '#b5482a', '#5b3a1e', 'valide', TRUE,  '2026-09-20 18:00:00', '2026-09-21 08:00:00'),
 (4, 4, 'Brunhild',      'feminin',     'rond',    'ronds',   'aquilin',   'fine',      'tresse', '#e8b896', '#e3c16f', '#3b6ea8', 'valide', FALSE, '2026-10-30 14:00:00', '2026-10-30 16:00:00'),
 (5, 5, 'Kaelthas',      'masculin',    'allonge', 'amande',  'aquilin',   'severe',    'longue', '#a7b8d8', '#e9e6df', '#c98a1b', 'valide', TRUE,  '2026-10-05 20:00:00', '2026-10-06 09:00:00'),
 (6, 5, 'Nyx',           'non_binaire', 'ovale',   'grands',  'droit',     'souriante', 'courte', '#5c3a26', '#2d3e8c', '#7b8794', 'valide', TRUE,  '2026-11-01 12:00:00', '2026-11-01 17:00:00'),
 (7, 3, 'Ombrelune',     'non_binaire', 'rond',    'plisses', 'retrousse', 'fine',      'chauve', '#8d5a3b', '#1f1a17', '#8a6a2f', 'en_attente', FALSE, '2026-11-06 21:00:00', NULL),
 (8, 4, 'Grimbald',      'masculin',    'carre',   'ronds',   'large',     'pleine',    'courte', '#f5d6c1', '#5a3825', '#3f7d3a', 'en_attente', FALSE, '2026-11-07 08:00:00', NULL);

INSERT INTO personnage_accessoires (personnage_id, accessoire_id, categorie) VALUES
 (1, 1, 'vetement'), (1, 10, 'arme'), (1, 15, 'accessoire'),
 (2, 2, 'vetement'), (2, 11, 'arme'), (2, 13, 'accessoire'),
 (3, 1, 'vetement'), (3, 5, 'armure'), (3, 9, 'arme'), (3, 14, 'accessoire'),
 (4, 4, 'vetement'), (4, 6, 'armure'), (4, 8, 'arme'),
 (5, 3, 'vetement'), (5, 7, 'armure'), (5, 8, 'arme'), (5, 12, 'accessoire'),
 (6, 2, 'vetement'), (6, 11, 'arme'), (6, 13, 'accessoire');

INSERT INTO avis (personnage_id, utilisateur_id, note, commentaire, statut, cree_le, modere_le) VALUES
 (1, 4, 5, 'Les ailes et l''arc vont parfaitement ensemble, superbe elfe !', 'valide', '2026-09-25 10:00:00', '2026-09-25 12:00:00'),
 (1, 5, 4, 'Très réussie, j''aurais ajouté une amulette.', 'valide', '2026-10-07 18:00:00', '2026-10-08 09:00:00'),
 (3, 3, 5, 'Un vrai nain des montagnes, la crête rousse est parfaite.', 'valide', '2026-09-22 20:00:00', '2026-09-23 08:00:00'),
 (5, 3, 4, 'La couronne et les épaulières de dragon, quelle prestance.', 'valide', '2026-10-10 11:00:00', '2026-10-10 15:00:00'),
 (5, 4, 3, 'Pas mal, mais la cape cache un peu l''armure.', 'en_attente', '2026-11-05 19:00:00', NULL),
 (6, 4, 5, 'Nyx a un style unique !', 'en_attente', '2026-11-06 09:00:00', NULL);
