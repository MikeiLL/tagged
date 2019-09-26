# Tagged
PHP markup generation without the fuss.


## Installation
```bash
composer install decodelabs/tagged
```

## Usage

Generate markup using a simple, flexible interface.

```php
echo Html::{'div.my-class#my-id'}('This is element content', [
    'title' => 'This is a title'
]);

/*
Creates -

<div class="my-class" id="my-id" title="This is a title">This is element content</div>
*/
```

Create individual tags without content:

```php
$tag = Html::tag('div.my-class');

echo $tag->open();
echo 'Content';
echo $tag->close();
```

Wrap HTML strings to be used where an instance of <code>Markup</code> is needed:

```php
$buffer = Html::raw('<span class="test">My span</span>');
```

Prepare arbitrary input for Markup output:

```php
$markup = Html::wrap(
    function() {
        yield Html::h1('My title');
    },
    [Html::p(['This is ', Html::strong('mixed'), ' content'])]
);
```


### Nesting

You can nest elements in multiple ways:

```php
// Pass in nested elements via array
echo Html::div([
    Html::{'span.inner1'}('Inner 1'),
    ' ',
    Html::{'span.inner2'}('Inner 2')
]);


// Return anything and everything via a generator
echo Html::div(function($el) {
    // $el is the root element
    $el->addClass('container');

    // Nest elements with a single call
    yield Html::{'header > h1'}('This is a header');
    yield Html::p('This is a paragraph');

    // Set attributes inline
    yield Html::{'p[data-target=open]'}('Target paragraph');
});
```


### Convert to HTML
Parse various formats and convert to HTML:

```php
// Plain text
echo Html::$parse->plainText($plainText); // Replace \n with <br />

// Markdown
echo Html::$parse->markdown($myMarkdown); // Trusted markdown
echo Html::$parse->userMarkdown($myMarkdown); // Untrusted markdown
echo Html::$parse->inlineMarkdown($myMarkdown); // Trusted inline markdown
echo Html::$parse->userInlineMarkdown($myMarkdown); // Untrusted inline markdown

// Tweet
echo Html::$parse->tweet($plainTweet); // Convert tweet source to HTML
```


### Time and date
Format and wrap dates and intervals

```php
// Custom format
Html::$time->format('now', 'd/m/Y', 'Europe/London');

// Locale format
// When timezone is true it is fetched from Systemic::$timezone
Html::$time->locale('now', 'long', 'long', true);

// Locale shortcuts
Html::$time->mediumDateTime('tomorrow');
Html::$time->longTime('yesterday');
Html::$time->shortDate('yesterday');
// ...etc


// Intervals
Html::$time->since('yesterday'); // 1 day
Html::$time->until('yesterday'); // -1 day
Html::$time->fromNow('yesterday'); // 1 day ago
Html::$time->fromNow('tomorrow'); // 1 day from now
Html::$time->between('yesterday', 'tomorrow'); // 1 day
```


### Icons
Create the markup needed for font or SVG icons:

```php
Html::$icon->setFormat('font');
echo Html::$icon->aubergine; // <i class="icon icon-aubergine"></i>

Html::$icon->setFormat('svg');
echo Html::$icon->aubergine; // <svg><use xlink:href="#aubergine" /></svg>

Html::$icon->setSvgReference('path/to/my/file.svg');
echo Html::$icon->aubergine; // <svg><use xlink:href="path/to/my/file.svg#aubergine" /></svg>
```


### Media embeds
Normalize embed codes shared from media sites:

```php
echo Html::$embed->video('https://www.youtube.com/watch?v=RG9TMn1FJzc');
```


### To text
Convert and normalize html to plain text:

```php
Html::$toText->convert('<h1>My html</h1>'); // My html
Html::$toText->preview('<h1>My html</h1>', 5); // My ht...
```


## Licensing
Glitch is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
