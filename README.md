# ACF PRO Installer

[![Packagist](https://img.shields.io/packagist/v/philippbaschke/acf-pro-installer.svg?maxAge=3600)](https://packagist.org/packages/philippbaschke/acf-pro-installer)
[![Packagist](https://img.shields.io/packagist/l/philippbaschke/acf-pro-installer.svg?maxAge=2592000)](https://github.com/PhilippBaschke/acf-pro-installer/blob/master/LICENSE)
[![Travis](https://img.shields.io/travis/PhilippBaschke/acf-pro-installer.svg?maxAge=3600)](https://travis-ci.org/PhilippBaschke/acf-pro-installer)
[![Coveralls](https://img.shields.io/coveralls/PhilippBaschke/acf-pro-installer.svg?maxAge=3600)](https://coveralls.io/github/PhilippBaschke/acf-pro-installer)

A composer plugin that makes installing [ACF PRO] with [composer] easier. 

It reads your :key: ACF PRO key from the **environment** or a **.env file**.

[ACF PRO]: https://www.advancedcustomfields.com/pro/
[composer]: https://github.com/composer/composer

## Usage

**1. Add the package repository to the [`repositories`][composer-repositories] field in `composer.json` 
   (based on this [gist][package-gist]):**

```json
{
  "type": "package",
  "package": {
    "name": "advanced-custom-fields/advanced-custom-fields-pro",
    "version": "*.*.*(.*)",
    "type": "wordpress-plugin",
    "dist": {
      "type": "zip",
      "url": "https://connect.advancedcustomfields.com/index.php?p=pro&a=download"
    },
    "require": {
      "philippbaschke/acf-pro-installer": "^1.0",
      "composer/installers": "^1.0"
    }
  }
}
```
Replace `"version": "*.*.*(.*)"` with your desired version.

**2. Make your ACF PRO key available**

Set the environment variable **`ACF_PRO_KEY`** to your [ACF PRO key][acf-account].

Alternatively you can add an entry to your **`.env`** file:

```ini
# .env (same directory as composer.json)
ACF_PRO_KEY=Your-Key-Here
```

**3. Require ACF PRO**

```sh
composer require advanced-custom-fields/advanced-custom-fields-pro:*
```
You can specify an [exact version][composer-versions] (that matches your desired version).

If you use **`*`**, composer will install the version from the package repository (see 1). This has the benefit that you only need to change the version in the package repository when you want to update.

*Be aware that `composer update` will only work if you change the `version` in the package repository. Decreasing the version only works if you require an [exact version][composer-versions].*

[composer-repositories]: https://getcomposer.org/doc/04-schema.md#repositories
[composer-versions]: https://getcomposer.org/doc/articles/versions.md
[package-gist]: https://gist.github.com/dmalatesta/4fae4490caef712a51bf
[acf-account]: https://www.advancedcustomfields.com/my-account/
