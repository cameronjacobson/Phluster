<?php

namespace Phluster\Providers;

use \Aws\Ec2\Ec2Client;

class AWS
{
	private $client;
	private $job;

	public function __construct($profile,$region){
		$this->client = Ec2Client::factory(array(
			'profile'=>$profile,
			'region'=>$region
		));
	}

	public function listInstances(){
		var_dump($this->client->describeInstances());
	}

	public function execCommand($command){
		switch($command[0]){
			case 'list':
				$this->listInstances();
				break;
			default:
				$this->out('hello '.implode(' ',$command));
				break;
		}
	}

	public function setJob($jobname){
		$this->job = $jobname;
	}
}
