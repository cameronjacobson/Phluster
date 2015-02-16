<?php

namespace Phluster\Providers\AWS;

trait ListCommands
{
	public static function listImages(){
		$ids = self::getValidImageIds();
		$images = self::$client->describeImages(array(
			'ImageIds'=>$ids
		));
		$names = [];
		foreach($images['Images'] as $image){
			if(strpos($image['Name'],'phluster-') === 0){
				$names[] = ' '.$image['Name'];
			}
		}
		return implode(PHP_EOL,$names) ?: 'None found';
	}

	public static function listInstances(){
		$ids = self::getValidImageIds();
		$instances = self::$client->describeInstances(array(
			'ImageIds'=>$ids
		));
		$instanceids = [];
		foreach($instances['Reservations'] as $reservation){
			foreach($reservation['Instances'] as $instance){
				$instanceids[] = $instance['InstanceId'];
			}
		}
		return implode(PHP_EOL,$instanceids) ?: 'None found';
	}

	private static function getValidImageIds(){
		$images = self::$client->describeImages(array(
			'Filters'=>array(
				array(
					'Name'=>'is-public',
					'Values'=>array('false')
				)
			)
		));
		$ids = [];
		foreach($images['Images'] as $image){
			if(strpos($image['Name'],'phluster-') === 0){
				$ids[] = $image['ImageId'];
			}
		}
		return $ids;
	}
}
