<?php

namespace gozoro\preview\models;



/**
 * Image event
 */
class Event extends \yii\base\Event
{
	/**
	 * File name to save.
	 * @var string
	 */
	public $filename;

	/**
	 * Extension of image file. Used strtolower().
	 * @var string
	 */
	public $extension;
}

