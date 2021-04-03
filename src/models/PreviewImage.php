<?php

namespace gozoro\preview\models;

use Yii;
use gozoro\preview\models\Event;

/**
 * Object preview image that returns component Preview, e.g. Yii:$app->preview->create($imagePath)
 *
 * @author gozoro <gozoro@yandex.ru>
 * @property string $url Returns url of preview image file.
 * @property string $filename Returns preview image file name (full path to image file).
 */
class PreviewImage extends \gozoro\image\Image
{
	const EVENT_BEFORE_SAVE = 'beforeSave';
	const EVENT_AFTER_SAVE = 'afterSave';



	/**
	 * Stores a pointer to a component.
	 * @var \gozoro\preview\PreviewComponent
	 */
	private $component;

	/**
	 * Array of operations before saving an image.
	 * @var array
	 */
	private $operations = array();

	/**
	 * A data of the image file to save
	 * @var array
	 */
	private $savingData = array();


    /**
     * Returns the value of a component property.
     *
     * @param string $name the property name
     * @return mixed the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     */
    public function __get($name)
    {
        $getter = 'get'.$name;
        if (method_exists($this, $getter))
		{
            return $this->$getter();
        }

        if(method_exists($this, 'set'.$name))
		{
            throw new \yii\base\InvalidCallException('Getting write-only property: '.get_class($this).'::'.$name);
        }

        throw new \yii\base\UnknownPropertyException('Getting unknown property: '.get_class($this).'::'.$name);
    }

	/**
	 *
	 * @param \gozoro\preview\PreviewComponent $component
	 * @return static
	 */
	public function setComponent(\gozoro\preview\PreviewComponent $component)
	{
		$this->component = $component;
		return $this;
	}

	/**
	 * Creating image resource for default preview
	 * @return resource
	 */
	protected function createDefaultImage()
	{
		$defaultPreview = Yii::getAlias($this->component->defaultPreview);

		if($defaultPreview)
		{
			if(file_exists($defaultPreview))
			{
				$this->setFilename($defaultPreview);
				$ext = $this->getExtension();

				if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
				{
					return $this->createInputImage();
				}
				else
				{
					$this->throwException("Default preview image format must be jpg, jpeg, png or gif.");
				}
			}
			else
			{
				$this->throwException("Default preview not exists.");
			}
		}
		else
		{
			$ext = $this->getExtension();
			$this->throwException("Unknow image format - $ext.");
		}
	}

	/**
	 * Returns original image file.
	 * @return string
	 */
	public function getOriginalFilename()
	{
		return parent::getFilename();
	}

	/**
	 * Returns encrypted image file name.
	 * @return string
	 */
	public function getEncryptName()
	{
		$original = $this->getOriginalFilename();
		$ext = $this->getExtension();
		$key = $original.';';
		foreach($this->operations as $operation)
		{
			$key .= $operation['method'].'(';
			$key .= implode(',', $operation['args']);
			$key .= ');';
		}

		return md5($key).'.'.$ext;
	}

	/**
	 * Returns preview image file name (full path to image file).
	 * @return string
	 */
	public function getFilename()
	{
		$path = Yii::getAlias($this->component->previewPath);
		return $path.'/'.$this->getEncryptName();
	}

	/**
	 * Returns url of preview image file.
	 * @return string
	 */
	public function getUrl()
	{
		$path = Yii::getAlias($this->component->previewWebPath);
		return $path.'/'.$this->getEncryptName();
	}

	/**
	 * Returns orientation name
	 * @return string 'portrait' | 'landscape' | 'square'
	 */
	public function getOrientationName()
	{
		if($this->isSquare())
			return 'square';
		elseif($this->isLandscape())
			return 'landscape';
		else
			return 'portrait';
	}

	/**
	 * Resizing image.
	 *
	 * @param int $maxWidth max width to resizing (px)
	 * @param int $maxHeight  max height to resizing (px)
	 * @param bool $keepAspectRatio if TRUE, preserves the aspect ratio of the image,
	 *                              otherwise allows you to distort the image to achieve dimensions.
	 * @param bool $allowUpscaling if TRUE, allows to increase the image size if necessary
	 *                             over the original size, otherwise will return the original image
	 *                             (with original dimensions).
	 * @return static
	 */
	public function resize($maxWidth = null, $maxHeight = null, $keepAspectRatio = true, $allowUpscaling = false)
	{
		$this->operations[] = [
			'method' => 'resize',
			'args'   => [$maxWidth, $maxHeight, (int)$keepAspectRatio, (int)$allowUpscaling]
		];

		return parent::resize($maxWidth, $maxHeight, $keepAspectRatio, $allowUpscaling);
	}

	/**
	 * Cropping image.
	 *
	 * @param int $width cropping width
	 * @param int $height cropping height
	 * @param int $src_x X coordinate of the top left corner of the cropping (default 0)
	 * @param int $src_y Y coordinate of the top left corner of the cropping (default 0)
	 * @return static
	 */
	public function crop($width, $height, $src_x = 0, $src_y = 0)
	{
		$this->operations[] = [
			'method' => 'crop',
			'args'   => [$width, $height, $src_x, $src_y]
		];

		return parent::crop($width, $height, $src_x, $src_y);
	}

	/**
	 * Saving image file if the file does not already exist.
	 * @return static
	 */
	public function cache()
	{
		$filename = $this->getFilename();
		if(!file_exists($filename))
		{
			$this->save();
		}

		return $this;
	}

	/**
	 * Runs before save image. Triggers event handlers.
	 * @return boolean
	 */
	public function beforeSave()
	{
		if(parent::beforeSave())
		{
			$event = new Event($this->savingData);
			Event::trigger($this, self::EVENT_BEFORE_SAVE, $event);
			return true;
		}
		else
			return false;
	}

	/**
	 * Runs after save image. Triggers event handlers.
	 * @return boolean
	 */
	public function afterSave()
	{
		if(parent::afterSave())
		{
			$event = new Event($this->savingData);
			Event::trigger($this, self::EVENT_AFTER_SAVE, $event);
			return true;
		}
		else
			false;
	}

	/**
	 * Attaches an event handler to an event.
	 *
     * @param string $eventName the event name.
     * @param callable $handler the event handler.
     * @param mixed $eventData the data to be passed to the event handler when the event is triggered.
     * @param bool $append whether to append new event handler to the end of the existing
     * handler list. If `false`, the new handler will be inserted at the beginning of the existing
     * handler list.
	 * @return static
	 */
	public function on($eventName, $handler, $eventData=null, $append=true)
	{
		Event::on(static::class, $eventName, $handler, $eventData, $append);
		return $this;
	}

	/**
	 * Saving image to $filename.
	 *
	 * @param string $filename full path to destination image file with extensions jpeg, jpg, png, gif.
	 * @return bool Returns TRUE if saving is success.
	 * @throws ImageException
	 */
	public function saveAs($filename)
	{

		$this->savingData = array(
			'filename'  => $filename,
			'extension' => self::parseExtension($filename)
		);

		return parent::saveAs($filename);
	}

	/**
	 * Throws overrides image exception
	 * @param type $message
	 * @throws ImageException
	 */
	protected function throwException($message)
	{
		throw new ImageException($message);
	}
}

/**
 * Yii image exception
 */
class ImageException extends \yii\base\Exception{}