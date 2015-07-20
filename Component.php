<?php

namespace wh\easemob;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\caching\Cache;
use Yii;

class Component extends \yii\base\Component
{
	public $cache = null;

	public $restUrl = 'https://a1.easemob.com';

	private $_cacheComponent = null;

	private $_cacheKey = '';

	private $_clientId = '';

	private $_clientSecret = '';

	private $_orgName = '';

	private $_appName = '';

    /**
     * 发送文本信息
     * @param $message
     * @param $targets
     * @return mixed
     */
    public function sendTextMessage($message, $targets)
    {
        $token = $this->getToken();

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];

        $body = [
            'target_type' => "users",
            'target' => $targets,
            'msg' => [
                'type' => "txt",
                "msg" => $message
            ],
            'from' => "ihaola"
        ];

        $result = $this->requestPost('messages', json_encode($body), $headers);
        return @json_decode($result, true);
    }

    /**
     * 注册用户
     * @param $username
     * @param $password
     * @param null $nickname
     * @return mixed
     */
    public function addUser($username, $password, $nickname = null)
    {
        $token = $this->getToken();

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];

        $body = [
            'username' => $username,
            'password' => $password
        ];

        if (!is_null($nickname)) {
            $body['nickname'] = $nickname;
        }

        $result = $this->requestPost('users', json_encode($body), $headers);
        return @json_decode($result, true);
    }

    /**
     * @param $data
     *   [
     *      ['username' => 'n1', 'password' => 'p1'],
     *      ['username' => 'n2', 'password' => 'p2']
     *   ]
     */
    public function bulkAddUser($data = [])
    {
        $token = $this->getToken();

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];

        $result = $this->requestPost('users', json_encode($data), $headers);
        return @json_decode($result, true);
    }

    /**
     * 获取用户信息
     * @param $username
     * @return mixed
     */
    public function getUser($username)
    {
        $token = $this->getToken();

        $headers = [
            'Authorization' => "Bearer $token"
        ];

        $result = $this->requestGet("users/$username", [], $headers);
        return @json_decode($result, true);
    }

    /**
     * 获取用户列表
     * @param int $limit
     * @param null $cursor
     */
    public function bulkGetUser($limit = 20, $cursor = null)
    {
        $token = $this->getToken();

        $headers = [
            'Authorization' => "Bearer $token"
        ];

        $params = [
            'limit' => $limit
        ];
        if (!is_null($cursor)) {
            $params['cursor'] = $limit;
        }
        $result = $this->requestGet("users", $params, $headers);
        return @json_decode($result, true);
    }

    /**
     * 删除指定用户
     * @param $username
     * @return mixed
     */
    public function deleteUser($username)
    {
        $token = $this->getToken();
        $headers = [
            'Authorization' => "Bearer $token"
        ];
        $result = $this->requestDelete("users/$username", $headers);
        return @json_decode($result, true);
    }

    public function bulkDeleteUser($limit = 5)
    {
        //
    }

    /**
     * 设置用户密码
     * @param $username
     * @param $password
     * @return mixed
     */
    public function setUserPassword($username, $password)
    {
        $token = $this->getToken();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];
        $body = [
            'newpassword' => $password
        ];
        $result = $this->requestPut("users/$username/password", json_encode($body), $headers);
        return @json_decode($result, true);
    }

    /**
     * 设置用户昵称
     * @param $username
     * @param $nickname
     * @return mixed
     */
    public function setUserNickname($username, $nickname)
    {
        $token = $this->getToken();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];
        $body = [
            'nickname' => $nickname
        ];
        $result = $this->requestPut("users/$username", json_encode($body), $headers);
        return @json_decode($result, true);
    }

    /**
     * 添加好友
     * @param $username
     * @param $friendUsername
     * @return mixed
     */
    public function addUserFriend($username, $friendUsername)
    {
        $token = $this->getToken();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];

        $result = $this->requestPost("users/$username/contacts/users/$friendUsername", '', $headers);
        return @json_decode($result, true);
    }

    public function deleteUserFriend($username, $friendUsername)
    {
        $token = $this->getToken();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $token"
        ];

        $result = $this->requestDelete("users/$username/contacts/users/$friendUsername", $headers);
        return @json_decode($result, true);
    }

    public function getUserFriend($username)
    {
        $token = $this->getToken();
        $headers = [
            'Authorization' => "Bearer $token"
        ];
        $result = $this->requestGet("users/$username/contacts/users", [], $headers);
        return @json_decode($result, true);
    }

    /**
     * 获取token
     * @return mixed
     */
    protected function getToken()
	{
		$cache = $this->getCache();
		$token = $cache->get($this->getCacheKey());

		if (empty($token)) {
			$body = [
				"grant_type" => "client_credentials",
				"client_id" => $this->_clientId,
				"client_secret" => $this->_clientSecret
			];
			$headers['Content-Type'] = 'application/json';
			$result = $this->requestPost('token', json_encode($body), $headers);
			$data = json_decode($result, true);

			$cache->set($this->getCacheKey(), $data['access_token'], $data['expires_in']);
			$token = $data['access_token'];
		}
		return $token;
	}

	protected function getCache()
	{
		if (is_null($this->_cacheComponent)) {

			if (is_object($this->cache)) {
				$this->_cacheComponent = $this->cache;
			} else if (is_string($this->cache)) {
				$this->_cacheComponent = Yii::$app->get($this->cache);
			} else if (is_null($this->cache)) {
				$this->_cacheComponent = Yii::$app->cache;
			} else if (is_array($this->cache)) {
				if (!isset($this->cache['class'])) {
					$this->cache['class'] = 'yii\caching\FileCache';
				}
				$this->_cacheComponent = Yii::createObject($this->cache);
			}
		}

		return $this->_cacheComponent;
	}

	/**
	 * 请求 post
	 */
	protected function requestPost($op, $body, $headers)
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

    protected function requestGet($op, $params, $headers)
    {
        $url = $this->restUrl.'/'.$this->_orgName.'/'.$this->_appName.'/'.$op;

        if (!empty($params)) {
            $getParams = [];
            foreach($params as $key =>  $value) {
                $getParams[] = $key .'='. urlencode($value);
            }
            $url .= '?'. join('&', $getParams);
        }
        $client = new Client();
        try {
            $response = $client->get($url, [
                'headers' => $headers,
            ]);
            return $response->getBody();
        } catch (RequestException $e) {
            echo $e->getRequest() ."\n";
            if ($e->hasResponse()) {
                echo $e->getResponse()."\n";
            }
        }
    }

    protected function requestDelete($op, $headers)
    {
        $url = $this->restUrl.'/'.$this->_orgName.'/'.$this->_appName.'/'.$op;
        $client = new Client();
        try {
            $response = $client->delete($url, [
                'headers' => $headers,
            ]);
            return $response->getBody();
        } catch (RequestException $e) {
            echo $e->getRequest() ."\n";
            if ($e->hasResponse()) {
                echo $e->getResponse()."\n";
            }
        }
    }

    protected function requestPut($op, $body, $headers)
    {
        $url = $this->restUrl.'/'.$this->_orgName.'/'.$this->_appName.'/'.$op;
        $client = new Client();
        try {
            $response = $client->put($url, [
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
		$this->_cacheKey = $cacheKey;
	}

	public function getCacheKey()
	{
		if ($this->_cacheKey == '') {
			$this->_cacheKey = 'easemob'.$this->_orgName.$this->_appName.$this->_clientId.$this->_clientSecret;
		}
		return $this->_cacheKey;
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
