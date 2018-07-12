# phpunit-skeleton
tool to create phpunit skeleton for files

Generates een skeleton file for your php file. Includes mocks for all constructor dependencies and initialzes the class 
with these dependencies.

usage:

`./phpunit-skeleton.phar create [filename]`

building a new version of the phar (with box)
`./vendor/bin/box build`

also included is a bash script which you can give a commmit hash and it will create unittests for all added files
from that hash
