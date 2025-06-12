<?php

namespace Lively\Resources\Components;

use Lively\Core\View\Component;

/**
 * Pagination Component
 * 
 * A component for displaying pagination controls with previous/next navigation and page information.
 * Can be used for any paginated content like search results, blog posts, or lists.
 * 
 * @example
 * ```php
 * // Basic usage
 * new Pagination([
 *     'currentPage' => 1,
 *     'lastPage' => 5
 * ]);
 * 
 * // Usage with custom text
 * new Pagination([
 *     'currentPage' => 2,
 *     'lastPage' => 10,
 *     'prevText' => '← Previous',
 *     'nextText' => 'Next →'
 * ]);
 * 
 * // Usage with additional query parameters
 * new Pagination([
 *     'currentPage' => 1,
 *     'lastPage' => 3,
 *     'queryParams' => ['category' => 'news', 'sort' => 'date']
 * ]);
 * 
 * // Digg-style windowed pagination
 * new Pagination([
 *     'currentPage' => 5,
 *     'lastPage' => 10,
 *     'window' => 2
 * ]);
 * ```
 * 
 * @property int $currentPage The current page number
 * @property int $lastPage The last page number
 * @property int $total Optional total number of items
 * @property int $perPage Optional number of items per page
 * @property array $queryParams Optional additional URL parameters to preserve
 * @property int $window Number of pages to show on each side of current (default 2)
 * @property bool $showInfo Whether to show the page information
 * @property bool $showPrevNext Whether to show previous/next buttons
 * @property string $prevText Text for the previous button
 * @property string $nextText Text for the next button
 * @property string $infoText Format string for page information
 * 
 * @view
 */
class Pagination extends Component {
    /**
     * Initialize the component state
     * 
     * @return void
     */
    protected function initState() {
        $this->setState('currentPage', $this->getProps('currentPage') ?? 1);
        $this->setState('lastPage', $this->getProps('lastPage') ?? 1);
        $this->setState('total', $this->getProps('total') ?? 0);
        $this->setState('perPage', $this->getProps('perPage') ?? 10);
        $this->setState('queryParams', $this->getProps('queryParams') ?? []);
        $this->setState('window', $this->getProps('window') ?? 2);
        $this->setState('showInfo', $this->getProps('showInfo') ?? false);
        $this->setState('showPrevNext', $this->getProps('showPrevNext') ?? true);
        $this->setState('prevText', $this->getProps('prevText') ?? '<');
        $this->setState('nextText', $this->getProps('nextText') ?? '>');
        $this->setState('firstText', $this->getProps('firstText') ?? '«');
        $this->setState('lastText', $this->getProps('lastText') ?? '»');
        $this->setState('infoText', $this->getProps('infoText') ?? 'Page %1$s of %2$s');
    }
    
    /**
     * Render the pagination component
     * 
     * @return string The rendered HTML
     */
    public function render() {
        if ($this->getState('lastPage') <= 1) {
            return '';
        }

        $currentPage = (int)$this->getState('currentPage');
        $lastPage = (int)$this->getState('lastPage');
        $queryParams = $this->getState('queryParams');
        $window = (int)$this->getState('window');

        // Build query string helper
        $qs = function($page) use ($queryParams) {
            $params = array_merge($queryParams, ['page' => $page]);
            return '?' . http_build_query($params);
        };

        $html = '<nav class="lively-component pagination" lively:component="' . $this->getId() . '" role="navigation" aria-label="Pagination">';
        $html .= '<ul class="pagination__list">';

        // First page button
        $html .= '<li>';
        if ($currentPage > 1) {
            $html .= '<a href="' . $qs(1) . '" class="pagination__first" aria-label="First">' . $this->getState('firstText') . '</a>';
        } else {
            $html .= '<span class="pagination__first pagination__disabled">' . $this->getState('firstText') . '</span>';
        }
        $html .= '</li>';

        // Previous page button
        $html .= '<li>';
        if ($currentPage > 1) {
            $html .= '<a href="' . $qs($currentPage - 1) . '" class="pagination__prev" aria-label="Previous">' . $this->getState('prevText') . '</a>';
        } else {
            $html .= '<span class="pagination__prev pagination__disabled">' . $this->getState('prevText') . '</span>';
        }
        $html .= '</li>';

        // Page number window
        $start = max(1, $currentPage - $window);
        $end = min($lastPage, $currentPage + $window);
        for ($i = $start; $i <= $end; $i++) {
            $html .= '<li>';
            if ($i == $currentPage) {
                $html .= '<span class="pagination__page pagination__current">' . $i . '</span>';
            } else {
                $html .= '<a href="' . $qs($i) . '" class="pagination__page">' . $i . '</a>';
            }
            $html .= '</li>';
        }

        // Next page button
        $html .= '<li>';
        if ($currentPage < $lastPage) {
            $html .= '<a href="' . $qs($currentPage + 1) . '" class="pagination__next" aria-label="Next">' . $this->getState('nextText') . '</a>';
        } else {
            $html .= '<span class="pagination__next pagination__disabled">' . $this->getState('nextText') . '</span>';
        }
        $html .= '</li>';

        // Last page button
        $html .= '<li>';
        if ($currentPage < $lastPage) {
            $html .= '<a href="' . $qs($lastPage) . '" class="pagination__last" aria-label="Last">' . $this->getState('lastText') . '</a>';
        } else {
            $html .= '<span class="pagination__last pagination__disabled">' . $this->getState('lastText') . '</span>';
        }
        $html .= '</li>';

        $html .= '</ul>';

        // Optional info
        if ($this->getState('showInfo')) {
            $html .= '<span class="pagination__info">' . sprintf($this->getState('infoText'), $currentPage, $lastPage) . '</span>';
        }

        $html .= '</nav>';
        return $html;
    }
}

// Only return a component instance if file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') == basename(__FILE__)) {
    return new Pagination();
}