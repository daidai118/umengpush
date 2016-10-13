<?php
namespace daidai118\umengpush;

/**
 * Created by PhpStorm.
 * User: daidai
 * Date: 2016/10/10
 * Time: 下午5:02
 */

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;

/*
 * 目前umeng推送其实也就安卓和ios的app key不一样 发送的内容格式有点区别
 *
	 * [
	 * 'alias'=>'',
	 * 'alert'=>$msg,
	 * ]
 *
	 * [
	 * 'alias'=> '',
	 * //通知栏提示
	 * 'ticker'=>'订单更新',
	 * 'title'=>'状态变更通知!',
	 * 'text'=>$msg,
	 * 'after_open'=>'',
	 * ]
 *
 */

class Umeng extends Component
{
	public $umengHost = 'http://msg.umeng.com';
	public $appkey;
	public $secret;
	//客户端适配器(目前使用yii2-client)
	public $clientConfig = ['class' => 'daidai118\umengpush\client'];
	//是否是正式
	public $production;
	const EVENT_AFTER_SEND = "after_send";
	const EVENT_SEND_ERROR = "error_send";
	const Android = 'Android';
	const Ios = 'IOS';
	//推送类型
	private $_type='customizedcast';
	//别名类型
	private $_alias_type;
	//body数据
	private $_data;
	//风格 ios 和安卓
	private $_style=self::Android;
	//别名类别
	public $alias_type;
	public $reponse;

	//广播
	public function broadcastNotification($data)
	{
		$this->setType('broadcast')->setData($data)->send();
	}
	//自定义通知
	public function customNotification($alias, $body)
	{
		$this->setData($body)->send([
			'type'=>'customizedcast',
			'alias'=>$alias,
			'alias_type'=>$this->aliasType
		]);
	}

	public function generateToken()
	{
		$time = time();
		return [
			'appkey' => $this->appkey,
			'timestamp' => $time,
			'validation_token' => md5($this->appkey . $this->secret . $time),
		];
	}

	//按照设备发送
	public function sendNotificationToDevices($device_tokens,$body)
	{
		$this->setData($body)->send([
			'type'=>'listcast',
			'device_tokens'=>$device_tokens,
		]);
	}
	//设定type
	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function setAliasType($val)
	{
		$this->_alias_type = $val;
		return $this;
	}

	public function getAliasType()
	{
		return is_null($this->alias_type) ? $this->_alias_type : $this->alias_type;
	}

	public function setData($body,$ext=null)
	{
		$bodyData = [];
		if($this->style == self::Android){
			$bodyData['payload']['body'] = $body;
			$ext&&$bodyData['extra'] = $ext;
		}else{
			$ext&&$bodyData['payload'] = $ext;
			$bodyData['payload']['aps'] = $body;
		}
		$this->_data = $bodyData;
		return $this;
	}

	public function getData()
	{
		return $this->_data;
	}

	public function setStyle($val)
	{
		$this->_style = $val;
		return $this;
	}

	public function getStyle()
	{
		return $this->_style;
	}


	/**
	 *自定义发送可以走这里
	 * type:unicast,listcast,broadcast,groupcast或customizedcast
	 */
	public function send($data=[])
	{
		$token = $this->generateToken();
		$bodyData = ArrayHelper::merge($data,$this->data);
		foreach ($token as $k => $v) {
			$bodyData[$k] = $v;
		}
		if (!empty($this->type) && in_array($this->type,
				['unicast', 'listcast', 'broadcast', 'groupcast', 'customizedcast'])
		) {
			$bodyData['type'] = $this->type;
		}
		//必填
		if (!isset($bodyData['type'])) {
			return false;
		}
		// 多个alias时用英文逗号分,不能超过50个。
		if (isset($data['alias']) && !empty($data['alias'])) {
			if (is_array($data['alias'])) {
				$bodyData['alias'] = implode(',', $data['alias']);
			} else {
				$bodyData['alias'] = $data['alias'];
			}
		}
		// 必填 消息类型，值为notification或者message
		!isset($data['payload']['display_type']) && $bodyData['payload']['display_type'] = 'notification';
		// 必填 通知栏提示文字。但实际没有用，todo确认
		$data['payload']['body']['title'] = 'test';
		//可选 消息描述。用于友盟推送web管理后台，便于查看。
		!isset($data['description']) && $bodyData['description'] = $data['payload']['body']['title'];

		$defaultTrueParams = [
			// 通知到达设备后的提醒方式
			'play_vibrate', // 可选 收到通知是否震动,默认为"true".注意，"true/false"为字符串
			'play_lights',  // 可选 收到通知是否闪灯,默认为"true"
			'play_sound',   // 可选 收到通知是否发出声音,默认为"true"
		];
		foreach ($defaultTrueParams as $one) {
			if (isset($data['payload']['body'][$one]) && ($data['payload']['body'][$one] == false || $data['payload']['body'][$one] == 'false')) {
				$bodyData['payload']['body'][$one] = 'false';
			}
		}
		//调试模式
		$bodyData['production_mode'] = $this->production;
		$http = new Client([
			'baseUrl' => $this->umengHost,
		]);
		$sign = md5("POST" . $this->umengHost . '/api/send' . Json::encode($bodyData) . $this->secret);
		$respone = $http->createRequest()->setMethod('POST')
			->setFormat(Client::FORMAT_JSON)
			->setUrl('api/send?sign=' . $sign)
			->setContent(Json::encode($bodyData))
			->setHeaders(['content-type' => "application/json"])
			->send();
		if ($respone->statusCode != 200) {
			$this->trigger(self::EVENT_SEND_ERROR);
		}
		$this->reponse = $respone->data;
		$this->trigger(self::EVENT_AFTER_SEND);
	}

	public function getClient()
	{
		return Yii::createObject($this->clientConfig);
	}

}