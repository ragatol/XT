<?php
include 'xt.php';
include 'parsedown/parsedown.php';
if(isset($_REQUEST['demo'])) {
	XT\parse('examples/'.$_REQUEST['demo']);
} else {
	XT\parse('examples/basic.xt');
}