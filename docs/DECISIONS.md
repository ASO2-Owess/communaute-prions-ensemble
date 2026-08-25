# Décisions techniques — Communauté Prions Ensemble

Une entrée par décision importante : le contexte, l'option retenue, les
alternatives écartées et pourquoi. Ce fichier explique le **pourquoi**, que le
code ne dira jamais.

---

## ADR-001 — Plateformes visées : Android et iOS

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Le public visé est une communauté chrétienne d'Afrique de l'Ouest.
Android y domine largement, mais une partie des membres, notamment la diaspora,
utilise iOS.

**Décision.** Livrer sur **Android et iOS**.

**Conséquences.** Impose un socle multiplateforme (voir ADR-004). Écarte la PWA
comme cible finale, l'installation et le hors-ligne y étant nettement plus
fragiles sur iOS.

---

## ADR-002 — Un serveur intermédiaire est obligatoire

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Le prototype appelle directement l'API de Claude depuis le
navigateur :

```js
fetch("https://api.anthropic.com/v1/messages", {
  headers: { "Content-Type": "application/json" },   // aucune clé
  ...
})
```

Cela ne fonctionne que dans le bac à sable Claude, qui injecte l'authentification.
Le réflexe naturel — « ajouter la clé dans le code » — est le piège à éviter.

**Décision.** L'application ne contacte **jamais** l'API de l'IA directement.
Elle passe par une API Laravel qui détient seule la clé.

**Justification.** Une application est téléchargée sur l'appareil de
l'utilisateur : son contenu est extractible par inspection du trafic réseau ou
décompilation. Une clé dans le code n'est pas cachée, seulement discrète.
Quelqu'un la récupère et consomme le budget IA sans limite.

**Bénéfices supplémentaires :**
1. **Cache mutualisé** — une méditation générée une fois, servie à tous. Coût
   divisé par le nombre d'utilisateurs.
2. **Limitation des abus** — quota d'appels par utilisateur et par jour.
3. **Validation humaine avant publication** — sur un sujet religieux, une
   approximation doctrinale coûte la confiance de la communauté.

**Alternatives écartées.**
- *Clé dans l'application* — faille de sécurité, budget non maîtrisable.
- *Renoncer aux fonctions IA* — supprimerait méditations dynamiques,
  biographies et quiz par chapitre, soit la majorité de la valeur ajoutée.

**Note.** Le serveur est de toute façon requis pour les comptes, le classement et
les questions au pasteur. Ce n'est donc pas une contrainte ajoutée, mais la pièce
centrale qui manquait.

---

## ADR-003 — Le texte biblique reste embarqué, les contenus IA vivent côté serveur

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Deux natures de contenu très différentes cohabitent : un texte
biblique figé de 4,34 Mo, et des contenus générés à la demande.

**Décision.**

| Contenu | Où | Pourquoi |
|---------|-----|---------|
| Texte biblique | Embarqué dans l'app | Ne change jamais ; le hors-ligne est une contrainte forte |
| Méditations, biographies, quiz générés | Serveur, avec cache local | Identiques pour tous → à mutualiser ; doivent être relus avant publication |
| Notes, progression, lectures | Local **et** serveur (synchronisé) | Utilisables hors ligne, mais doivent survivre au changement d'appareil |

**Alternative écartée.** *Télécharger la Bible au premier lancement* — allège
l'installation, mais rend la première utilisation dépendante du réseau, ce qui
contredit la promesse du produit. À réexaminer si le poids de l'app pose
problème sur les magasins d'applications.

---

## ADR-004 — Stack : Flutter + Laravel

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Android et iOS (ADR-001), hors-ligne obligatoire, plus de 2 000
membres, comptes utilisateurs, back-office pastoral, clé d'API à protéger
(ADR-002).

**Décision.** **Flutter** pour l'application mobile, **Laravel** pour l'API.

**Justification.**
- *Flutter* produit les deux applications à partir d'un seul code, gère
  nativement le stockage local qu'exige le hors-ligne, et permet de reproduire
  fidèlement l'identité visuelle déjà définie.
- *Laravel* fournit rapidement authentification, base de données, back-office
  pour le pasteur, et sert de relais protégeant la clé de l'IA.
- Ce sont les deux piliers technologiques de l'atelier (`CONVENTIONS.md` §3) :
  compétence capitalisée d'un projet à l'autre.

**Alternatives écartées.**
- *PWA + PWABuilder* — une PWA fonctionnelle **existe déjà** et produirait un
  APK en quelques jours pour un coût quasi nul. Écartée comme cible finale car
  l'installation sur iOS est manuelle et peu adoptée, le cache y est sujet à
  éviction par Safari, et `localStorage` sature. **Elle reste utile comme
  solution de transition** (voir ADR-005).
- *Site web classique* — renonce au hors-ligne, contrainte forte du projet.
- *Deux applications natives (Kotlin + Swift)* — double le coût de
  développement et de maintenance sans bénéfice pour ce type d'application.
- *React Native* — techniquement viable, mais Flutter est le pilier de
  l'atelier ; changer sans bénéfice concret contredirait `CONVENTIONS.md` §3.

---

## ADR-005 — Commencer par l'API, la PWA sert de transition

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Flutter demande plusieurs semaines avant un premier livrable. Une
PWA fonctionnelle existe déjà et peut être branchée sur une API bien plus vite.

**Décision.** Construire **l'API Laravel en premier**. La PWA existante s'y
branche pour valider l'API en conditions réelles pendant que l'app Flutter est
développée.

**Justification.** L'API est identique quel que soit le client : c'est le seul
travail dont on est certain qu'il ne sera pas jeté. La brancher d'abord sur la
PWA permet de vérifier ses choix avec de vrais utilisateurs, sans attendre.

**Premier périmètre de l'API :** comptes utilisateurs, questions au pasteur avec
espace de réponse, relais IA avec cache mutualisé.

---

## ADR-006 — Dépôt unique pour `api/` et `mobile/`

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Le projet comporte deux applications qui se déploient séparément.

**Décision.** Un **seul dépôt Git** à la racine du projet.

**Justification.** Les deux parties évoluent ensemble : une modification d'API et
son usage côté mobile se relisent dans le même commit, et les versions ne peuvent
pas se désynchroniser.

**Prix à payer.** Déploiement plus attentif — ne déployer que le dossier
concerné. Acceptable pour une équipe réduite.

**À réexaminer si** deux équipes distinctes travaillent en parallèle sur les deux
parties.

---

## ADR-007 — Le schéma prévoit plusieurs répondants dès le départ

**Date :** 2026-08-25 · **Statut :** actée

**Contexte.** Plus de 2 000 membres pour un seul pasteur répondant. À 10 % de
participation, cela représente près de 7 questions par jour — intenable.

**Décision.** Modéliser la relation « question → répondant » comme une relation
vers un **rôle**, pas vers une personne unique. Prévoir dès la conception le
pré-tri des questions et une FAQ alimentée par les réponses déjà données.

**Justification.** On ne construit pas pour un besoin futur imaginaire — mais on
laisse la porte ouverte quand la fermer coûte cher. Ici, passer d'un répondant à
plusieurs après coup impliquerait une migration de base de données et une reprise
de toute la logique de l'espace pastoral.

**Ce qu'on ne fait PAS maintenant :** interface de gestion d'équipe, attribution
automatique, statistiques par répondant. Un seul répondant à ce jour.
