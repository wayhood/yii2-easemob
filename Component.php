<?php

namespace wh\easemob;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\caching\Cache;
use Yii;

class Component extends \yii\base\Component
{
	public $cache = null;

	public $restUrl = 'https://a1.easemob.com/';

	private $_cacheComponent = null;

	private $_cacheKey = '';

	private $_clientId = '';

	private $_clientSecret = '';

	private $_orgName = '';

	private $_appName = '';

	protected function getToken()
	{
		$cache = $this->getCache();

		$token = $this->get($this->getCacheKey());

		if ($token === false) {
			$body = [
				"grant_type" => "client_credentials",
				"client_id" => $this->_clientId,
				"client_secret" => $this->clientSecret;
			];

			$headers['Content-Type'] => 'application/json';
			$result = $this->request('token', json_encode($body), $headers);
			var_dump($result);
			$data = json_decode($result, true);
			$cache->set($this->getCacheKey(), $data['token'], $data['expires_in']);
			$token = $data['token'];
		}
		return $token;
	}

	protected function getCache()
	{
		if (is_null($this->_cacheComponent)) {

			if (is_object($this->cache)) {
				$this->_cacheComponent = $this->cache;
			} else if (is_string($cache)) {
				$this->_cacheComponent = Yii::$app->get($this->cache);
			} else if (is_null($cache)) {
				$this->_cacheComponent = Yii::$app->cache;
			} else if (is_array($cache)) {
				if (!isset($this->cache['class'])) {
					$this->cache['class'] = 'yii\caching\FileCache';
				}
				$this->_cacheComponent = Yii::createObject($this->cache);
			}
		}

		return $this->_cacheComponent();
	}

	/**
	 * 请求
	 */
	protected function request($op, $body, $header)
	{
		$url = $this->restUrl.'/'.$this->_orgName.'/'.$this->_appName.'/'.$op;

        $client = new Client();
        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'body'    => $body
            ]);
			return $response->getBody();
        } catch (RequestException $e) {
            echo $e->getRequest() ."\n";
            if ($e->hasResponse()) {
                echo $e->getResponse()."\n";
            }
        }
	}

	public function setCacheKey($cacheKey)
	{
		$this->cacheKey = $cacheKey;
	}

	public function getCacheKey()
	{
		if ($this->cacheKey == '') {
			$this->cacheKey = 'easemob'.$this->_orgName.$this->_appName.$this->clientId.$this->clientSecret;
		}
		return $this->cacheKey;
	}

	public function setClientId($clientId)
	{
		$this->_clientId = $clientId;
	}

	public function getClientId()
	{
		return $this->_clientId;
	}

	public function setClientSecret($clientSecret)
	{
		$this->_clientSecret = $clientSecret;
	}

	public function getClientSecret()
	{
		return $this->_clientSecret;
	}

	public function setAppName($appName)
	{
		$this->_appName = $appName;
	}

	public function getAppName()
	{
		return $this->_appName;
	}

	public function setOrgName($orgName)
	{
		$this->_orgName = $orgName;
	}

	public function getOrgName()
	{
		return $this->_orgName;
	}



}
