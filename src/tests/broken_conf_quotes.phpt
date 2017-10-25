--TEST--
Broken configuration - missing quote
--SKIPIF--
<?php if (!extension_loaded("snuffleupagus")) print "skip"; ?>
--INI--
sp.configuration_file={PWD}/config/broken_conf_quotes.ini
--FILE--
--EXPECT--
