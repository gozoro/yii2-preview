# yii2-preview
Yii2 component for resizing, cropping and creating preview image and saving to cache folder.
Used only GD library.

Installation
------------
```code
	composer require gozoro/yii2-preview
```




Configuration
-----
```php

'components' => [

	...

	'preview' => [
		'class' => 'gozoro\preview\PreviewComponent',
		'previewPath' => '/var/www/site/www/preview_cache',
		'previewWebPath' => '/preview_cache',
		'defaultPreview' => 'default.jpg',
	],

	...

],

```


Usage
-----
```php
$filename = "/var/www/site/images/image.jpg";

//Get preview url
$url = Yii::$app->preview->create($filename)->resize(300,300)->crop(200,200)->cache()->url;
print '<img src="'.$url.'">';

//Get preview path
$imagePath = Yii::$app->preview->create($filename)->resize(300,300)->crop(200,200)->cache()->filename;

//Save As
Yii::$app->preview->create($filename)->resize(300,300)->crop(200,200)->saveAs('/var/www/site/images/image2.jpg');

```
Other methods see the link [gozoro/image](https://github.com/gozoro/image)


Configuration for PDF
-----
```php

'components' => [

	...

	'preview' => [
		'class' => 'gozoro\preview\PreviewComponent',
		'previewPath' => '/var/www/site/www/preview_cache',
		'previewWebPath' => '/preview_cache',
		'on beforeOpen' => function($event)
		{
			if($event->extension == 'pdf')
			{
				$pdf_file = $event->filename;
				$hash = 'pdf_'.md5($pdf_file);

				$pdf_image = '/var/www/site/www/preview_cache/'.$hash.'.jpg';

				if(!file_exists($pdf_image))
				{
					system( "/usr/bin/nice -2 /usr/bin/gs -dNOPAUSE -q -dBATCH -dSAFER -sDEVICE=jpeg "
						. " -dJPEGQ=100 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -r150 -dFirstPage=1 -dLastPage=1 "
						. " -sOutputFile=".$pdf_image." ".$pdf_file   );

				}
				$event->filename = $pdf_image;
			}
		},
	],

	...

],

```