******
OpenID
******
OpenID is a free and easy way to use a single digital identity across the Internet,
see http://openid.net/ for details.
This package is a PHP implementation of the OpenID 1.1 and 2.0
specifications for Relying Party functionality.

Only Relying Party support is provided at this time. Provider support is already 
underway, and will be added as a separate package (i.e. ``OpenID_Provider``).

There is out of the box support for a few extensions, including Simple Registration 
(1.0 and 1.1), Attribute Exchange, OAuth-Hybrid, and some support for the new 
UI extension.

This package supports a storage interface
(including ``Cache_Lite`` and ``MDB2`` drivers) 
for easy addition of custom drivers.
There is also support for observers for logging, etc.

There is an example web console for testing discovery, relying party (with some 
useful debugging functionality), and also a sample implementation of an OpenID JS 
Selector (i.e. the "NASCAR" solution).

You can try the examples here: http://shupp.org/openid/examples

A couple of notes about this package:

* There is 88% code coverage, and full CS compliance with PHP_CodeSniffer 1.1.0.
* This package meets all test-id.net tests with the exception of
  SSL validation, as that doesn’t work well in curl for some reason
  (I’m investigating it).

============
Installation
============

PEAR
====
::

    $ pear install OpenID-alpha


Composer
========
::

    $ composer require pear/openid


=====
Links
=====
Homepage
  http://pear.php.net/package/OpenID
Bug tracker
  http://pear.php.net/bugs/search.php?cmd=display&package_name[]=OpenID
Unit test status
  https://travis-ci.org/pear/OpenID

  .. image:: https://travis-ci.org/pear/OpenID.svg?branch=master
     :target: https://travis-ci.org/pear/OpenID
Packagist
  https://packagist.org/packages/pear/openid
