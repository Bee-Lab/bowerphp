Bowerphp
========

An implementation of [bower](http://bower.io) in PHP.

http://bowerphp.org

[![travis build](https://api.travis-ci.org/Bee-Lab/bowerphp.png)](https://travis-ci.org/Bee-Lab/bowerphp) 
[![Code Climate](https://codeclimate.com/github/Bee-Lab/bowerphp/badges/gpa.svg)](https://codeclimate.com/github/Bee-Lab/bowerphp)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bee-Lab/bowerphp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bee-Lab/bowerphp/?branch=master)
[![insight](https://insight.sensiolabs.com/projects/d1fbaca7-0e68-4782-979b-2372a9578c2d/mini.png)](https://insight.sensiolabs.com/projects/d1fbaca7-0e68-4782-979b-2372a9578c2d) [![Join the chat at https://gitter.im/Bee-Lab/bowerphp](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/Bee-Lab/bowerphp?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


Installation
------------

```bash
$ composer require beelab/bowerphp
```

Configuration
-------------

Currently, you can configure your bower directory in ``.bowerrc`` file, just like the original Bower.

If you need many dependencies, you'll likely hit the Github API limit (currently 60 requests per hour).
To increase your limit to 5000 requests per hour, you can use a token.
See [Github help](https://help.github.com/articles/creating-an-access-token-for-command-line-use/).
Once you created your token, just store it in the ``BOWERPHP_TOKEN`` environment variable.

Status
------

This project is in stable version (no beta suffix), but still in `0` major version (BC not assured).

See currently open [issues](https://github.com/Bee-Lab/bowerphp/issues).

Contributing
------------

All contribution are welcome, just take a look at our [issues](https://github.com/Bee-Lab/bowerphp/issues) tracker if you want to start somewhere.

If you make a PR make sure that it follow the [PSR2 standard](http://www.php-fig.org/psr/psr-2/).
To make sure that your code comply with the standard, you can use a git hook with [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).
There is one here that you only need to copy to your .git/hooks folder under the name of pre-commit and you are set.

Building the phar
-----------------

You can build the phar by simply running:
```sh
php bin/compile
```
Or you can use the [box command line utility](https://github.com/box-project/box2)
If you add this config in a box.json file, you only just need to run the two commands below to have a working phar.

###the commands

```sh
$ box build
$ chmod +x bower.phar
```

### the box.json config

```json
{
    "directories": ["src"],
    "files": ["LICENSE"],
    "finder": [
        {
            "name": "*.php",
            "exclude": ["Tests", "phpunit", "mockery"],
            "in": "vendor"
        }
    ],
    "main": "bin/bowerphp",
    "output": "bower.phar",
    "stub": true
}
```
