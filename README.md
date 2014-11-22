Bowerphp
========

An implementation of [bower](http://bower.io) in PHP.

http://bowerphp.org

[![travis build](https://api.travis-ci.org/Bee-Lab/bowerphp.png)](https://travis-ci.org/Bee-Lab/bowerphp) [![insight](https://insight.sensiolabs.com/projects/d1fbaca7-0e68-4782-979b-2372a9578c2d/mini.png)](https://insight.sensiolabs.com/projects/d1fbaca7-0e68-4782-979b-2372a9578c2d)

Installation
------------

```bash
$ composer require beelab/bowerphp:1.0.*@dev
```

Configuration
-------------

Currently, you can configure your bower directory in ``.bowerrc`` file, just like the original Bower.

If you need many dependencies, you'll likely hit the Githup API limit (currently 60 requests per hour).
To increase your limit to 5000 requests per hour, you can use a token.
See [Github help](https://help.github.com/articles/creating-an-access-token-for-command-line-use/).
Once you created your token, just store it in the ``BOWERPHP_TOKEN`` environment variable.

Status
------

This project is in alpha status.

See currently open [issues](https://github.com/Bee-Lab/bowerphp/issues).
