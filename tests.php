<?php

include 'xt.php';

// ATX test

if (isset($_REQUEST['test'])) {
	XT\parse(new SplFileObject('tests/'.$_REQUEST['test'].'.xt'));
} else {
	$t = microtime(true);
	XT\parse(new SplFileObject('examples/basic.xt'));
	$t = microtime(true) - $t;
	echo "Executado em $t segundos.\n";
}
