<?php

namespace Phluster\Providers;

use \Phluster\Commands\ListCommandProcessor;
use \Aws\Ec2\Ec2Client;
use \Phluster\Phluster;

class AWS
{
	use \Phluster\Providers\AWS\ListCommands;

	public static $client;
	private static $job;

	public function __construct($profile,$region){
		self::$client = Ec2Client::factory(array(
			'profile'=>$profile,
			'region'=>$region
		));
	}

	public function execCommand($command,Phluster $phluster){
		switch($head = strtolower(array_shift($command))){
			case 'list':
				$processor = new ListCommandProcessor($phluster->getProvider());
				return $processor->process($command);
				break;
			default:
				array_unshift($command,$head);
				$phluster->out('hello '.implode(' ',$command));
				break;
		}
	}

	public function setJob($jobname){
		$this->job = $jobname;
	}
}
