友盟推送

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).


```
composer require daidai118/yii2-umengpush
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :
追加到components中
```

		'push'=>[
			'class' => 'daidai118\umengpush\Umeng',
			 'appkey'=>'xxxx',
	         'secret'=>'xxxx',
			 'alias_type'=>'fs',
			 'production'=>false,
		],

```
往ios发送别名消息 默认为安卓
```
Yii::$app->pushios->setStyle(Umeng::Ios)->customNotification('fs2',[
			'ticker'=>'abcdef',
			'title'=>'abcdef!',
			'text'=>'a什么鬼'.date("h:i:s"),
		]);
Yii::$app->pushios->customNotification('fs2',[
			'ticker'=>'abcdef',
			'title'=>'abcdef!',
			'text'=>'a什么鬼'.date("h:i:s"),
		]);

```
广播消息
```
		Yii::$app->push->broadcastNotification([
			'ticker'=>'abcdef',
			'title'=>'abcdef!',
			'text'=>'a什么鬼'.date("h:i:s"),
		]);
```
按照设备推送
```
Yii::$app->push->sendNotificationToDevices('devices_token',[
			'ticker'=>'abcdef',
			'title'=>'abcdef!',
			'text'=>'a什么鬼'.date("h:i:s"),
		]);
```
