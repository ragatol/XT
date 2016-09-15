<!DOCTYPE hmtl>
<html>
	<head>
		<meta charset="utf8">
		<title>XT Test</title>
	</head>
	<body>
<?php

include '../xt.php';

// ATX test
$t = microtime(true);
if (isset($_REQUEST['test'])) {
	XT\parse(new SplFileObject($_REQUEST['test'].'.md'),'/xt/tests/tests.php');
} else {
	echo "<h1>XT Tests</h1>\n<ul>";
	$dir = new \DirectoryIterator(".");
	foreach ($dir as $fileinfo) {
		if ($fileinfo->isFile() && preg_match('/\.md$/S',$fileinfo->getFilename())) {
			$name = preg_replace('/\.md$/S','',$fileinfo->getFilename());
			echo "<li><a href=\"?test={$name}\">{$name}</a></li>\n";
		}
	}
}
$t = microtime(true) - $t;
echo "Executado em $t segundos.\n";
?>
	</body>
</html>
