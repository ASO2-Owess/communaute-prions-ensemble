# Communauté Prions Ensemble — contexte du projet

> Contexte métier et décisions **de ce projet**.
> La méthode de travail commune est dans `../CLAUDE.md`.
> Le cadrage détaillé est dans `PROJECT-BRIEF.md`.

**Statut :** conception · **Démarré le :** 25 août 2026
**Client :** Dr. Stéphane NIAZALE — à la fois commanditaire, auteur du prototype
et **pasteur répondant** de l'application.

---

## 1. Ce que fait l'application

Une application de vie spirituelle pour une communauté chrétienne francophone
d'Afrique de l'Ouest (Côte d'Ivoire, paiements Wave et Orange Money). Elle
permet de lire la Bible, méditer, progresser dans la connaissance des Écritures,
poser des questions à l'équipe pastorale et se situer dans une communauté.

**Contrainte structurante : tout le cœur de l'application doit fonctionner sans
connexion internet.** Les données mobiles coûtent cher au public visé.

## 2. Ce qui existe déjà

Deux livrables antérieurs, tous deux issus du même code :

1. **Prototype HTML** — un artifact Claude mono-fichier de 4,6 Mo.
2. **Build PWA** (`pwa/`) — le même code dépaqueté en application web
   installable : `index.html`, `manifest.json`, `sw.js`, icônes, `img/`.
   Malgré le nom d'origine du dossier, **ce n'est pas un projet Android natif**.

### L'actif majeur : la Bible intégrale embarquée

| | |
|---|---|
| Traduction | Louis Segond |
| Livres | 66 |
| Chapitres | 1 189 |
| Versets | 31 170 |
| Poids | 4,34 Mo de JSON |

Ce contenu est **réutilisable tel quel** et constitue la principale valeur déjà
acquise du projet. Il ne change jamais → il reste embarqué dans l'application,
jamais servi par le serveur.

### Les 14 écrans conçus

Accueil (verset du jour) · Bible (AT/NT → livre → chapitre) · Méditation ·
Quiz · Agenda (plan de lecture 12 jours) · Bloc-notes · Encouragements ·
Personnages (~60 figures, 6 catégories) · Ma progression · Classement ·
Pasteur · J'ai une question · Faire un don.

### Le système de progression

| Action | Points |
|--------|--------|
| Lire un chapitre | 5 |
| Méditer un chapitre | 15 |
| Terminer un quiz | 10 |

Niveaux : Disciple (0) → Serviteur (150) → Intendant (400) → Ancien (900) →
Berger (1800). Plus une série (*streak*) de jours consécutifs d'activité.

### Identité visuelle

Palette déjà fixée, à conserver dans Flutter :

| Rôle | Couleur |
|------|---------|
| Parchemin (fond) | `#f7f2e7` |
| Indigo | `#232a4d` |
| Indigo profond | `#161a33` |
| Or | `#b8863b` |
| Or clair | `#e8c988` |
| Paroles de Dieu | `#2f7d4f` (vert) |
| Paroles des anges | `#2f5fa8` (bleu) |
| Paroles de Jésus | `#a5322a` (rouge) |

Titres en serif (Georgia), corps en sans-serif système. Largeur maximale 520 px
— pensé mobile dès l'origine.

## 3. Ce qui est simulé et doit être construit

**À garder en tête en permanence : l'application s'appelle « Communauté », et
c'est précisément la communauté qui n'existe pas encore.**

| Élément | Réalité actuelle |
|---------|------------------|
| Classement | 12 utilisateurs **codés en dur** — les mêmes pour tout le monde |
| Questions au pasteur | Stockées sur le téléphone, **n'atteignent personne** ; l'écran affiche « En attente de réponse » pour une attente sans fin |
| Espace pastoral | **N'existe pas** — aucun écran pour lire et répondre |
| Comptes utilisateurs | Aucun — changer de téléphone = tout perdre |
| Appels IA | `fetch("https://api.anthropic.com/v1/messages")` **sans clé** — échouera hors du bac à sable |
| Dons | Deux numéros affichés + bouton « Copier ». Aucun paiement, aucune trace |
| Stockage | `localStorage` (5–10 Mo), et l'erreur de quota est **avalée par un `catch` vide** |

Défauts du build PWA relevés et non encore corrigés : `icon-192.png` fait
réellement 1254×1254 px et 1,57 Mo ; `icon-maskable-512.png` est le clone exact
de `icon-512.png` (pas de marge de sécurité) ; le `LISEZ-MOI` indique une
procédure de mise à jour erronée (il faut incrémenter `CACHE_NAME` dans `sw.js`,
sinon la mise à jour n'atteint jamais les utilisateurs).

## 4. Décisions prises

| # | Décision | Conséquence |
|---|----------|-------------|
| 1 | Android **et** iOS | Impose un socle multiplateforme |
| 2 | Plus de 2 000 membres visés | Vraie base de données indexée, quotas IA, modération |
| 3 | Un pasteur répondant identifié | On construit l'espace pastoral — voir le risque n°1 |
| 4 | Dons : numéros en phase 1, paiement en phase 2 | Modèle de données prévu pour l'accueillir |
| 5 | **Stack : Flutter (mobile) + Laravel (API)** | Décision finale du 25/08/2026 |
| 6 | Commencer par l'API Laravel | Aucun travail jeté ; la PWA peut s'y brancher en attendant Flutter |

Détail et alternatives écartées : `docs/DECISIONS.md`.

## 5. Architecture cible

```
   App Flutter (Android + iOS)            PWA existante (transition)
   ├─ Bible embarquée (hors ligne)                 │
   ├─ Stockage local (lectures, notes)             │
   └─ Sync quand le réseau revient                 │
              │                                     │
              └──────────────┬──────────────────────┘
                             ▼
                  API Laravel
                  ├─ Comptes & authentification
                  ├─ Progression, points, classement
                  ├─ Questions au pasteur + espace de réponse
                  ├─ Cache mutualisé des contenus IA
                  └─ Relais IA (détient la clé)
                             │
                             ▼
                       API Claude
```

**Trois règles d'architecture non négociables :**

1. **La clé de l'API IA ne quitte jamais le serveur.** L'application ne parle
   jamais directement à l'IA. Une application est téléchargée sur l'appareil de
   l'utilisateur : tout ce qu'elle contient est extractible.
2. **Les contenus générés par IA sont mutualisés.** Une méditation sur Jean 3
   est produite **une fois** puis servie à tous. Le coût est divisé par le nombre
   d'utilisateurs — décisif avec 2 000+ membres.
3. **Tout contenu IA est relu avant publication.** Sujet religieux : une
   approximation doctrinale coûte la confiance de la communauté. C'est une
   contrainte produit, pas un confort.

**Ce qui reste local :** le texte biblique (ne change jamais) et l'état de
lecture, pour que l'app fonctionne en mode avion.
**Ce qui vit sur le serveur :** tout ce qui est partagé ou doit survivre au
changement d'appareil.

## 6. Le risque n°1 : le goulot d'étranglement pastoral

Plus de 2 000 membres, **un seul** répondant.

| Participation | Questions/mois | Par jour |
|---------------|----------------|----------|
| 2 % | 40 | ~1,3 |
| 5 % | 100 | ~3,3 |
| 10 % | 200 | ~6,7 |

Chaque question demande une réponse réfléchie, sur des sujets parfois intimes.
Une personne seule ne tient pas ce rythme. Conséquences à intégrer **dès la
conception** :

- Afficher un **délai de réponse annoncé et honnête**, jamais une attente
  indéfinie comme aujourd'hui.
- Une **FAQ alimentée par les réponses déjà données** — c'est elle qui absorbe
  la charge, beaucoup de questions se répètent.
- Un **pré-tri** des questions par thème et urgence.
- Prévoir **plusieurs répondants dans le schéma de données dès le départ**, même
  s'il n'y en a qu'un au lancement. Rajouter cette notion après coup coûte dix
  fois plus cher.

## 7. Sensibilité des données

Les questions au pasteur sont des **données personnelles sensibles** : sujets
spirituels, parfois intimes, parfois liés à des situations de détresse. Elles ne
doivent jamais apparaître dans les journaux applicatifs, être envoyées à un
service tiers sans nécessité, ni être visibles par un autre membre.

Le prototype contient un numéro de téléphone réel (+225 07 09 13 85 75) et le nom
de Dr. Stéphane NIAZALE — accord à confirmer avant toute publication.

## 8. Organisation du dossier

```
communaute-prions-ensemble/
├── CLAUDE.md            <- ce fichier
├── PROJECT-BRIEF.md     <- cadrage complet
├── README.md            <- installer et lancer le projet
├── docs/
│   ├── DECISIONS.md         décisions techniques et alternatives écartées
│   ├── ANALYSE-PROTOTYPE.md ce qui est réel vs simulé
│   └── ANALYSE-BUILD-PWA.md analyse du build existant
├── pwa/                 <- l'existant : sert de référence et de transition
├── api/                 <- backend Laravel        (à construire)
└── mobile/              <- application Flutter    (à construire)
```

Un **seul dépôt Git** à la racine du projet, contenant `api/` et `mobile/`.
C'est un choix assumé : les deux parties évoluent ensemble, une modification
d'API et son usage mobile se relisent dans le même commit. Le prix à payer est
un déploiement un peu plus attentif (ne déployer que le dossier concerné).

## 9. Questions encore ouvertes

- Budget d'hébergement
- Délai de livraison attendu
- Notifications push : nécessaires ou non
- Périmètre exclu à écrire noir sur blanc : audio ? autre traduction ?
  messagerie entre membres ? diffusion de cultes ?
- Le rôle « responsable de communauté » existe-t-il, distinct du pasteur ?
