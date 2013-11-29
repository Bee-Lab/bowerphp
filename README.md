bowerphp
========

An implementation of [bower](http://bower.io) in PHP.

Status
------

This project is in pre-alpha status.

Currently, it just lists options in a terminal.

Roadmap
-------

* minimum features (e.g. install, update)
  * read ``bower.json`` file
  * install required packages
  * install a package required by terminal and edit ``bower.json`` accordingly
  * update a single or all packages
  * create a phar archive of this project

* advanced features (e.g., all features originally implemented by bower)
  * remove a package
  * read options from a ``.bowerrc`` file 
  * search a package
  * init ``bower.json``
  * get package info
  * handle cache

