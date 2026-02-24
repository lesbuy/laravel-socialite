<?php

namespace Laravel\Socialite\Two;

use Illuminate\Support\Arr;
use GuzzleHttp\ClientInterface;
use Config;

class WuhanProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['wxid'],
			'type' => Config::get('const.USERTYPE_WX_WUHAN'),
			'openid' => $user['openid'],
			'nickname' => $user['name'], 
			'name' => $user['name'],
			'email' => null, 
			'avatar' => null,
			'avatar_original' => null,
			'gender' => 0,
			'birth' => null,
			'detail' => null,
			'redirect' => $user['redirect'],
        ]);
    }

	protected function decrypt($cookie) {

		if (!$cookie) return false;

		$str = phpdecrypt($cookie);
		if (!$str) return false;

		$arr = explode("\t", $str);
		if (count($arr) != 4) return false;

		if ($arr[3] != Config::get('const.USERTYPE_WX_WUHAN')) return false;

		return ['id' => $arr[0], 'wxid' => $arr[1], 'name' => $arr[2], ];
	}

    /**
     * {@inheritdoc}
     */
    public function user()
    {
		$user = $this->decrypt($this->getCode());

		if ($user) {
			$redirect = urldecode($this->request->input('redirect_uri'));
			$user['redirect'] = $redirect;
			$user['openid'] = $this->request->input('openid');

	        $user = $this->mapUserToObject($user);
		}

        return $user;
    }

	protected function getAuthUrl($state) {

	}

	protected function getTokenUrl() {

	}

	protected function getUserByToken($token) {

	}
}
