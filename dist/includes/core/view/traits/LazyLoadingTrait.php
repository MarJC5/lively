<?php

namespace Lively\Core\View\Traits;

/**
 * Trait for component lazy loading
 */
trait LazyLoadingTrait
{
    protected $lazyLoad = false; // Whether this component should be lazy loaded
    protected $lazyLoadThreshold = 200; // Number of pixels from viewport to start loading
    
    /**
     * Enable lazy loading for this component
     * 
     * @param int $threshold Lazy loading threshold in pixels from viewport
     * @return self
     */
    public function lazy(int $threshold = 200): self {
        $this->lazyLoad = true;
        $this->lazyLoadThreshold = $threshold;
        return $this;
    }
    
    /**
     * Check if this component should be lazy loaded
     * 
     * @return bool
     */
    public function isLazy(): bool {
        return $this->lazyLoad;
    }
    
    /**
     * Get lazy loading threshold
     * 
     * @return int
     */
    public function getLazyLoadThreshold(): int {
        return $this->lazyLoadThreshold;
    }
    
    /**
     * Render a lazy loading placeholder for this component
     * 
     * @return string Lazy loading HTML wrapper
     */
    protected function renderLazy(): string {
        $placeholderHeight = $this->props['placeholder_height'] ?? 'auto';
        $placeholderWidth = $this->props['placeholder_width'] ?? '100%';
        $placeholderClass = $this->props['placeholder_class'] ?? 'lazy-component-placeholder';
        
        // Component metadata for hydration
        $hydrationData = htmlspecialchars(json_encode([
            'id' => $this->id,
            'class' => $this->getFullComponentClass(),
            'props' => $this->getProps(),
            'state' => $this->getState()
        ]), ENT_QUOTES, 'UTF-8');
        
        $threshold = $this->getLazyLoadThreshold();
        
        // Generate the lazy loading wrapper
        $output = <<<HTML
<div 
    id="lazy-{$this->id}" 
    class="lazy-component" 
    data-component="{$hydrationData}"
    style="min-height: {$placeholderHeight}; width: {$placeholderWidth};"
>
    <div class="{$placeholderClass}">
        <!-- Placeholder content -->
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup the Intersection Observer to load this component when visible
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const lazyComponent = entry.target;
                            const componentData = JSON.parse(lazyComponent.dataset.component);
                            
                            // Request the component content
                            fetch('/lively/component/load', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify(componentData)
                            })
                            .then(response => response.text())
                            .then(html => {
                                // Replace placeholder with actual component
                                const wrapper = document.createElement('div');
                                wrapper.innerHTML = html;
                                lazyComponent.parentNode.replaceChild(wrapper.firstElementChild, lazyComponent);
                                
                                // Dispatch event to notify the component is loaded
                                document.dispatchEvent(new CustomEvent('component:loaded', { 
                                    detail: { id: componentData.id } 
                                }));
                            })
                            .catch(error => {
                                console.error('Error loading lazy component:', error);
                            });
                            
                            // Stop observing the element once it's loaded
                            observer.unobserve(lazyComponent);
                        }
                    });
                },
                { rootMargin: '{$threshold}px' }
            );
            
            // Start observing the lazy component
            observer.observe(document.getElementById('lazy-{$this->id}'));
        });
    </script>
</div>
HTML;

        return $output;
    }
} 