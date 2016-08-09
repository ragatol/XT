<?php
namespace XT\impl;

class LineReader {
	public $source;
	public $blocks;
	public $current_block;
	private $rewind;
	public $line;
	public $baseurl;

	public function __construct( \SplFileObject $source, $baseurl ) {
		$this->source = $source;
		$this->current_block = null;
		$this->blocks = [];
		$this->rewind = false;
		$this->line = "";
		$this->baseurl = $baseurl;
	}

	public function rewindLine() {
		$this->rewind = true;
	}

	public function push( string $blk ) {
		array_push($this->blocks,$this->current_block);
		$this->current_block .= $blk;
	}

	public function pop() {
		$this->current_block = array_pop($this->blocks);
	}

	public function readLine() {
		if ($this->rewind) {
			$this->rewind = false;
		} else {
			if ($this->source->eof()) {
				$this->line = "";
				return false;
			}
			$this->line = preg_replace('/ {4}/S',"\t",$this->source->fgets());
		}
		if (\strlen(\trim($this->line)) == 0) return "\n";
		if ($this->current_block === null) return $this->line;
		if (!preg_match('/^'.$this->current_block.'/S',$this->line)) return false;
		return preg_replace('/^'.$this->current_block.'/S',"",$this->line,1); 
	}

	public function getLine() {
		if ($this->source->eof()) return false;
		return ($this->line = $this->source->fgets());
	}
}

function replaceSpecial($line) {
	$line = preg_replace('/_/S','&lowbar;',$line);
	$line = preg_replace('/\*/S','&ast;',$line);
	return $line;
}

function parseLine($reader,$line) {
	// URL generators:
	// email
	$line = preg_replace('/<(\S+\@\S+\.\S+)>/S','<a href="mailto:$1">$1</a>',$line);
	// images
	$line = preg_replace_callback('/\!\[(.+?)\]\(([^" ]+)(?:\s*("|\')([^\3]*?)\3)?\)/S',
		function ($matches) use (&$reader) {
			$title = isset($matches[4]) ? "title=\"{$matches[4]}\"" : "";
			if (preg_match('/^(?:http(?:s)?:\/\/|\/\S|\?\S)/S',$matches[2])) {
				$link = $matches[2];
			} else {
				$link = \dirname($reader->baseurl). '/' . $matches[2];
			}
			return "<img src=\"$link\" alt=\"{$matches[1]}\" $title>";
		}, $line);
	// links
	$line = preg_replace_callback('/\[(.+?)\]\(([^"\'\s\)]+)(?:\s*("|\')([^\3\)]*?)\3)?\)/S',
		function ($matches) use (&$reader) {
			$title = isset($matches[4]) ? "title=\"{$matches[4]}\"" : "";
			if (preg_match('/^(?:http(?:s)?:\/\/|\/\S|\?\S)/S',$matches[2])) {
				$link = $matches[2];
			} else {
				$link = \dirname($reader->baseurl). '/' . $matches[2];
			}
			return "<a href=\"$link\" $title>{$matches[1]}</a>";
		}, $line);
	// automatic link
	$line = preg_replace('/(?<!(?:ref|src)=["\'])<?((?:https?|ftp):\/\/[\w-.!*\'\;:@&=+$,\/?#]+)>?/S',"<a href=\"$1\">$1</a>",$line);
	// special characters
	// < -> &lt; if not an <a>, <b>, <i> or <span> tag
	$line = preg_replace('/<(?!\/?a|b|i|span)/S','&lt;',$line);
	// formatting
	// inline code
	$line = preg_replace_callback('/(`{1,2})(.+?)\1/S',
		function ($matches) {
			return '<code>'.replaceSpecial($matches[2]).'</code>';	
		},$line);
	// strong
	$line = preg_replace_callback('/(\*\*|__)([\w_\*<].+?)\1(?=[^\w\*_])/S',
		function ($matches) {
			return '<strong>'.replaceSpecial(preg_replace('/((?<=\W|^)[\*_])(.+?)(?:(?<=\w|>)\1)/S','<em>$2</em>',$matches[2])).'</strong>';
		},$line);
	// em
	$line = preg_replace('/((?<=\W|^)[\*_])(.+?)(?:(?<=\w|>)\1)/S','<em>$2</em>',$line);
	return $line;
}

function parseHTML(LineReader $reader) {
	echo $reader->line;
	$endtag = preg_replace('/^<(\w+).*/S','/^<\/$1/S',$reader->line);
	while (false !== ($line = $reader->getLine())) {
		if (strlen(trim($line)) > 0 && !preg_match('/^\s|</S',$line)) {
			$reader->rewindLine();
			return;
		}
		echo $line;
		if (preg_match($endtag,$line)) return;
	}
}

function parseCode(LineReader $reader, bool $fenced = false) {
	$lang = "";
	if ($fenced) {
		$lang = preg_replace('/.*?[~`]{3,}(\S+)\n/S','$1',$reader->line);
	}
	echo "<pre><code", (strlen($lang) > 0 ? " class=\"language-$lang\"" : "") , ">\n";
	if (!$fenced) {
		$reader->push('\t');
		$reader->rewindLine();
	}
	while (false !== ($line = $reader->readLine())) {
		if ($fenced && preg_match('/^[`~]{3}/S',$line)) break;
		echo htmlentities($line);
	}
	echo "</code></pre>\n";
	if (!$fenced) {
		$reader->pop();
		$reader->rewindLine();
	}
}

function parseList(LineReader $reader, bool $ordered) {
	$tag = $ordered ? 'ol' : 'ul';
	$regex = $ordered ? '/^(?:\d+\.\s)/S' : '/^(?:[*-]\s)/S';
	echo "<$tag>\n";
	$reader->rewindLine();
	while (false !== ($line = $reader->readLine())) {
		if (!preg_match($regex,$line)) break;
		$reader->line = mb_substr($reader->line,0,mb_strlen($reader->line)-mb_strlen($line)).preg_replace($regex,"\t",$line,1);
		$reader->push('\t');
		$reader->rewindLine();
		echo "<li>";
		parseP($reader);
		echo "</li>\n";
		$reader->pop();
		$reader->rewindLine();
	}
	// end list
	echo "</$tag>\n";
	$reader->rewindLine();
}

function parseQuote(LineReader $reader) {
	echo "<blockquote>\n";
	$reader->rewindLine();
	$reader->push('(?:>\s?)');
	parseP($reader);
	$reader->pop();
	$reader->rewindLine();
	echo "</blockquote>\n";
}

function parseP(LineReader $reader) {
	$p = false;
	while ( false !== ($line = $reader->readLine())) {
		if ($reader->current_block == "" && preg_match('/^<\w/S',$line)) {
			// inline HTML
			if ($p) { echo "</p>"; $p = false; }
			parseHTML($reader);
			continue;
		}
		if (preg_match('/^#{1,6}\s*\w+/S',$line)) {
			// H1-H6
			if ($p) { echo "</p>\n"; $p = false; }
			$lvl = strspn($line,"#");
			echo "<h$lvl>",parseLine($reader,trim($line,"#\n ")),"</h$lvl>\n";
			continue;
		}
		if (preg_match('/^---+$/S',$line)) {
			// hr
			if ($p) { echo "</p>\n"; $p = false; }
			echo "<hr>\n";
			continue;
		}
		if (preg_match('/^>/S',$line)) {
			// blockquote
			if ($p) { echo "</p>\n"; $p = false; }
			parseQuote($reader);
			continue;
		}
		if (preg_match('/^\d+\./S', $line)) {
			// ol
			if ($p) { echo "</p>\n"; $p = false; }
			parseList($reader,true);
			continue;
		}
		if (preg_match('/^(?:-|\*)\s/S', $line)) {
			// ul
			if ($p) { echo "</p>\n"; $p = false; }
			parseList($reader,false);
			continue;
		}
		if (preg_match('/^[`~]{3}/S',$line)) {
			// fenced pre-code
			if ($p) { echo "</p>\n"; $p = false; }
			parseCode($reader,true);
			continue;
		}
		if (preg_match('/^\t/S',$line)) {
			// pre,code
			if ($p) { echo "</p>\n"; $p = false; }
			parseCode($reader);
			continue;
		}
		if ($line == "\n") {
			if ($p) { echo "</p>\n"; $p = false; }
			continue;
		}
		if (!$p) {
			if ($line == "\n" || $line == "") continue;
			echo "<p>"; $p = true;
		}
		echo parseLine($reader,$line);
		if (preg_match('/  $/S',$line)) echo "<br>";
	}
	if ($p) echo "</p>\n";
}

// end namespace XT\impl

namespace XT;

/**
 * Parses a XT file through the PHP interpreter, then transforms XT text to HTML.
 * @param SplFileObject $INPUT inpu stream with source
 * @param string $baseurl If set, it'll be added at the start of realtive urls
 */
function parse(\SplFileObject $INPUT, $baseurl = NULL ) {
	impl\parseP(new impl\LineReader($INPUT,$baseurl));
};

// end namespace XT
