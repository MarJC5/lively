<?php

namespace Lively\Resources\Components\Layouts;

use Lively\Core\View\Component;

/**
 * @view
 */
class App extends Component {
    protected function initState() {
        $this->setState('class', $this->getProps('class') ?? '');
        $this->setState('id', $this->getProps('id') ?? 'app');
    }
    
    /**
     * Render the icon component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        return <<<HTML
        <main id="{$this->getState('id')}" class="lively-component app {$this->getState('class')}" lively:component="{$this->getId()}" role="region" aria-label="App">
            {$this->getProps('children')}
        </main>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new App();
}