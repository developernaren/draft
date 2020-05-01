# Draft

Draft is a PHP static site creator.

## Why the name Draft?
From the time I heard about [reactphp](https://reactphp.org/) I wanted to work with it. But there was not any sort of starter template or framework to take it up.
With [DriftPhp](https://driftphp.io/), I finally found something to get started. Since this is a draft generator of sort which generates drafts of blog posts and it is close to Drift. 
Hence, Draft.


## Installation

`composer install draftphp/draft`


### Usage

This is built on of ReactPhp. So, we work with Promises.

```php
use \React\EventLoop\Factory;
use \React\Filesystem\Filesystem;
use DraftPhp\HtmlGenerator;
use DraftPhp\Config;
use function Clue\React\Block\await;

$loop = Factory::create();
$filesystem = Filesystem::create($loop);

$configData = [
  'pages_dir' => '/path/to/pages/',//directory where the pages are
  'layout_dir' =>  '/path/to/layouts/', //directory where the layout are
];

$config = new Config($configData);
//this would build the html page based on `/path/to/pages/index.html`
$generator = new HtmlGenerator($config, $filesystem, 'index.html');

//usage as promise
$generator->getHtml()
    ->then(function ($content){
    var_dump($content);//this is the content generated based on the file, index.html
});   

//usage with await
$content = await($generator->getHtml(), $loop);
```

### `<draft>`
We include 'meta' for post in a `<draft>` tag. Meta here means whatever you want to be replaced in the content of the page.

> `{content}` in the layout and `layout` in the `<draft>` tag are reserved and cannot be used to replace the contents in the page

The syntax in the template is `{meta}`.
For example if you want to add title to a page `{title}`, add a 
```
title: This is the test title
```
in your draft tag. 
and in your layout. You would add

```html
...
<title>{title}</title>
...

```

This will generate 
```html
<title>This is the test title</title>
```
in the HTML.
  
    Example draft tag
    ```
    <draft>       
        description: this is the best description
        title: This is the test title
        layout: blog.html
    </draft>
    ```
Refer to this file for [example](/tests/Mocks/pages/index.html) 

## Todos

- [ ] Refactor to make it adaptable
- [ ] Tests
- [ ] Cache Support
- [ ] Build process to generate static html pages
- Configurable
    - Options
        - [ ] Base Route
        - [ ] Layout path
        - [ ] Cache Path and driver
        - [ ] Better Seo support
        - [ ] Hot reload for md changes
        
I feel like there is only so much a static site generator should be able to do, but feel free to add things you would like to see here.
        
## Badge

[![CircleCI](https://circleci.com/gh/draftphp/draft.svg?style=svg)](https://circleci.com/gh/draftphp/draft)





