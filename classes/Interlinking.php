<?php

namespace PalPalych\AutoInterlinking\Classes;

use Illuminate\Cache\TaggableStore;
use Cache;
use Request;
use DOMXPath;
use DOMDocument;
use PalPalych\AutoInterlinking\Models\Keyword;
use PalPalych\AutoInterlinking\Models\Settings;

class Interlinking
{
    /**
     * @var string The content to be processed.
     */
    protected $content;

    public const CACHE_TAG = 'palpalych.autointerlinking';

    protected Settings $settings;

    /**
     * @var int The number of replacements made.
     */
    protected $replacementsCount = 0;

    /**
     * Interlinking constructor.
     * @param string $content
     */
    public function __construct($content)
    {
        $this->content = $content;
        $this->settings = Settings::instance();
    }

    /**
     * Renders the content with interlinks.
     * @return string
     */
    public function render()
    {
        if (!$this->settings->cache_enabled) {
            return $this->processContent();
        }

        $cacheKey = self::CACHE_TAG . '.' . md5($this->content);
        $store = Cache::getStore();

        if ($store instanceof TaggableStore) {
            return Cache::tags(self::CACHE_TAG)->remember($cacheKey, $this->settings->cache_lifetime, function () {
                return $this->processContent();
            });
        }

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $processedContent = $this->processContent();
        Cache::put($cacheKey, $processedContent, $this->settings->cache_lifetime);

        return $processedContent;
    }

    protected function processContent(): string
    {
        if ($this->isPageExcluded()) {
            return $this->content;
        }

        $keywords = Keyword::where('active', true)->get();
        if ($keywords->isEmpty()) {
            return $this->content;
        }

        // Sort keywords by length descending to match longer phrases first
        $keywords = $keywords->sortByDesc(function ($keyword) {
            return strlen($keyword->keyword);
        });

        foreach ($keywords as $keyword) {
            $this->processKeyword($keyword);
        }

        return $this->content;
    }

    protected function processKeyword(Keyword $model)
    {
        $maxReplacements = (int) $this->settings->max_replacements_by_page;

        if ($maxReplacements > 0 && $this->replacementsCount >= $maxReplacements) {
            return;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $this->content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        $excludedTags = $this->getExcludedTags();
        $queryParts = [];
        foreach ($excludedTags as $tag) {
            $queryParts[] = "not(ancestor::" . $tag . ")";
        }
        $query = "//text()[" . implode(' and ', $queryParts) . "]";
        $textNodes = $xpath->query($query);

        $pattern = '/\b(' . preg_quote($model->keyword, '/') . ')\b/iu';

        foreach ($textNodes as $node) {
            $textContent = $node->textContent;

            $newContent = preg_replace_callback(
                $pattern,
                function ($matches) use ($model) {
                    $maxReplacements = (int) $this->settings->max_replacements_by_page;
                    if ($maxReplacements > 0 && $this->replacementsCount >= $maxReplacements) {
                        return $matches[0];
                    }

                    $this->replacementsCount++;
                    $target = $model->getSetting('open_in_new_tab') ? ' target="_blank"' : '';
                    $class = $model->getSetting('css_class') ? ' class="' . e($model->getSetting('css_class')) . '"' : '';
                    $title = $model->getSetting('title') ? ' title="' . e($model->getSetting('title')) . '"' : '';
                    return '<a href="' . e($model->url) . '"' . $target . $class . $title . '>' . $matches[1] . '</a>';
                },
                $textContent
            );

            if ($newContent !== $textContent) {
                $fragment = $dom->createDocumentFragment();
                @$fragment->appendXML($newContent);
                $node->parentNode->replaceChild($fragment, $node);
            }
        }

        $this->content = $dom->saveHTML();
    }

    protected function isPageExcluded(): bool
    {
        $excludedPages = $this->settings->excluded_pages;
        if (empty($excludedPages) || !is_array($excludedPages)) {
            return false;
        }

        $currentPath = Request::path();

        foreach ($excludedPages as $page) {
            if (empty($page['path'])) {
                continue;
            }

            if (str_is($page['path'], $currentPath)) {
                return true;
            }
        }

        return false;
    }

    protected function getExcludedTags(): array
    {
        $tagMap = [
            'anchor' => 'a',
            'heading_one' => 'h1',
            'heading_two' => 'h2',
            'heading_three' => 'h3',
            'script' => 'script',
            'style' => 'style',
        ];

        $excludedTags = (array) $this->settings->excluded_html_tags;

        // Always ensure 'a' tags are excluded to prevent nested links
        if (!in_array('anchor', $excludedTags)) {
            $excludedTags[] = 'anchor';
        }

        $tagsToExclude = array_intersect_key($tagMap, array_flip($excludedTags));

        return array_values($tagsToExclude);
    }
}
