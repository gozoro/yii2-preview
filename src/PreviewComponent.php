<?php

namespace gozoro\preview;

use Yii;
use gozoro\preview\models\PreviewImage;
use gozoro\preview\models\Event;

/**
 * Preview component.
 * @author gozoro <gozoro@yandex.ru>
 */
class PreviewComponent extends yii\base\Component
{
	const EVENT_BEFORE_OPEN = 'beforeOpen';
	const EVENT_AFTER_OPEN  = 'afterOpen';
	const EVENT_BEFORE_SAVE = 'beforeSave';
	const EVENT_AFTER_SAVE  = 'afterSave';




	/**
	 * Path to folder for creating preview files.
	 * @var string
	 */
	public $previewPath = '@webroot/preview_cache';

	/**
	 * Web path to folder for crating preview files.
	 * @var string
	 */
	public $previewWebPath = '@web/preview_cache';

	/**
	 * Mode of created image files.
	 * @var integer
	 */
	public $mode = 0664;


	/**
	 * Filename to default preview image
	 * @var string
	 */
	public $defaultPreview;


	protected $filename;


	/**
	 * Creating preview image file wrapper
	 * @param string $filename
	 * @return PreviewImage
	 */
	public function create($filename)
	{
		$eventData = [
			'filename' => $filename,
			'extension' => PreviewImage::parseExtension($filename)
		];


		$event = new Event($eventData);
		$this->trigger(self::EVENT_BEFORE_OPEN, $event);


		$filename = $event->filename;
		$preview = new PreviewImage($filename);
		$preview->setComponent($this);

		$preview->on(self::EVENT_BEFORE_SAVE, function(Event $event){
				$this->trigger(self::EVENT_BEFORE_SAVE, $event);
		});

		$preview->on(self::EVENT_AFTER_SAVE, function(Event $event){
				chmod($event->filename, $this->mode);
				$this->trigger(self::EVENT_AFTER_SAVE, $event);
		});

		$this->trigger(self::EVENT_AFTER_OPEN, $event);

		return $preview;
	}
}