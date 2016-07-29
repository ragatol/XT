<?php

include 'xt.php';

// ATX test
$t = microtime(true);
if (isset($_REQUEST['test'])) {
	XT\parse(new SplFileObject('tests/'.$_REQUEST['test'].'.xt'));
} else {
	XT\parse(new SplFileObject('examples/basic.xt'));
}
$t = microtime(true) - $t;
echo "Executado em $t segundos.\n";
