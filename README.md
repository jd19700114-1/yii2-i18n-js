# yii2-i18n-js

- [Installation](#installation)
- [Usage](#usage)

## Installation

``` shell
composer require w3lifer/yii2-i18n-js
```

1. Add this to your application configuration:

``` php
<?php

return [
    // ...
    'components' => [
        // ...
        'i18nJs' => [
            'class' => 'w3lifer\yii2\I18nJs',
        ],
        // ...
    ],
    // ...
];
```

2. Initialize the component anywhere, for example in `@app/views/layouts/main.php`:

``` php
Yii::$app->i18nJs;
```

Note, you do not need to register the component in the places that will be processed with AJAX-requests (for example, in `@app/config/web.php` -> `bootstrap`, `on afterRequest`, etc), because it will be loaded twice, and it makes no sense.


## Usage

``` js
$(function () {
  console.log(yii.t('app', 'Hello'));
  console.log(yii.t('app', 'Hello, World!'));
  console.log(yii.t('app', 'Hello, {username}!', {username: 'John'}));
  console.log(yii.t('app', 'Hello, {0}!', ['John']));
  console.log(yii.t('app', 'Hello, {0} {1}!', ['John', 'Doe']));
  console.log(yii.t('app', 'Hello, {0} {1}!', ['John', 'Doe'], 'en-US'));
});
```
