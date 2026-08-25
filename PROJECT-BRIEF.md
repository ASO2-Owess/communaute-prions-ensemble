# Fiche de cadrage — Communauté Prions Ensemble

> **Version 1 — 25 août 2026.** Quatre décisions structurantes ont été prises
> (voir §10). Les sections encore marquées **[À CONFIRMER]** restent ouvertes.

**Date de démarrage :** 25 août 2026
**Client / commanditaire :** Communauté Prions Ensemble **[À CONFIRMER]**
**Type :** client
**Statut :** cadrage
**Base de départ :** prototype HTML fonctionnel (voir `docs/ANALYSE-PROTOTYPE.md`)

---

## 1. Le problème

Une communauté chrétienne veut donner à ses membres un outil unique pour lire la
Bible, méditer, progresser dans leur connaissance des Écritures, poser leurs
questions à l'équipe pastorale et rester reliés les uns aux autres — y compris
avec une connexion internet coûteuse ou intermittente.

**Comment fait-on aujourd'hui, sans ce projet ?**
Bible papier ou application généraliste, groupe WhatsApp pour les échanges,
questions posées de vive voix après le culte, dons remis en main propre ou par
transfert mobile sans traçabilité. Rien n'est relié, rien ne se mesure.

## 2. Les utilisateurs

| Qui | Ce qu'il vient faire | Ce qui compte pour lui |
|-----|----------------------|------------------------|
| **Membre de la communauté** | Lire, méditer, faire les quiz, suivre sa progression, poser une question, donner | Que ça marche sans connexion et sans consommer ses données mobiles |
| **Pasteur / équipe pastorale** | Lire et répondre aux questions, publier des contenus, suivre la vie de la communauté | Un endroit unique où voir les questions en attente **[écran inexistant à ce jour]** |
| **Responsable de la communauté** | Suivre l'activité, les dons, animer | **[À CONFIRMER : ce rôle existe-t-il, distinct du pasteur ?]** |

**Décidé :** l'équipe pastorale se réduit pour l'instant à **une personne identifiée**. Voir le risque majeur au §8.

## 3. Fonctionnalités

### Obligatoires
- [ ] Lecture de la Bible Louis Segond intégrale, **hors ligne**
- [ ] Sauvegarde réelle et durable : notes, progression, questions
- [ ] Compte utilisateur (pour retrouver ses données en changeant de téléphone)
- [ ] Progression : points, niveaux, série de jours
- [ ] Quiz biblique
- [ ] Bloc-notes personnel
- [ ] Plan de lecture (agenda)
- [ ] Poser une question au pasteur **et recevoir une vraie réponse**
- [ ] Espace pastoral pour lire et répondre aux questions

### Souhaitées
- [ ] Classement communautaire réel
- [ ] Méditations et biographies générées par IA, relues avant publication
- [ ] Quiz généré automatiquement sur un chapitre lu
- [ ] Notifications (verset du jour, rappel de lecture) **[À CONFIRMER]**

### Explicitement hors périmètre
- **[À CONFIRMER]** — à trancher : traduction autre que Louis Segond ? audio ?
  messagerie entre membres ? diffusion de cultes en direct ?

## 4. Les données

| Donnée | Contient quoi | Liée à |
|--------|---------------|--------|
| Utilisateur | Nom, identifiant de connexion, date d'inscription | ses lectures, notes, questions |
| Lecture | Livre, chapitre, date, points gagnés | un utilisateur |
| Note | Texte, date, éventuellement un verset | un utilisateur |
| Question | Texte, date, statut, réponse du pasteur | un utilisateur + un pasteur |
| Résultat de quiz | Score, portée, date | un utilisateur |
| Contenu généré (méditation, biographie, quiz) | JSON, statut de validation | un chapitre ou un personnage — **partagé par tous** |
| Texte biblique | 66 livres, 1 189 chapitres, 31 170 versets | *(donnée fixe, embarquée)* |

**Point d'architecture déjà identifié :** le texte biblique ne change jamais et
doit rester dans l'application. Les contenus générés par IA sont **communs à
tous les utilisateurs** et doivent donc vivre sur le serveur, pas dans chaque
téléphone.

## 5. Intégrations externes

| Service | Pour quoi faire | Si elle tombe en panne |
|---------|-----------------|------------------------|
| API Claude (IA) | Méditations, biographies, quiz par chapitre | Contenus déjà générés servis depuis le cache ; les nouveaux attendent |
| Wave / Orange Money | **Phase 1 : affichage des numéros uniquement.** Vraie intégration reportée en phase 2 | Sans objet en phase 1 |
| Notifications push | Rappels **[À CONFIRMER]** | Dégradation acceptable |

## 6. Contraintes

- **Plateformes visées :** **Android ET iOS** — décidé. Impose un socle mobile multiplateforme.
- **Utilisation hors ligne nécessaire ?** **Oui, contrainte forte** — la lecture
  biblique doit fonctionner sans aucune connexion
- **Volume attendu :** **plus de 2 000 membres** — décidé. Impose une vraie base de données indexée, des quotas IA, et de la modération.
- **Délai :** **[À CONFIRMER]**
- **Budget d'hébergement :** **[À CONFIRMER]** — un serveur est indispensable
- **Contraintes légales :** données personnelles (nom, questions à caractère
  spirituel et parfois intime). Les questions au pasteur sont des données
  **sensibles** et doivent être traitées comme telles.

## 7. Critères de réussite

**[À COMPLÉTER avec le commanditaire]**. Proposition de départ :

1. Un membre peut lire la Bible entière en mode avion, sans erreur.
2. Une question posée reçoit une réponse effective du pasteur dans l'application.
3. Un membre change de téléphone, se reconnecte, et retrouve ses notes et ses points.
4. **[À CONFIRMER : objectif de membres actifs à N mois ?]**

## 8. Risques et zones floues

| Risque | Impact | Ce qu'on fait |
|--------|--------|---------------|
| Contenu IA doctrinalement inexact | **Élevé** — sujet religieux, perte de confiance | Validation humaine avant publication |
| Coût des appels IA non maîtrisé | Moyen | Cache serveur mutualisé + quota par utilisateur |
| **Un seul pasteur pour plus de 2 000 membres** | **Critique** — voir encadré ci-dessous | Modération, file d'attente, FAQ, délai annoncé honnêtement |
| Poids de l'application (Bible embarquée) | Moyen | Mesurer ; envisager un téléchargement au premier lancement |
| Budget serveur non défini | **Bloquant** | À trancher — un serveur est désormais certain |
| Données personnelles du prototype (numéro, nom) | Faible | Confirmer l'accord des personnes concernées |

### Risque n°1 du projet : le goulot d'étranglement pastoral

Deux décisions prises ensemble créent une tension qu'il faut nommer clairement :
**plus de 2 000 membres**, et **une seule personne pour répondre aux questions**.

Si seulement 2 % des membres posent une question dans le mois, cela fait
**40 questions mensuelles** pour une personne — chacune demandant une réponse
réfléchie sur des sujets spirituels parfois intimes. Si le taux monte à 10 %,
c'est 200 questions par mois, soit près de 7 par jour. Aucune personne seule ne
tient ce rythme durablement.

Ce n'est pas un problème technique, mais il a des conséquences techniques
directes. Pistes à prévoir dès la conception :

- Afficher un **délai de réponse annoncé** et honnête, jamais « en attente »
  indéfiniment comme dans le prototype.
- Une **FAQ construite à partir des réponses déjà données** — beaucoup de
  questions se répètent ; y répondre une fois doit servir à tous.
- Un **pré-tri** des questions (par thème, par urgence) pour que le pasteur
  traite d'abord ce qui compte.
- Prévoir dès le départ que **plusieurs répondants** puissent être ajoutés, même
  s'il n'y en a qu'un au lancement. C'est bien plus coûteux à rajouter après.

**À valider avec le commanditaire avant de développer cette fonctionnalité.**

## 9. Décision de stack

**Contraintes qui ferment le débat :** Android **et** iOS, hors-ligne
obligatoire, plus de 2 000 membres, comptes utilisateurs, back-office pastoral,
clé d'API IA à protéger.

**Stack pressentie :** **Flutter** (application mobile) + **Laravel** (serveur d'API)

**Justification :**
Flutter produit les deux applications, Android et iOS, à partir d'un seul code —
sans quoi il faudrait écrire et maintenir deux applications distinctes. Il gère
nativement le stockage local qu'exige le hors-ligne. Laravel fournit
l'authentification, la base de données, un back-office pour le pasteur, et sert
d'intermédiaire détenant la clé de l'API IA.

**Alternatives écartées :**
- **PWA** — écartée : le hors-ligne y est fragile et les notifications ne sont
  pas fiables sur iPhone, alors qu'iOS fait désormais partie du périmètre.
- **Site web classique** — écarté : renonce au hors-ligne, contrainte forte.
- **Deux applications natives (Kotlin + Swift)** — écartée : double le coût de
  développement et de maintenance sans bénéfice pour ce type d'application.

**DÉCISION FINALE : Flutter (mobile) + Laravel (API)** — actée le 25/08/2026
par le commanditaire, Android et iOS confirmés.

Ordre de construction : **l'API Laravel d'abord**, la PWA existante branchée
dessus comme solution de transition pendant le développement Flutter. Détail
dans `docs/DECISIONS.md` (ADR-004 et ADR-005).

### Historique de cette décision (conservé volontairement)

Elle a été rouverte une fois. Le raisonnement mérite d'être gardé.

L'examen du dossier fourni par le client (voir `docs/ANALYSE-BUILD-PWA.md`) a
révélé qu'il ne s'agit pas d'un projet Android natif mais d'une **PWA
hors-ligne déjà fonctionnelle**, transformable en `.apk` gratuitement via
PWABuilder. La PWA n'est donc plus une hypothèse à écarter : elle existe.

Le choix Flutter / PWA ne porte que sur **l'enveloppe**. Tout ce qui manque au
projet — comptes, classement réel, questions atteignant le pasteur, IA
sécurisée, cache mutualisé — vit **côté serveur**, à l'identique dans les deux
scénarios.

Le report a été levé le même jour : le commanditaire a confirmé Android **et**
iOS avec Flutter et Laravel. La PWA n'est donc plus une cible finale, mais elle
reste précieuse comme référence visuelle et comme solution de transition.

---

## 10. Journal des décisions

| Date | Décision | Conséquence |
|------|----------|-------------|
| 2026-08-25 | Android **et** iOS | Impose un socle multiplateforme (Flutter) |
| 2026-08-25 | Plus de 2 000 membres | Vraie base de données, quotas IA, modération |
| 2026-08-25 | Un pasteur répondant identifié | On construit l'espace pastoral — mais voir le risque n°1 |
| 2026-08-25 | Dons : affichage des numéros en phase 1 | Modèle de données conçu pour accueillir de vrais dons en phase 2 |
| 2026-08-25 | Le « dossier Android » est en fait une **PWA fonctionnelle** | Rouvre le choix d'enveloppe ; un APK est atteignable en quelques jours |
| 2026-08-25 | **Stack finale : Flutter + Laravel**, Android et iOS | Décision du commanditaire — voir ADR-004 |
| 2026-08-25 | **Construire l'API Laravel en premier** | La PWA s'y branche en transition — voir ADR-005 |
| 2026-08-25 | Dépôt Git unique pour `api/` et `mobile/` | Versions jamais désynchronisées — voir ADR-006 |
