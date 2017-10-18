--TEST--
Disable functions check on `ret` with an alias
--SKIPIF--
<?php if (!extension_loaded("snuffleupagus")) die "skip"; ?>
--INI--
sp.configuration_file={PWD}/config/config_disabled_functions_ret_alias.ini
--FILE--
<?php 
echo strpos("pouet", "p") . "\n";
?>
--EXPECTF--
[snuffleupagus][0.0.0.0][disabled_function][drop] The execution has been aborted in %a/disabled_functions_ret_alias.php:%d, because the function 'strpos' returned '0', which matched the rule 'test'.
