# composer-folders-plugin

[![Latest Stable Version](https://img.shields.io/packagist/v/slam/composer-folders-plugin.svg)](https://packagist.org/packages/slam/composer-folders-plugin)
[![Downloads](https://img.shields.io/packagist/dt/slam/composer-folders-plugin.svg)](https://packagist.org/packages/slam/composer-folders-plugin)
[![Integrate](https://github.com/Slamdunk/composer-folders-plugin/workflows/Integrate/badge.svg?branch=master)](https://github.com/Slamdunk/composer-folders-plugin/actions)
[![Code Coverage](https://codecov.io/gh/Slamdunk/composer-folders-plugin/coverage.svg?branch=master)](https://codecov.io/gh/Slamdunk/composer-folders-plugin?branch=master)

Create and clean specified folders

```
{
    "require": {
        "slam/composer-folders-plugin": "^1.0"
    },
    "extra": {
        "folders-plugin": {
            "create": {
                "data":         "0770",
                "data/cache":   "0770",
                "data/hide-me": "0500",
                "tmp":          "0777"
            },
            "clean": {
                "data/cache":   "*",
                "tmp":          "*.cache.php"
            }
        }
    }
}

```
