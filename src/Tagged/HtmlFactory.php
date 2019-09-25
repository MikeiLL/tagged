<?php
/**
 * This file is part of the Tagged package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Tagged;

use DecodeLabs\Tagged\Markup;
use DecodeLabs\Tagged\Buffer;
use DecodeLabs\Tagged\Builder\Html\ContentCollection;
use DecodeLabs\Tagged\Builder\Html\Tag;
use DecodeLabs\Tagged\Builder\Html\Element;

use DecodeLabs\Veneer\FacadeTarget;
use DecodeLabs\Veneer\FacadeTargetTrait;
use DecodeLabs\Veneer\FacadePluginAccessTarget;
use DecodeLabs\Veneer\FacadePluginAccessTargetTrait;
use DecodeLabs\Veneer\FacadePlugin;

class HtmlFactory implements Markup, FacadeTarget, FacadePluginAccessTarget
{
    use FacadeTargetTrait;
    use FacadePluginAccessTargetTrait;

    const FACADE = 'Html';

    const PLUGINS = [
        'parse'
    ];


    /**
     * Instance shortcut to el
     */
    public function __invoke(string $name, $content, array $attributes=null): Markup
    {
        return $this->el($name, $content, $attributes);
    }

    /**
     * Call named widget from instance
     */
    public function __call(string $name, array $args): Markup
    {
        return Element::create($name, ...$args);
    }

    /**
     * Dummy string generator to satisfy Markup dep
     */
    public function __toString(): string
    {
        return '';
    }



    /**
     * Stub to get empty plugin list to avoid broken targets
     */
    public function getFacadePluginNames(): array
    {
        return static::PLUGINS;
    }

    /**
     * Stub to avoid broken targets
     */
    public function loadFacadePlugin(string $name): FacadePlugin
    {
        if (!in_array($name, self::PLUGINS)) {
            throw Glitch::EInvalidArgument($name.' is not a recognised facade plugin');
        }

        $class = '\\DecodeLabs\\Tagged\\FactoryPlugins\\'.ucfirst($name);
        return new $class($this);
    }






    /**
     * Create a standalone tag
     */
    public function tag(string $name, array $attributes=null): Markup
    {
        return new Tag($name, $attributes);
    }

    /**
     * Create a standalone element
     */
    public function el(string $name, $content, array $attributes=null): Markup
    {
        return Element::create($name, $content, $attributes);
    }

    /**
     * Wrap raw html string
     */
    public function raw(string $html): Markup
    {
        return new Buffer($html);
    }

    /**
     * Normalize arbitrary content
     */
    public function wrap(...$content): Markup
    {
        return ContentCollection::normalize($content);
    }

    /**
     * Wrap arbitrary content as collection
     */
    public function content(...$content): Markup
    {
        return new ContentCollection($content);
    }




    /**
     * Generate nested list
     */
    public function list(?iterable $list, string $container, string $name, callable $callback=null, array $attributes=[]): Markup
    {
        return Element::create($container, function () use ($list, $name, $callback) {
            if (!$list) {
                return;
            }

            $i = 0;

            foreach ($list as $key => $item) {
                yield Element::create($name, function ($el) use ($key, $item, $callback, &$i) {
                    if ($callback) {
                        return $callback($item, $el, $key, ++$i);
                    } else {
                        return $item;
                    }
                });
            }
        }, $attributes)->setRenderEmpty(false);
    }


    /**
     * Generate naked list
     */
    public function elements(?iterable $list, string $name, callable $callback=null, array $attributes=[]): Markup
    {
        return ContentCollection::normalize(function () use ($list, $name, $callback, $attributes) {
            if (!$list) {
                return;
            }

            $i = 0;

            foreach ($list as $key => $item) {
                yield el($name, function ($el) use ($key, $item, $callback, &$i) {
                    if ($callback) {
                        return $callback($item, $el, $key, ++$i);
                    } else {
                        return $item;
                    }
                }, $attributes);
            }
        });
    }

    /**
     * Create a standard ul > li structure
     */
    public function uList(?iterable $list, callable $renderer=null, array $attributes=[]): Markup
    {
        return $this->list($list, 'ul', 'li', $renderer ?? function ($value) {
            return $value;
        }, $attributes);
    }

    /**
     * Create a standard ol > li structure
     */
    public function oList(?iterable $list, callable $renderer=null, array $attributes=[]): Markup
    {
        return $this->list($list, 'ol', 'li', $renderer ?? function ($value) {
            return $value;
        }, $attributes);
    }

    /**
     * Create a standard dl > dt + dd structure
     */
    public function dList(?iterable $list, callable $renderer=null, array $attributes=[]): Markup
    {
        $renderer = $renderer ?? function ($value) {
            return $value;
        };

        return Element::create('dl', function () use ($list, $renderer) {
            if (!$list) {
                return;
            }

            foreach ($list as $key => $item) {
                $dt = Element::create('dt', null);

                // Render dd tag before dt so that renderer can add contents to dt first
                $dd = (string)Element::create('dd', function ($dd) use ($key, $item, $renderer, &$i, $dt) {
                    return $renderer($item, $dt, $dd, $key, ++$i);
                });

                if ($dt->isEmpty()) {
                    $dt->push($key);
                }

                yield $dt;
                yield new Buffer($dd);
            }
        }, $attributes)->setRenderEmpty(false);
    }

    /**
     * Create an inline comma separated list with optional item limit
     */
    public function iList(?iterable $list, callable $renderer=null, string $delimiter=null, string $finalDelimiter=null, int $limit=null): Markup
    {
        if ($delimiter === null) {
            $delimiter = ', ';
        }

        return Element::create('span.list', function ($el) use ($list, $renderer, $delimiter, $finalDelimiter, $limit) {
            $el->setRenderEmpty(false);

            if (!$list) {
                return;
            }

            $first = true;
            $i = $more = 0;

            try {
                $total = count($list);
            } catch (\Throwable $e) {
                $total = null;
            }

            if ($finalDelimiter === null) {
                $finalDelimiter = $delimiter;
            }

            $items = [];

            foreach ($list as $key => $item) {
                if ($item === null) {
                    continue;
                }

                $i++;

                $cellTag = Element::create('?span', function ($el) use ($key, $item, $renderer, &$i) {
                    if ($renderer) {
                        return $renderer($item, $el, $key, $i);
                    } else {
                        return $item;
                    }
                });

                if (empty($tagString = (string)$cellTag)) {
                    $i--;
                    continue;
                }

                if ($limit !== null && $i > $limit) {
                    $more++;
                    continue;
                }


                $items[] = new Buffer($tagString);
            }

            $total = count($items);

            foreach ($items as $i => $item) {
                if (!$first) {
                    if ($i + 1 == $total) {
                        yield $finalDelimiter;
                    } else {
                        yield $delimiter;
                    }
                }

                yield $item;

                $first = false;
            }

            if ($more) {
                if (!$first) {
                    yield $delimiter;
                }

                yield Element::create('em.more', '…+ '.$more);
            }
        });
    }



    /**
     * Convert arbitrary html to text
     */
    public function toText($html): ?string
    {
        if (is_string($html)) {
            $html = new Buffer($html);
        }

        $html = (string)ContentCollection::normalize($html);

        if (empty($html)) {
            return null;
        }

        $output = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $output = str_replace("\r\n", "\n", $output);
        return $output;
    }

    /**
     * Convert HTML to text and shorten if needed
     */
    public function previewText($html, int $maxLength=null): ?string
    {
        if (null === ($output = self::toText($html))) {
            return null;
        }

        if ($maxLength !== null) {
            $length = mb_strlen($output);

            if ($length > $maxLength) {
                $output = mb_substr($output, 0, $maxLength).'…';
            }
        }

        return $output;
    }

    /**
     * Convert HTML to text and shorten if neededm wrapping in Markup
     */
    public function preview($html, int $maxLength=null): Markup
    {
        if (null === ($output = self::toText($html))) {
            return null;
        }

        if ($maxLength !== null) {
            $length = mb_strlen($output);

            if ($length > $maxLength) {
                $output = [
                    Element::create('abbr', mb_substr($output, 0, $maxLength), [
                        'title' => $output
                    ]),
                    Element::create('span.suffix', '…')
                ];
            }
        }

        return ContentCollection::normalize($output);
    }

    /**
     * Escape HTML
     */
    public function esc(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
