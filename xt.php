<?php
namespace XT\impl;

class LineReader {
	public $source;
	public $blocks;
	public $current_block;
	public $rewind;
	public $line;

	public function __construct( \SplFileObject $source ) {
		$this->source = $source;
		$this->current_block = null;
		$this->blocks = [];
		$this->rewind = false;
		$this->line = "";
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
			if ($this->source->eof()) return false;
			$this->line = $this->source->fgets();
		}
		if (\strlen(\trim($this->line)) == 0) return "\n";
		if ($this->current_block === null) return $this->line;
		if (!preg_match(';^'.$this->current_block.';S',$this->line)) return false;
		return preg_replace(';^'.$this->current_block.';S',"",$this->line,1); 
	}

	public function getLine() {
		if ($this->source->eof()) return false;
		return ($this->line = $this->source->fgets());
	}
}

function parseLine($line) {
	// inline code
	$line = preg_replace(';`([^\']+?)`;S','<code>$1</code>',$line);
	return $line;
}

function parseHTML(LineReader $reader) {
	echo $reader->line;
	$endtag = preg_replace(';^<(\w+).*;',';^</$1;S',$reader->line);
	while (false !== ($line = $reader->getLine())) {
		if (strlen(trim($line)) > 0 && !preg_match(';^\s|<;S',$line)) {
			$reader->rewindLine();
			return;
		}
		echo $line;
		if (preg_match($endtag,$line)) return;
	}
}

function parseCode(LineReader $reader) {
	echo "<pre><code>\n";
	$reader->rewindLine();
	$reader->push("(?: {4}|\t)");
	while (false !== ($line = $reader->readLine())) {
		echo parseLine($line);
	}
	echo "</code></pre>\n";
	$reader->rewindLine();
	$reader->pop();
}

function parseP(LineReader $reader) {
	$p = false;
	while ( false !== ($line = $reader->readLine())) {
		if ($reader->current_block == "" && preg_match(';^<\w;S',$line)) {
			// inline HTML
			if ($p) { echo "</p>"; $p = false; }
			parseHTML($reader);
			continue;
		}
		if (preg_match(';^={1,6}\s*\w+;S',$line)) {
			// H1-H6
			if ($p) { echo "</p>\n"; $p = false; }
			$lvl = strspn($line,"=");
			echo "<h$lvl>",trim($line,"=\n "),"</h$lvl>\n";
			continue;
		}
		if (preg_match(';^-+$;S',$line)) {
			// hr
			if ($p) { echo "</p>\n"; $p = false; }
			echo "<hr>\n";
			continue;
		}
		if (preg_match(';^(?: {4}|\t);',$line)) {
			// pre,code
			if ($p) { echo "</p>\n"; $p = false; }
			parseCode($reader);
			continue;
		}
		if ($line == "\n") {
			if ($p) {
				echo "</p>\n";
				$p = false;
			}
			continue;
		}
		if (!$p) { echo "<p>"; $p = true; }
		echo parseLine($line);
	}
	if ($p) echo "</p>\n";
}

// end namespace XT\impl

namespace XT;

/**
 * Parses a XT file through the PHP interpreter, then transforms XT text to HTML.
 * @param SplFileObject $INPUT inpu stream with source
 * @param \SplFileObject $OUTPUT If set, write the results to it, otherwise the PHP default output will be used
 */
function parse(\SplFileObject $INPUT, \SplFileObject $OUTPUT = NULL ) {
	// parse resulting XT/HTML file to HTML5
	ob_start();
	impl\parseP(new impl\LineReader($INPUT));
	// flush buffer or save contents to output object
	if (is_null($OUTPUT)) {
		ob_end_flush();
	} else {
		$OUTPUT->fwrite(ob_get_contents());
		ob_end_clean();
	}
};

// end namespace XT
