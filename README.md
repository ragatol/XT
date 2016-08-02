# XT
XT - A small and fast Markdown style text to HTML parser.

## About

XT is a library to convert Markdown style text to HTML code.

It's able to understand headings, paragraphs, quotes, ordered and unordered lists, inline HTML and code fragments.

Since it's designed to be a single-pass, line-by-line, top-down parser, there's some differences on how it implements some complex structures:

- List itens with multiple lines must be identated within the list item first line, eg;
	~~~
	-	Item
	-	Multiple
		Line

		Item
	-	Other Item
	~~~
- References must be defined __before__ use.
- Only a few tags are understood when used inline. Use the block-level HTML for more complex HTML.

## How to use

~~~php
<?php

include "xt.php";

XT\parse(new SplFileObject("file"));

~~~
