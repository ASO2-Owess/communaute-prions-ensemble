# Analyse du prototype — Communauté Prions Ensemble

Analyse du fichier `prototype/Communaute_Prions_Ensemble.html` (4,6 Mo, un seul
fichier). Ce document sert de base au cadrage. Il décrit **ce qui existe
réellement**, **ce qui est simulé**, et **ce qu'il faudra construire**.

---

## 1. Ce que fait le prototype

Une application de vie spirituelle, pensée pour le mobile (largeur maximale
520 px), en français, orientée Afrique de l'Ouest (paiements Wave et Orange
Money, indicatif +225 Côte d'Ivoire).

**14 écrans**, accessibles depuis une grille d'accueil :

| Écran | Contenu |
|-------|---------|
| Accueil | Verset du jour + grille de navigation |
| Bible | AT / NT → livre → chapitre, texte intégral |
| Méditation | Contexte, leçons, « rhéma » par chapitre |
| Quiz Bible | QCM général ou généré sur un chapitre lu |
| Agenda | Plan de lecture chronologique en 12 jours |
| Bloc-notes | Notes personnelles de l'utilisateur |
| Encouragements | Versets d'espoir et de motivation |
| Personnages | 6 catégories, ~60 figures bibliques, biographies |
| Ma progression | Points, niveaux, série de jours consécutifs |
| Classement | Palmarès de la communauté |
| Pasteur | Présentation + « J'ai une question » |
| Faire un don | Numéros Wave / Orange Money à copier |

**Système de progression déjà conçu :**
- Points : lecture d'un chapitre = 5, méditation = 15, quiz = 10.
- Niveaux : Disciple (0) → Serviteur (150) → Intendant (400) → Ancien (900) →
  Berger (1800).
- Série (« streak ») de jours consécutifs d'activité.

## 2. Le contenu embarqué : le point fort du prototype

La **Bible Louis Segond complète** est intégrée dans le fichier :

- 66 livres
- 1 189 chapitres
- 31 170 versets
- 4,34 Mo de données JSON

C'est un actif considérable et déjà prêt. Il fonctionne **totalement hors
ligne**, ce qui est décisif pour le public visé.

Sont aussi embarqués : 3 images (JPEG, en base64), un jeu d'icônes SVG, des
méditations écrites à la main pour quelques chapitres, un quiz statique, un plan
de lecture de 12 jours, et une liste de personnages bibliques.

## 3. Ce qui est **simulé** (et ne fonctionnera pas hors de la maquette)

C'est la section la plus importante du document. Un prototype a le droit de
faire semblant ; un produit livré, non.

### 3.1 Le classement communautaire est faux

```js
const COMMUNITY = [
  {name:"Aminata K.", pts:1420}, {name:"Jean-Marc T.", pts:1185}, ...
];
```

Douze utilisateurs écrits en dur dans le code. Il n'y a **aucune communauté
réelle** : chaque personne qui installe l'application voit les douze mêmes noms
inventés et se compare à eux. Un vrai classement suppose des comptes
utilisateurs et un serveur qui centralise les points.

### 3.2 Les questions au pasteur ne partent nulle part

L'écran « J'ai une question » enregistre le texte **sur le téléphone de
l'utilisateur uniquement**. Aucun pasteur ne la reçoit jamais. L'application
affiche « En attente de réponse » — une attente qui ne se terminera jamais.
C'est le décalage le plus grave entre la promesse faite à l'utilisateur et la
réalité technique.

### 3.3 L'appel à l'IA ne peut pas fonctionner ainsi

Les méditations dynamiques, les biographies de personnages et les quiz par
chapitre sont générés par un appel à l'API de Claude :

```js
const response = await fetch("https://api.anthropic.com/v1/messages", { ... });
```

Cet appel **ne contient aucune clé d'authentification**. Il ne fonctionne que
parce que la maquette tourne dans un environnement Claude qui l'injecte à sa
place. Sortie de cet environnement, chaque appel échouera.

Et surtout : **on ne peut pas simplement ajouter la clé dans l'application.**
Voir la section 5.2.

### 3.4 La sauvegarde utilise une API qui n'existe pas ailleurs

```js
await window.storage.get('progress');
```

`window.storage` est fourni par l'environnement de la maquette. Dans un
navigateur ordinaire ou une application mobile, cet objet n'existe pas : notes,
progression, questions et profil seraient perdus à chaque ouverture.

### 3.5 Les dons ne sont pas des paiements

L'écran affiche deux numéros de téléphone avec un bouton « Copier ». Aucune
transaction n'est déclenchée, aucun don n'est enregistré, aucun reçu n'est émis.
C'est un pense-bête, pas un système de paiement.

### 3.6 Pas de compte utilisateur

Aucune inscription, aucune connexion. Le champ « nom » du profil est purement
décoratif. Conséquence directe : impossible de changer de téléphone sans tout
perdre, impossible d'avoir un classement réel, impossible pour un pasteur de
savoir qui pose une question.

## 4. Synthèse : ce qui est acquis, ce qui reste à construire

| Élément | État |
|---------|------|
| Texte biblique intégral | **Acquis** — réutilisable tel quel |
| Design, identité visuelle, parcours | **Acquis** — sert de référence |
| Système de points et de niveaux | **Conçu**, à réimplémenter côté serveur |
| Lecture hors ligne | **Acquis** dans le principe |
| Sauvegarde des données | À refaire (persistance réelle) |
| Comptes utilisateurs | À construire entièrement |
| Classement réel | À construire entièrement |
| Questions au pasteur (aller-retour) | À construire entièrement |
| Espace de réponse pour le pasteur | À construire entièrement (n'existe pas) |
| Génération IA (méditations, bios, quiz) | À déplacer côté serveur |
| Dons | À décider : vitrine ou vraie intégration |

**Lecture de ce tableau :** le prototype a résolu le problème du *contenu* et de
l'*expérience*. Il n'a rien résolu de ce qui fait une application
**communautaire** — et le nom du produit est « Communauté Prions Ensemble ».
C'est là que se trouve tout le travail.

## 5. Deux points d'attention avant de continuer

### 5.1 Données personnelles dans le prototype

Le fichier contient un numéro de téléphone réel (+225 07 09 13 85 75) et le nom
d'une personne identifiable (Dr. Stéphane NIAZALE). À confirmer avec le
commanditaire : ces informations sont-elles bien destinées à être publiques ?

### 5.2 Une clé d'API ne peut jamais vivre dans l'application

Point technique à comprendre absolument.

Une application mobile ou web est **téléchargée sur l'appareil de
l'utilisateur**. Tout ce qu'elle contient peut être extrait : il suffit d'ouvrir
le fichier, d'inspecter le trafic réseau ou de décompiler l'application. Une clé
d'API glissée dans le code n'est pas cachée, elle est simplement **peu visible**
— ce n'est pas la même chose.

Conséquence concrète : quelqu'un récupère la clé et consomme le budget IA du
projet à sa place, sans limite.

La solution est structurelle : l'application ne parle jamais directement à
l'API de l'IA. Elle parle à **notre propre serveur**, qui détient la clé et qui
seul contacte l'IA.

```
AVANT (prototype)     Application ──────────────────► API Claude
                                    (clé exposée)

APRÈS (produit)       Application ──► Notre serveur ──► API Claude
                                                        (clé protégée)
```

Ce serveur intermédiaire apporte trois bénéfices supplémentaires :
1. Il **met en cache** les contenus générés — une méditation sur Jean 3 n'est
   produite qu'une fois, puis servie à tous. Coût divisé par le nombre
   d'utilisateurs.
2. Il **limite les abus** (nombre d'appels par utilisateur et par jour).
3. Il permet de **relire et corriger** un contenu généré avant publication —
   essentiel sur un sujet religieux, où une approximation doctrinale a un coût
   réel pour la communauté.

Ce serveur est de toute façon nécessaire pour les comptes, le classement et les
questions au pasteur. Il ne s'agit donc pas d'une contrainte supplémentaire,
mais de la pièce centrale qui manque au projet.
