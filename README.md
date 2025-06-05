# Lively - WordPress Theme Moderne

Lively est un thème WordPress moderne qui introduit un système de composants inspiré de Livewire, permettant une expérience de développement plus dynamique et interactive.

## 🚀 Concept

Lively repense l'approche traditionnelle du développement WordPress en introduisant un système de composants réactifs, similaire à Livewire. Cette approche permet de :

- Créer des composants interactifs sans avoir à écrire de JavaScript complexe
- Maintenir une structure de code propre et modulaire
- Bénéficier d'une expérience de développement moderne tout en restant dans l'écosystème WordPress

## 🏗️ Architecture

Le thème est construit avec une architecture moderne utilisant :

- **Vite** comme bundler pour une expérience de développement rapide
- **Sass** pour une gestion avancée des styles
- Un système de composants personnalisé pour la réactivité

### Structure des dossiers

```
lively/
├── src/
│   ├── js/          # JavaScript et composants
│   │   ├── hooks/   # Hooks WordPress personnalisés
│   │   └── lib/     # Bibliothèques et utilitaires
│   └── scss/        # Styles Sass
├── dist/            # Fichiers compilés
└── cli/            # Outils de développement
```

## 🛠️ Installation

1. Clonez le thème dans votre dossier `wp-content/themes/`
2. Installez les dépendances :
   ```bash
   yarn install
   ```
3. Lancez le serveur de développement :
   ```bash
   yarn dev
   ```

## 📦 Scripts disponibles

- `yarn dev` : Lance le serveur de développement Vite
- `yarn watch` : Compile les assets en mode watch
- `yarn build` : Compile les assets pour la production

## 🎯 Système de composants

Le système de composants de Lively s'inspire de Livewire pour offrir :

- Des composants réactifs côté serveur
- Une mise à jour automatique du DOM
- Une gestion d'état simplifiée
- Une intégration transparente avec WordPress

## 🔧 Développement

Pour contribuer au développement :

1. Créez une branche pour votre fonctionnalité
2. Développez en utilisant `yarn dev`
3. Testez vos modifications
4. Soumettez une pull request

## 📝 Licence

ISC - Développé par Martin IS IT Services

