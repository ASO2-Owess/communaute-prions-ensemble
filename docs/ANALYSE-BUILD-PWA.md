# Analyse du dossier « android_build » — ce que c'est réellement

Analysé le 25 août 2026, dossier `android_build_Communauté Prions Ensemble/`.

---

## 1. Ce n'est pas un projet Android

Le dossier ne contient ni Kotlin, ni Java, ni `build.gradle`, ni `AndroidManifest.xml`.

```
android_build_Communauté Prions Ensemble/
├── index.html              4,67 Mo   l'application entière
├── manifest.json             674 o   carte d'identité de l'app
├── sw.js                   1,6 Ko    service worker (cache hors-ligne)
├── icon-192.png            1,57 Mo   ← voir §4.1
├── icon-512.png               2 Ko
├── icon-maskable-512.png      2 Ko   ← voir §4.2
├── img/                              3 photos, 177 Ko au total
└── LISEZ-MOI.md
```

C'est une **PWA** — *Progressive Web App*, une application web installable.

**À comprendre absolument.** Une PWA est un site web qui, grâce à deux fichiers,
se comporte comme une application installée :

- `manifest.json` est sa **carte d'identité** : nom, icône, couleurs, mode
  d'affichage. C'est ce qui permet à Android de proposer « Installer
  l'application » et de créer une icône sur l'écran d'accueil.
- `sw.js` (*service worker*) est un **programme qui s'installe dans le
  navigateur** et s'intercale entre l'application et le réseau. À la première
  visite, il copie tous les fichiers dans un cache local. Aux visites suivantes,
  il sert ces fichiers depuis le cache — d'où le fonctionnement hors ligne, même
  en mode avion.

Le `LISEZ-MOI.md` prévoit de transformer ce dossier en `.apk` via **PWABuilder**
(outil gratuit de Microsoft) : PWABuilder emballe la PWA dans une coquille
Android et produit un fichier installable. La démarche est correcte et
fonctionne.

## 2. Une vraie amélioration par rapport à la maquette

Ce build corrige un des défauts majeurs relevés dans `ANALYSE-PROTOTYPE.md` :

```js
/* Stockage : utilise l'API hôte si présente, sinon localStorage */
if(!window.storage){
  window.storage = {
    async get(k){ ... localStorage.getItem('cpe:'+k) ... },
    async set(k,v){ ... localStorage.setItem('cpe:'+k, v) ... }
  };
}
```

C'est un **adaptateur** (*shim*) : si l'API du bac à sable n'existe pas, il en
fabrique une équivalente au-dessus de `localStorage`, le stockage standard du
navigateur. Le reste du code n'a pas eu besoin d'être touché.

C'est une bonne pratique et elle mérite d'être nommée : le code appelle
`window.storage`, **jamais `localStorage` directement**. Le jour où l'on
remplacera le stockage par une base de données ou par une synchronisation
serveur, il n'y aura qu'un seul endroit à modifier. C'est exactement la
« séparation des responsabilités » de nos conventions, appliquée au stockage.

**Conséquence :** notes, progression, questions et profil sont désormais
réellement sauvegardés sur l'appareil.

## 3. Ce qui reste cassé

### 3.1 L'appel à l'IA échouera toujours

```js
const response = await fetch("https://api.anthropic.com/v1/messages", {
  headers: { "Content-Type": "application/json" },   // aucune clé
  ...
});
```

Inchangé depuis la maquette. Une fois hors du bac à sable Claude, chaque appel
sera refusé. Concrètement, dès la mise en ligne :

- Méditations sur les chapitres non rédigés à la main → **indisponibles**
- Biographies des ~60 personnages bibliques → **indisponibles**
- Quiz généré sur un chapitre lu → **indisponible**

Reste fonctionnel : la Bible intégrale, les 7 méditations écrites à la main, le
quiz statique de 35 questions, le bloc-notes, l'agenda, les encouragements.

Le `sw.js` prend soin d'exclure `api.anthropic.com` du cache pour que ces appels
passent toujours par le réseau — le raisonnement est juste, mais il protège un
appel qui ne peut pas aboutir.

**Ceci ne se corrige pas en ajoutant une clé dans le fichier** (voir
`ANALYSE-PROTOTYPE.md` §5.2). Il faut le serveur intermédiaire.

### 3.2 Toujours pas de communauté

Le classement affiche les mêmes 12 noms codés en dur. Les questions au pasteur
sont stockées dans le `localStorage` du téléphone et n'atteignent personne.
Aucun compte utilisateur, donc aucune récupération des données en changeant
d'appareil.

### 3.3 `localStorage` va finir par saturer, en silence

`localStorage` est plafonné à environ **5 à 10 Mo** par site selon le
navigateur. Or l'application y stocke aussi les contenus générés
(`med:`, `bio:`, `chapquiz:`). Avec 1 189 chapitres possibles, le plafond sera
atteint.

Et voici le vrai problème :

```js
async set(k,v){ try{ localStorage.setItem('cpe:'+k, v); }catch(e){} }
```

`catch(e){}` — l'erreur est **avalée sans un mot**. Quand le quota sera dépassé,
l'application continuera de fonctionner en apparence, mais les notes ne seront
plus enregistrées et personne ne le saura.

**Règle professionnelle à retenir :** un `catch` vide est presque toujours un
bug en attente. Une erreur qu'on choisit d'ignorer doit au minimum être
enregistrée quelque part, et l'utilisateur averti si ça le concerne. Ici, perdre
silencieusement les notes personnelles de quelqu'un est un vrai préjudice.

## 4. Trois défauts concrets, corrigeables tout de suite

### 4.1 `icon-192.png` fait 1,57 Mo et mesure 1254 × 1254 px

| Fichier | Taille déclarée | Taille réelle | Poids |
|---------|-----------------|---------------|-------|
| `icon-192.png` | 192 × 192 | **1254 × 1254** | **1 568 Ko** |
| `icon-512.png` | 512 × 512 | 512 × 512 | 2 Ko |

Le `manifest.json` annonce du 192×192 ; le fichier n'en est pas un. Il est aussi
utilisé comme favicon et comme icône Apple, donc **1,5 Mo téléchargés à chaque
démarrage à froid** — sur une connexion mobile facturée à la donnée, pour une
image qui sera affichée en 192 pixels. À régénérer à la bonne dimension.

### 4.2 L'icône « maskable » n'en est pas une

`icon-512.png` et `icon-maskable-512.png` sont **strictement le même fichier**
(empreinte MD5 identique).

Une icône *maskable* doit prévoir une marge de sécurité : Android découpe les
icônes selon la forme choisie par le fabricant (ronde, carré arrondi, goutte).
Sans cette marge, le logo sera rogné sur ses bords. À régénérer avec le motif
centré dans environ 80 % de la surface.

### 4.3 La procédure de mise à jour du `LISEZ-MOI` ne fonctionnera pas

Le fichier indique : « il suffit de remplacer `index.html` ». C'est faux, et
c'est un piège classique des service workers.

```js
const CACHE_NAME = "prions-ensemble-v2";
```

Le service worker sert le cache **en priorité** sur le réseau. Tant que
`CACHE_NAME` ne change pas, les utilisateurs continueront de voir l'ancienne
version, indéfiniment. Il faut **incrémenter ce nom à chaque publication**
(`v3`, `v4`…) : l'événement `activate` supprime alors les anciens caches et la
nouvelle version est chargée.

À corriger dans le `LISEZ-MOI`, sinon la première mise à jour ne parviendra
à personne.

## 5. Ce que ce dossier change pour l'architecture

Il **rouvre honnêtement** le choix de stack, qui semblait tranché.

Ce qui était vrai avant : « la PWA est écartée car le hors-ligne y est fragile
et les notifications peu fiables sur iOS ».

Ce qui est vrai maintenant : **une PWA hors-ligne fonctionnelle existe déjà**.
Elle n'est pas hypothétique. Elle contient la Bible intégrale, elle persiste ses
données, et PWABuilder peut en produire un `.apk` cette semaine, gratuitement.

### La question de fond n'est pas celle qu'on croyait

Le choix Flutter / PWA porte uniquement sur **l'enveloppe** de l'application. Or
tout ce qui manque au projet — comptes, classement réel, questions qui
atteignent le pasteur, génération IA sécurisée, cache mutualisé — se trouve
**du même côté dans les deux cas : le serveur.**

```
                    ┌─────────────────────────────┐
   PWA existante ──►│                             │
        ou          │   Serveur Laravel           │──► API Claude
   App Flutter   ──►│   comptes · classement      │    (clé protégée)
                    │   questions · cache IA      │
                    └─────────────────────────────┘
```

**Le serveur est nécessaire dans les deux scénarios, et il est identique dans
les deux scénarios.** C'est donc par lui qu'il faut commencer : c'est le seul
travail dont on est certain qu'il ne sera pas jeté, quelle que soit la décision
finale sur l'enveloppe.

Repousser le choix Flutter/PWA n'est pas de l'indécision : c'est éviter de
trancher trop tôt une question dont la réponse dépend d'informations qu'on n'a
pas encore (budget, importance réelle d'iOS, délai attendu).

### Ce qu'il faut savoir pour trancher plus tard

| Critère | PWA + PWABuilder | Flutter |
|---------|------------------|---------|
| Délai pour un APK Android | **quelques jours** | plusieurs semaines |
| Coût de développement | **quasi nul** (existe déjà) | réécriture complète de l'interface |
| Distribution iOS | manuelle (Partager → Ajouter à l'écran d'accueil), pas d'App Store simple | App Store standard |
| Hors-ligne | fonctionnel, mais soumis à l'éviction du cache par Safari sur iOS | maîtrisé |
| Notifications push | possibles sur Android ; sur iOS uniquement si l'utilisateur a installé la PWA | fiables partout |
| Stockage | à migrer de `localStorage` vers IndexedDB (quota bien supérieur) | base locale native |

## 6. Recommandation

1. **Corriger les trois défauts du §4** — moins d'une heure, gain immédiat.
2. **Ne pas encore publier l'APK** tant que les fonctions IA échouent
   silencieusement : mieux vaut les masquer que promettre ce qui ne marche pas.
3. **Commencer par le serveur Laravel.** Il est requis dans tous les scénarios.
   Premier périmètre : comptes utilisateurs, questions au pasteur avec espace de
   réponse, relais IA avec cache mutualisé.
4. **Brancher la PWA existante sur ce serveur** — elle devient alors une vraie
   application communautaire, sans avoir attendu Flutter.
5. **Trancher Flutter vs PWA plus tard**, avec le budget, le délai et le poids
   réel d'iOS en main.

## 7. Point de convention à régler

Deux dossiers ne respectent pas nos règles de nommage (`CONVENTIONS.md` §1) :

- `android_build_Communauté Prions Ensemble/` — espaces et accents
- `Communauté Prions Ensemble/` — idem, et vide

Les espaces et accents cassent les commandes en ligne de commande, les URL et
les outils de build. À renommer et à regrouper sous
`communaute-prions-ensemble/`, avec le build dans un sous-dossier `pwa/`.
