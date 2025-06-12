<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * @view
 */
class Breadcrumb extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $this->setState('separator', $this->getProps('separator') ?? '/');
    }

    protected function getItemsFromUrl() {
        $url = $_SERVER['REQUEST_URI'];
        $url = parse_url($url);
        $path = $url['path'];
        $path = explode('/', $path);
        $path = array_filter($path);
        $path = array_values($path);

        // Add home item at the beginning
        array_unshift($path, '/');

        return $path;
    }
    
    /**
     * Get post title from slug
     */
    protected function getPostTitle($slug, $fullPath = '') {
        // Try to get post by full path first
        if ($fullPath) {
            $post = get_page_by_path($fullPath, OBJECT, ['post', 'page']);
            if ($post) {
                return $post->post_title;
            }
        }
        
        // Fallback to single slug
        $post = get_page_by_path($slug, OBJECT, ['post', 'page']);
        if ($post) {
            return $post->post_title;
        }
        
        // If no post found, try to get term
        $term = get_term_by('slug', $slug, 'category');
        if ($term) {
            return $term->name;
        }
        
        return $slug;
    }
    
    /**
     * List items from the URL as HTML
     */
    protected function listItems() {
        $items = $this->getItemsFromUrl();
        $html = [];
        $currentPath = '';
        
        foreach ($items as $item) {
            if ($item === '/') {
                $html[] = '<li class="breadcrumb__item"><a href="/">' . __('Home') . '</a></li>';
            } else {
                $currentPath .= '/' . $item;
                $title = $this->getPostTitle($item, trim($currentPath, '/'));
                $html[] = '<li class="breadcrumb__item"><a href="' . $currentPath . '">' . $title . '</a></li>';
            }
        }

        // add separator between items but not after the last item
        foreach ($html as $key => $item) {
            if ($key < count($html) - 1) {
                $html[$key] .= '<span class="breadcrumb__separator">' . $this->getState('separator') . '</span>';
            }
        }

        return implode('', $html);
    }
    
    /**
     * Render the component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        return <<<HTML
        <div class="lively-component" lively:component="{$this->getId()}" role="region" aria-label="Breadcrumb">
            <ul class="breadcrumb" aria-label="Breadcrumb">
                {$this->listItems()}
            </ul>
        </div>
        HTML;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Breadcrumb();
}