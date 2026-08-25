# Communauté Prions Ensemble — Version Android (hors-ligne)

Application développée par Dr. Stéphane NIAZALE.

## Contenu du dossier

- `index.html` — l'application complète (Bible intégrale des 66 livres, 1189 chapitres, 31 170 versets)
- `manifest.json` — carte d'identité de l'app (nom, icône, couleurs)
- `sw.js` — service worker qui met l'app en cache pour un fonctionnement 100% hors-ligne
- `icon-192.png`, `icon-512.png`, `icon-maskable-512.png` — icônes de l'application
- `img/` — photos de l'app (accueil, méditation, pasteur) : conserve ce dossier lors d'une mise à jour

## Fonctionne hors-ligne, sans connexion internet

- Bible complète (66 livres, texte intégral)
- Bloc-notes, agenda de méditation, encouragements
- Question au pasteur (mise en file d'attente locale)
- Faire un don (Wave / Orange Money : +225 07 09 13 85 75)
- Quiz classique (35 questions, mélangées aléatoirement à chaque partie)
- Les 7 méditations phares déjà rédigées (Genèse 1 et 3, Exode 20, Josué 1, Psaume 23, Luc 1, Jean 1, Matthieu 5)

## Nécessite une connexion internet (la première fois seulement)

- Méditation détaillée générée pour un chapitre non encore rédigé
- Biographies des personnages bibliques (Prophètes, Juges, Apôtres...)
- Quiz dynamique (nouvelles questions générées à la demande)

Une fois générés, ces contenus restent ensuite accessibles hors-ligne (mise en cache automatique).

---

## Option 1 — Installer directement sur Android (le plus simple, gratuit)

1. Copie le dossier entier sur ton téléphone (ou héberge-le en ligne, voir Option 2).
2. Ouvre `index.html` avec **Chrome** sur Android.
3. Menu Chrome (⋮) → **Ajouter à l'écran d'accueil** (ou "Installer l'application" si proposé).
4. Une icône apparaît sur ton téléphone : elle s'ouvre en plein écran, comme une vraie app, et fonctionne hors-ligne après la première ouverture.

## Option 2 — Obtenir un vrai fichier .apk installable (sans écrire de code)

Pour un .apk signé que tu peux distribuer ou publier sur le Play Store, utilise **PWABuilder** (outil gratuit, développé par Microsoft) :

1. **Héberge ces fichiers en ligne** (nécessaire pour PWABuilder) — options gratuites :
   - GitHub Pages (github.com → crée un dépôt → active "Pages")
   - Netlify Drop (netlify.com/drop → glisser-déposer le dossier)
   - Vercel, Firebase Hosting, ou ton propre hébergement web
2. Rends-toi sur **https://www.pwabuilder.com**
3. Colle l'URL de ton site hébergé (celle qui pointe vers `index.html`)
4. Clique sur **"Start"**, puis choisis **Android** dans les packages proposés
5. Télécharge le fichier **.apk** (ou **.aab** pour publier sur le Play Store) généré automatiquement
6. Installe-le sur un téléphone Android (active "Sources inconnues" dans les réglages si nécessaire)

PWABuilder lit `manifest.json` et `sw.js` déjà présents dans ce dossier — aucune configuration supplémentaire n'est nécessaire.

## Mise à jour du contenu

Pour ajouter de nouvelles méditations rédigées à la main, ou modifier le contenu, il suffit de remplacer `index.html` par une version mise à jour, en conservant les autres fichiers (`manifest.json`, `sw.js`, icônes).
