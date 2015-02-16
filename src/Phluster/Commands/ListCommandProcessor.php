<?php

namespace Phluster\Commands;

class ListCommandProcessor
{
	private $provider;

	public function __construct($provider){
		$this->provider = $provider;
	}

	public function process($command){
		switch($head = strtolower(array_shift($command))){
			case 'images':
				return $this->provider->listImages();
				break;
			case 'instances':
				return $this->provider->listInstances();
				break;
			default:
				break;
		}
	}
}
