<?php

namespace Lively\Cli\Commands;

use Lively\Cli\Command;
use Lively\Cli\CommandInterface;

class MakeComposantCommand extends Command implements CommandInterface
{
    protected string $name = 'make:composant';
    protected string $description = 'Create a new component';

    public function handle(array $args = []): void
    {
        if (empty($args[0])) {
            $this->error('Component name is required');
            return;
        }

        $componentName = $args[0];
        $resourceFolder = $args[1] ?? null;

        // Create component directory if it doesn't exist
        $componentPath = \Lively\Cli\Cli::getInstance()->getResourcePath() . "/components";
        if ($resourceFolder) {
            $componentPath .= "/{$resourceFolder}";
            if (!is_dir($componentPath)) {
                mkdir($componentPath, 0755, true);
            }
        }

        // Create component files
        $this->createComponentFiles($componentPath, $componentName);
        
        $successMessage = $resourceFolder 
            ? "Component {$componentName} created successfully in {$resourceFolder} folder!"
            : "Component {$componentName} created successfully!";
        
        $this->success($successMessage);
    }

    protected function createComponentFiles(string $path, string $name): void
    {
        // Create component.php
        $namespace = 'Lively\Resources\Components';
        $className = $name;
        if (strpos($path, '/components/') !== false) {
            // Extract the folder structure after /components/
            $folderPath = substr($path, strpos($path, '/components/') + 12);
            if ($folderPath) {
                $namespace .= '\\' . str_replace('/', '\\', $folderPath);
                $className = $folderPath . '/' . $name;
            }
        }

        $componentContent = <<<PHP
<?php

namespace {$namespace};

use Lively\Core\View\Component;

/**
 * @view
 */
class {$name} extends Component {
    protected function initState() {
        // Initialize your component state here
    }
    
    public function render() {
        return <<<HTML
        <div class="lively-component" lively:component="{\$this->getId()}" role="region" aria-label="{$name}">
            <!-- Your component HTML here -->
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename(\$_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new {$name}();
}
PHP;
        file_put_contents("{$path}/{$name}.php", $componentContent);
    }
} 