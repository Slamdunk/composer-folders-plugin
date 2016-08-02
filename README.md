# composer-folders-plugin

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
