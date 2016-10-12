<?php
namespace daidai118\umengpush\client;
/**
 * Created by PhpStorm.
 * User: daidai
 * Date: 2016/10/10
 * Time: 下午6:06
 */
interface Client
{
	public function setBodyData();
	public function send();
	public function setUrl();
	public function setMime();
}