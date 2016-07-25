<?php
/*
XT - eXtendable Text Parser/Processor
Requires PHP Version 7 or higher.

Converts markdown/wiki style text file to HTML5, while allowing to use PHP language inside it.

BSD 3-Clause Licence:

Copyright (c) 2016, Rafael Fernandes
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors
   may be used to endorse or promote products derived from this software without
   specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES 
OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT 
SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT 
OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR 
TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, 
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace XT;

class XTParser {

	protected $input;
	protected $rewind = false;
	protected $current_ident = 0;
	protected $line;
	protected $line_original;
	
	protected function parseLine($line) : string {
		return htmlentities($line);
	}
	
	protected function lineAtIdent($ident) : string {
		return preg_replace(';^(?: {4}|\t){'.$ident.'};S','',$this->line_original);
	}
	
	protected function readLine($emptyok = true) : bool {
		if ($this->rewind) {
			$this->rewind = false;
		} else {
			if ($this->input->eof()) {
				// $this->current_ident = 0;
				// $this->line = '';
				// $this->line_original = '';
				return false;
			}
			$this->line_original = $this->input->fgets();
		}
		// find and check line identation
		$line_ident = 0;
		for ($i = 0; $i < \strlen($this->line_original); ++$i) {
			$c = $this->line_original[$i];
			if ($c != ' ' && $c != "\t") break;
			if ($c == ' ' && (\strlen($this->line_original) - $i) >= 4) {
				if ($this->line_original[$i + 1] != ' ' ||
					$this->line_original[$i + 2] != ' ' ||
					$this->line_original[$i + 3] != ' ') break;
				$i += 3;
			}
			$line_ident++;
		}
		$this->line = rtrim($this->lineAtIdent($this->current_ident));
		if ($line_ident < $this->current_ident) {
			if (strlen($this->line) == 0 && $emptyok) {
				// empty lines are ok and just keeps the same identation
				return true;
			}
			// this identation is over
			$this->current_ident = $line_ident;
			return false;
		} else {
			return true;
		}
	}
	
	protected function rewindLine() {
		$this->rewind = !$this->input->eof();
	}
	
	protected function echoP(array &$text) {
		if (empty($text)) return;
		echo '<p>';
		$last = '';
		while (strlen($last) == 0) $last = array_pop($text);
		while (!empty($text)) {
			echo $this->parseLine(array_shift($text)),PHP_EOL;
		}
		echo $this->parseLine($last),'</p>',PHP_EOL;
		//$text = [];// start a new array
	}
	
	protected function parsePre() {
		$this->current_ident++;
		$this->rewindLine();
		echo "<code><pre>\n";
		while ($this->readLine(false)) {
			echo $this->parseLine($this->line),PHP_EOL;
		}
		echo "</pre></code>\n";
		$this->rewindLine(); // re-evaluate line that broke the identation
	}
	
	protected function parseHeading() {
		$h = 0;
		while ( $h < strlen($this->line) &&
				$this->line[$h] == '#') {
			++$h;		
		}
		echo "<h$h>",$this->parseLine(substr(rtrim($this->line,"# "),$h+1)),"</h$h>",PHP_EOL;
	}
	
	protected function echoHTML() {
		$this->rewindLine();
		while ($this->readLine()) {
			if (!preg_match(';^\s|<;',$this->line)) {
				$this->rewindLine();
				break;	
			}
			echo $this->line,"\n";
		}
	}
	
	protected function parseLI() {
		
	}
	
	protected function parseUL() {
		echo "<ul>\n";
		$this->rewindLine();
		while ($this->readLine()) {
			if (!preg_match(';^[*-]\s+\S', $this->line_noident)) break;
			$this->parseLI();
		}
		echo "</ul>\n";
		$this->rewindLine();
	}
	
	protected function parseBlock() {
		$paragraph = [];
		while ($this->readLine()) {
			// start new paragraphs with empty lines
			if (strlen($this->line) == 0) {
				$this->echoP($paragraph);
				continue;
			}
			// skip HTML code
			if (strlen($this->line) && $this->line[0] == "<") {
				$this->echoP($paragraph);
				$this->echoHTML();
				continue;
			}
			// <hr>
			if (preg_match(';^-{3};', $this->line)) {
				$this->echoP($paragraph);
				echo "<hr>\n";
				continue;
			}
			// ul
			if (preg_match(';^\*|-\s+\S;', $this->line)) {
				$this->echoP($paragraph);
				$this->parseUL();
				continue;
			}
			// <h1-6>
			if (preg_match(';^#{1,6}\s+\S;',$this->line)) {
				$this->echoP($paragraph);
				$this->parseHeading();
				continue;
			}
			// <code><pre>			
			if (preg_match(';^ {4}|\t;',$this->line)) {
				$this->echoP($paragraph);
				$this->parsePre();
				continue;
			}
			// adicionar ao paragrafo
			array_push($paragraph,$this->line);
		}
		$this->echoP($paragraph);
	}
	
	// Block-type transformations
	public function parseFrom( \SplFileObject $input ) {
		$this->input = $input;
		$this->parseBlock();
	}
}

/**
 * Start a new scope to include the XT file for PHP processing
 * @param string $FILENAME File to be included
 * @param mixed $VARS Variables available to the PHP script inside the XT file
 * @param bool $___decl When true, will create local variables from the keys in $VARS
 */
function parse_php(string $FILENAME, &$VARS, bool $___decl = false) {
	if ($___decl) {
		unset($__decl);
		// bring keys from $VARS to include scope (creates references)
		extract($VARS,EXTR_SKIP | EXTR_REFS);
	}
	include $FILENAME;
}

/**
 * Parses a XT file through the PHP interpreter, then transforms XT text to HTML.
 * @param string $FILENAME File with the source code
 * @param array $VARS Variables available to the PHP script inside the xt file
 * @param \SplFileObject $OUTPUT If set, write the results to it, otherwise the PHP default output will be used
 * @param bool $declarevars If set, all keys in $VARS will be available as local vars to the PHP script
 */
function parse(string $FILENAME, $VARS = [], \SplFileObject $OUTPUT = NULL, bool $declarevars = false ) {
	// parse PHP in a clean scope and copy result to temporary file
	ob_start();
	parse_php($FILENAME,$VARS,$declarevars);
	$result = new \SplFileObject("php://temp","w+");
	$result->fwrite(ob_get_contents());
	$result->rewind();
	ob_end_clean();
	// parse resulting XT/HTML file to HTML5
	$parser = new XTParser;
	ob_start();
	$parser->parseFrom($result);
	// flush buffer or save contents to output object
	if (is_null($OUTPUT)) {
		ob_end_flush();
	} else {
		$OUTPUT->fwrite(ob_get_contents());
		ob_end_clean();
	}
};
