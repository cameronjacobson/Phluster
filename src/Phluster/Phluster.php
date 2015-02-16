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

	public function __construct(){
		$this->prompt = $this->color('blue','phluster > ');
		$this->fin = fopen('php://stdin','r');
		$this->fout = fopen('php://stdout','w');
		$this->key = array();
		self::$PROJECT_DIR = dirname(dirname(__DIR__));
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
			switch(strtolower($command[0])){
				case 'quit':
				case 'exit':
					$this->outcolorln('bluebg','goodbye!');
					exit;
					break;
				case 'createjob':
					$this->createJob($command[1]);
					break;
				case 'loadjob':
					$this->job = $command[1];
					$this->loadJobConfig($command[1]);
					break;
				case 'deletejob':
					$this->deleteJob(@$command[1]);
					break;
				case 'savejob':
					$this->saveJobConfig();
					break;
				case 'usekey':
					$this->key['priv'] = file_get_contents('~/.ssh/'.$command[1]);
					$this->key['pub'] = file_get_contents('~/.ssh/'.$command[1].'.pub');
					break;
				case 'provider':
					$this->setProvider($command);
					break;
				case 'listjobs':
					$this->listJobs();
					break;
				default:
					if($this->isReady()){
						$this->provider->execCommand($command);
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
			case 'redbg':
				return "\033[41m";
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

	private function error($err){
		$this->outcolor('redbg',$err);
	}

	private function loadJobConfig($jobname){
		if(empty($jobname)){
			throw new Exception('Invalid Job');
		}
		$filename = $this->getJobFileName($jobname);
		if(file_exists($filename)){
			$this->jobconfig = parse_ini_file($filename,true);
		}
		else{
			throw new Exception('Job does not exist');
		}
	}

	private function createJob($jobname){
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
			if($ini['deleted']){
				$this->outln($ini['name']);
			}
		}
	}

	private function removeDeletedJobs(){
		
	}

	private function setProvider($command){
		if(!empty($this->jobconfig['provider'])){
			throw new Exception('Provider already chosen for this job');
		}
		switch(strtolower($command[1])){
			case 'aws':
				$this->provider = new AWS($command[2],$command[3]);
				break;
			default:
				throw new Exception('Invalid Provider');
				break;
		}
		$this->jobconfig['provider'] = array(
			'name'=>$command[1],
			'profile'=>$command[2],
			'region'=>$command[3]
		);
	}
}
