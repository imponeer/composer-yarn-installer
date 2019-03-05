[![License](https://img.shields.io/github/license/imponeer/composer-yarn-installer.svg?maxAge=2592000)](LICENSE) [![Test Status](https://travis-ci.org/imponeer/composer-yarn-installer.svg?branch=master)](https://travis-ci.org/imponeer/composer-yarn-installer) [![PHP from Packagist](https://img.shields.io/packagist/php-v/imponeer/composer-yarn-installer.svg)](https://php.net) 
[![Packagist](https://img.shields.io/packagist/v/imponeer/composer-yarn-installer.svg)](https://packagist.org/packages/imponeer/composer-yarn-installer) 
[![Packagist](https://img.shields.io/packagist/dm/imponeer/composer-yarn-installer.svg)](https://packagist.org/packages/imponeer/composer-yarn-installer)

# Composer Yarn Installer

Composer plugin that installs [Yarn](https://yarnpkg.com/) to /vendor/bin path.

## Usage
 
Easiest way to do that is to execute composer command from console:

```bash
composer require imponeer/composer-yarn-installer
```

## Configuration

It's possible a bit to configure plugin. For that some configuration options can be added to composer.json `extra` section.

| Key | Possible values | Default | What does? |
|-----|-----------------|---------|-------------|
| yarn_version | rc<br />latest<br />nighty<br />MAJOR.MINOR.REVISION | latest | Specifies what yarn version should be installed |

## How to contribute?

If you want to add some functionality or fix bugs, you can fork, change and create pull request. If you not sure how this works, try [interactive GitHub tutorial](https://try.github.io).

If you found any bug or have some questions, use [issues tab](https://github.com/imponeer/composer-yarn-installer/issues) and write there your questions.
