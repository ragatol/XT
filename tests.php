<?php

include 'xt.php';

// ATX test

if (isset($_REQUEST['test'])) {
	XT\parse('tests/'.$_REQUEST['test'].'.xt');
} else {
	XT\parse('tests/index.xt');
}
