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
	public $umengHost = 'http://msg.umeng.com/';
	public $appkey;
	public $secret;
	//客户端适配器(目前使用yii2-client)
	public $clientConfig = ['class'=>'daidai118\umengpush\client'];
	public $type='customizedcast';
	//是否是正式
	public $production;

	//广播
	public function broadcastNotification($data)
	{
		$bodyData = [
			'type' => 'broadcast',
			'payload' => [
				'body' => [
					'title' => $data['title'],
					'text' => $data['text'],
				]
			]
		];
		return $this->send($bodyData);
	}
	public function generateToken(){
		$time = time();
		return [
			'appkey' => $this->appkey,
			'timestamp' => $time ,
			'validation_token' => md5($this->appkey . $this->secret . $time),
		];
	}

	//按照设备发送
	public function sendNotificationToDevices($data)
	{
		if (!isset($data['device_tokens']) || empty($data['device_tokens'])) {
			throw new Exception('need param: device_tokens');
		}
		$bodyData = [
			'type' => 'listcast',
			'payload' => [
				'body' => [
					'title' => $data['title'],
					'text' => $data['text'],
				]
			],
		];
		if (is_array($data['device_tokens'])) {
			$bodyData['device_tokens'] = implode(',', $data['device_tokens']);
		} else {
			$bodyData['device_tokens'] = $data['device_tokens'];
		}
		return $this->send($bodyData);
	}
	//设定type
	public function setType($type){
		$this->type = $type;
		return $this;
	}

	/**
	 *自定义发送可以走这里
	 * type:unicast,listcast,broadcast,groupcast或customizedcast
	 */
	public function send($data)
	{
		$token = $this->generateToken();
		$bodyData = $data;
		foreach ($token as $k => $v) {
			$bodyData[$k] = $v;
		}
		//必填
		if (!isset($data['type'])) {
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
		!isset($data['payload']['display_type'])&&$bodyData['payload']['display_type'] = 'notification';
		// 必填 通知栏提示文字。但实际没有用，todo确认
		!isset($data['payload']['body']['ticker'])&& $bodyData['payload']['body']['ticker'] = $data['payload']['body']['title'];
		//可选 消息描述。用于友盟推送web管理后台，便于查看。
		!isset($data['description'])&& $bodyData['description'] = $data['payload']['body']['title'];

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
		$http = new Client();
		$http->baseUrl = $this->umengHost;
		$http->post('api/send',$bodyData,['content-type' => 'application/json'])->send();

		if ($http->statusCode != 200) {
			print_r($http->content);
		}
		$respone = $http->data;
		if (!isset($respone['ret']) || $respone['ret'] != 'SUCCESS') {
			print_r($http->content);
		}
		return true;
	}
	public function getClient()
	{
		return Yii::createObject($this->clientConfig);
	}

}