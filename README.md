# CakePHP Application Skeleton with Docker

[![Build Status](https://img.shields.io/travis/cakephp/app/master.svg?style=flat-square)](https://travis-ci.org/cakephp/app)
[![License](https://img.shields.io/packagist/l/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)

A skeleton for creating applications with [CakePHP](https://cakephp.org) 3.x. using Docker

The framework source code can be found here: [cakephp/cakephp](https://github.com/cakephp/cakephp).

## Installation

1. Install [Composer](https://getcomposer.org/doc/00-intro.md) using the recommended configuration.
2. Run `composer create-project --prefer-dist angelxmoreno/cakephp-skeleton [app_name]`.

You can now either use your machine's webserver to view the default home page, or start
up the built-in webserver with:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Update

There are a few changes from the original skeleton found at [cakephp/app](https://github.com/cakephp/app)

    1. Updated README to denote the usage of Docker
    2. Deleted unused directories and files
        a. .github/
        b. config/.env.default
    3. Moved .env.default to root
    4. Removed files from .gitignore ( config/app is now commitable )

## Configuration

Read and edit `config/app.php` and setup the `'Datasources'` and any other
configuration relevant for your application.

## Layout

The app skeleton uses a subset of [Foundation](http://foundation.zurb.com/) CSS
framework by default. You can, however, replace it with any other library or
custom styles.
