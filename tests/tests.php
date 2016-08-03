<?php

include '../xt.php';

// ATX test
$t = microtime(true);
if (isset($_REQUEST['test'])) {
	XT\parse(new SplFileObject($_REQUEST['test'].'.xt'),'/xt/tests/tests.php');
} else {
	XT\parse(new SplFileObject('../examples/basic.xt'),'/xt/tests/tests.php');
}
$t = microtime(true) - $t;
echo "Executado em $t segundos.\n";
