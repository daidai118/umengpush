<?php
namespace daidai118\umengpush;
/**
 * Created by PhpStorm.
 * User: daidai
 * Date: 2016/10/10
 * Time: 下午5:02
 */

use yii\base\Component;

class Umeng extends Component
{
	public $api_uri_prefix = 'http://msg.umeng.com/';
	public $appkey;
	public $app_master_secret;
	//客户端适配器(目前使用yii2-client)
	public $client;
}