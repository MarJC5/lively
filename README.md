# Lively - Modern WordPress Theme

Lively is a modern WordPress theme that introduces a component system inspired by Livewire, enabling a more dynamic and interactive development experience.

## 🚀 Concept

Lively rethinks the traditional WordPress development approach by introducing a reactive component system, similar to Livewire. This approach allows you to:

- Create interactive components without writing complex JavaScript
- Maintain a clean and modular code structure
- Benefit from a modern development experience while staying within the WordPress ecosystem

## 🏗️ Architecture

The theme is built with a modern architecture using:

- **Vite** as a bundler for a fast development experience
- **Sass** for advanced style management
- A custom component system for reactivity

### Directory Structure

```
lively/
├── src/
│   ├── js/          # JavaScript and components
│   │   ├── hooks/   # Custom WordPress hooks
│   │   └── lib/     # Libraries and utilities
│   └── scss/        # Sass styles
├── dist/            # Compiled files
└── cli/            # Development tools
```

## 🛠️ Installation

1. Clone the theme into your `wp-content/themes/` folder
2. Install dependencies:
   ```bash
   yarn install
   ```
3. Start the development server:
   ```bash
   yarn dev
   ```

## 📦 Available Scripts

- `yarn dev` : Starts the Vite development server
- `yarn watch` : Compiles assets in watch mode
- `yarn build` : Compiles assets for production

## 🎯 Component System

Lively's component system is inspired by Livewire to provide:

- Server-side reactive components
- Automatic DOM updates
- Simplified state management
- Seamless WordPress integration

## 🔧 Development

To contribute to development:

1. Create a branch for your feature
2. Develop using `yarn dev`
3. Test your changes
4. Submit a pull request

## Production

Once the theme is compiled, simply copy the dist folder to `wp-content/themes/` and select the theme in the WordPress administration panel.

## 📝 License

MIT - Developed by Martin IS IT Services

