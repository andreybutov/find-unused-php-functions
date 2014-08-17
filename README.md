find-unused-php-functions
=========================

Find unused functions in a set of PHP files.

**USAGE:  `find_unused_functions.php <root_directory>`**

NOTE: This is a ‘quick-n-dirty’ approach to the problem. This script only performs a lexical pass over the files, and does not respect situations where different modules define identically named functions or methods. If you use an IDE for your PHP development, it may offer a more comprehensive solution.

Requires PHP 5

