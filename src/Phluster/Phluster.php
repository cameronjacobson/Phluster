<?php

namespace Phluster;

use Phluster\Providers\AWS;
use \Exception;

class Phluster
{
	private $fin;
	private $fout;
	private $prompt;
	private $key;
	private $job;
	private $jobconfig;

	private static $PROJECT_DIR;
	private static $HELP_FILE;

	public function __construct(){
		$this->prompt = $this->color('blue','phluster > ');
		$this->fin = fopen('php://stdin','r');
		$this->fout = fopen('php://stdout','w');
		$this->key = array();
		self::$PROJECT_DIR = dirname(dirname(__DIR__));
		self::$HELP_FILE = self::$PROJECT_DIR.'/files/help.txt';
	}

	public function CLI(){
		$this->prompt();
		while($command = $this->getCommand()){
			$this->execCommand($command);
			$this->promptln();
		}
	}

	private function execCommand($command){
		try{
			switch($head = strtolower(array_shift($command))){
				case 'quit':
				case 'exit':
					$this->outcolorln('bluebg','goodbye!');
					exit;
					break;
				case 'createjob':
					$this->createJob($command[0]);
					break;
				case 'loadjob':
					$this->job = $command[0];
					$this->loadJobConfig($command[0]);
					break;
				case 'deletejob':
					$this->deleteJob(@$command[0]);
					break;
				case 'savejob':
					$this->saveJobConfig();
					break;
				case 'usekey':
					$this->key['priv'] = file_get_contents('~/.ssh/'.$command[0]);
					$this->key['pub'] = file_get_contents('~/.ssh/'.$command[0].'.pub');
					break;
				case 'provider':
					$this->setProvider($command);
					break;
				case 'listjobs':
					$this->listJobs();
					break;
				case 'listdeletedjobs':
					$this->listDeletedJobs();
					break;
				case 'removedeletedjobs':
					$this->removeDeletedJobs();
					break;
				case 'help':
					$this->outcolor('green',file_get_contents(self::$HELP_FILE));
					break;
				default:
					array_unshift($command,$head);
					if($this->isReady()){
						$this->outcolorln('green',PHP_EOL.$this->provider->execCommand($command,$this));
					}
					else{
						throw new Exception('Invalid Command');
					}
					break;
			}
		}
		catch(Exception $e){
			$this->error($e->getMessage());
		}
	}

	private function isReady(){
		if(empty($this->provider)){
			throw new Exception('No cloud provider selected');
		}
		if(empty($this->provider->job)){
			if(empty($this->job)){
				throw new Exception('No job is currently loaded');
			}
			$this->provider->setJob($this->job);
		}
		return true;
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

	public function out($data){
		fwrite($this->fout,$data);
	}

	public function outln($data){
		fwrite($this->fout,$data.PHP_EOL);
	}

	public function color2code($color){
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
			case 'redbg':
				return "\033[41m";
				break;
		}
	}

	public function textreset(){
		return "\033[0m";
	}

	public function color($color,$data){
		return $this->color2code($color).$data.$this->textreset();
	}

	public function outcolor($color,$data){
		$this->out($this->color($color,$data));
	}

	public function outcolorln($color,$data){
		$this->outln($this->color($color,$data));
	}

	public function error($err){
		$this->outcolor('redbg',$err);
	}

	private function loadJobConfig($jobname){
		if(empty($jobname)){
			throw new Exception('Invalid Job');
		}
		$filename = $this->getJobFileName($jobname);
		if(file_exists($filename)){
			$this->jobconfig = parse_ini_file($filename,true);
			$this->provider = null;
			if(!empty($this->jobconfig['provider'])){
				$this->setProvider(array(
					$this->jobconfig['provider']['name'],
					$this->jobconfig['provider']['profile'],
					$this->jobconfig['provider']['region']
				),true);
			}
		}
		else{
			throw new Exception('Job does not exist');
		}
	}

	private function createJob($jobname){
		$this->provider = null;
		$filename = $this->getJobFileName($jobname);
		touch($filename);
		$this->jobconfig = array('name'=>$jobname);
		$this->saveJobConfig();
	}

	private function getJobFileName($jobname){
		return self::$PROJECT_DIR.'/config/jobs/'.$jobname.'.ini';
	}

	private function deleteJob($jobname = null){
		if($jobname = $this->jobname($jobname)){
			$this->provider = null;
			$this->loadJobConfig($jobname);
			$this->jobconfig['deleted'] = true;
			$this->saveJobConfig();
		}
	}

	private function jobname($jobname){
		return empty($jobname) ? $this->job : $jobname;
	}

	private function saveJobConfig(){
		if(!empty($this->jobconfig['name'])){
			$ini = array();
			$ini[] = $this->writeIniValue('string','name',$this->jobconfig['name']);
			$ini[] = $this->writeIniValue('bool','deleted',$this->jobconfig['deleted']);
			foreach($this->jobconfig as $key=>$values){
				if(in_array($key,array('name','deleted'))){
					continue;
				}
				$ini[] = $this->writeIniGroup($key);
				foreach($values as $k=>$v){
					$ini[] = $this->writeIniValue('string',$k,$v);
				}
			}
			$filename = self::$PROJECT_DIR.'/config/jobs/'.$this->jobconfig['name'].'.ini';
			file_put_contents($filename,implode(PHP_EOL,$ini));
		}
	}

	private function writeIniValue($type,$key,$value){
		switch($type){
			case 'bool':
			case 'boolean':
				return $key.'='.(empty($this->jobconfig['deleted']) ? 'false' : 'true');
				break;
			case 'number':
				return $key.'='.(int)$value;
				break;
			case 'string':
				return $key.'="'.$value.'"';
				break;
		}
	}

	private function writeIniGroup($groupname){
		return PHP_EOL.'['.$groupname.']';
	}

	private function listJobs(){
		foreach(glob(self::$PROJECT_DIR.'/config/jobs/*.ini') as $filename){
			$ini = parse_ini_file($filename,true);
			if($ini['deleted']){
				continue;
			}
			$this->outln($ini['name']);
		}
	}

	private function listDeletedJobs(){
		foreach(glob(self::$PROJECT_DIR.'/config/jobs/*.ini') as $filename){
			$ini = parse_ini_file($filename,true);
			if($ini['deleted']){
				$this->outln($ini['name']);
			}
		}
	}

	private function removeDeletedJobs(){
		foreach(glob(self::$PROJECT_DIR.'/config/jobs/*.ini') as $filename){
			$ini = parse_ini_file($filename,true);
			if($ini['deleted']){
				unlink($filename);
			}
		}
	}

	private function setProvider($command,$fromconfig = false){
		if(!$fromconfig && !empty($this->jobconfig['provider'])){
			throw new Exception('Provider already chosen for this job');
		}
		switch(strtolower($command[0])){
			case 'aws':
				$this->provider = new AWS($command[1],$command[2]);
				break;
			default:
				throw new Exception('Invalid Provider');
				break;
		}
		$this->jobconfig['provider'] = array(
			'name'=>$command[0],
			'profile'=>$command[1],
			'region'=>$command[2]
		);
	}

	public function getProvider(){
		return $this->provider;
	}
}
