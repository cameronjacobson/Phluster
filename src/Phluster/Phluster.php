<?php

namespace Phluster;

class Phluster
{
	private $fin;
	private $fout;
	private $prompt;

	public function __construct(){
		$this->prompt = $this->color('blue','phluster > ');
		$this->fin = fopen('php://stdin','r');
		$this->fout = fopen('php://stdout','w');
	}

	public function CLI(){
		$this->prompt();
		while($command = $this->getCommand()){
			$this->execCommand($command);
			$this->promptln();
		}
	}

	private function execCommand($command){
		switch(strtolower($command[0])){
			case 'quit':
			case 'exit':
				$this->outcolorln('bluebg','goodbye!');
				exit;
				break;
			default:
				$this->out('hello '.implode(' ',$command));
				break;
		}
	}

	private function getCommand(){
		$line = fgets($this->fin);
		return preg_split("|\s|",$line,null,PREG_SPLIT_NO_EMPTY);
	}

	private function isComplete(){
		return true;
	}

	private function prompt(){
		$this->out($this->prompt);
	}

	private function promptln(){
		$this->out(PHP_EOL.$this->prompt);
	}

	private function out($data){
		fwrite($this->fout,$data);
	}

	private function outln($data){
		fwrite($this->fout,$data.PHP_EOL);
	}

	private function color2code($color){
		switch($color){
			case 'red':
				return "\033[31m";
				break;
			case 'green':
				return "\033[32m";
				break;
			case 'purple':
				return "\033[35m";
				break;
			case 'blue':
				return "\033[34m";
				break;
			case 'bluebg':
				return "\033[44m";
				break;
		}
	}

	private function textreset(){
		return "\033[0m";
	}

	private function color($color,$data){
		return $this->color2code($color).$data.$this->textreset();
	}

	private function outcolor($color,$data){
		$this->out($this->color($color,$data));
	}

	private function outcolorln($color,$data){
		$this->outln($this->color($color,$data));
	}
}
