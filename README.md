Bowerphp
========

An implementation of [bower](http://bower.io) in PHP.

http://bowerphp.org

[![travis build](https://api.travis-ci.org/Bee-Lab/bowerphp.png)](https://travis-ci.org/Bee-Lab/bowerphp) 
[![Code Climate](https://codeclimate.com/github/Bee-Lab/bowerphp/badges/gpa.svg)](https://codeclimate.com/github/Bee-Lab/bowerphp)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bee-Lab/bowerphp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bee-Lab/bowerphp/?branch=master)
[![insight](https://insight.sensiolabs.com/projects/d1fbaca7-0e68-4782-979b-2372a9578c2d/mini.png)](https://insight.sensiolabs.com/projects/d1fbaca7-0e68-4782-979b-2372a9578c2d)


Installation
------------

```bash
$ composer require beelab/bowerphp:1.0.*@dev
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

This project is in alpha status.

See currently open [issues](https://github.com/Bee-Lab/bowerphp/issues).

Building the phar
-----------------

One method to build the phar is to simply run:
```sh
php bin/compile
```
Another is to use the [box command line utility](https://github.com/box-project/box2)
If you add this config in a box.json file you only just need to run the two commands bellow to have a working phar.

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
