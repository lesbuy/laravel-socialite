<?php

namespace Laravel\Socialite\Two;

use Illuminate\Support\Arr;
use GuzzleHttp\ClientInterface;
use Config;

class BaiduProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://openapi.baidu.com';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = '2.0';

    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['userid', 'username', 'portrait', 'userdetail', 'birthday', 'sex', 'nickname', 'name', 'id', 'openid', 'unionid'];

    /**
     * Display the dialog in a popup view.
     *
     * @var bool
     */
    protected $popup = false;

    /**
     * Re-request a declined permission.
     *
     * @var bool
     */
    protected $reRequest = false;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->graphUrl.'/oauth/'.$this->version.'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->graphUrl.'/oauth/'.$this->version.'/token';
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return Arr::add(
            parent::getTokenFields($code), 'grant_type', 'authorization_code'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
//        $meUrl = $this->graphUrl.'/rest/'.$this->version.'/passport/users/getInfo?access_token='.$token.'&fields='.implode(',', $this->fields);
        $meUrl = $this->graphUrl.'/rest/'.$this->version.'/passport/users/getInfo?get_unionid=1&access_token='.$token;
		echo $meUrl . "\n";

/*
        if (! empty($this->clientSecret)) {
            $appSecretProof = hash_hmac('sha256', $token, $this->clientSecret);

            $meUrl .= '&appsecret_proof='.$appSecretProof;
        }
*/
        $response = $this->getHttpClient()->get($meUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['userid'],
			'type' => Config::get('const.USERTYPE_BAIDU'),
			'nickname' => null, 
			'name' => isset($user['username']) ? $user['username'] : null,
			'email' => Arr::get($user, 'email'), 
			'avatar' => "https://himg.baidu.com/sys/portrait/item/" . substr($user['portrait'], 0, 4) . substr($user['portrait'], -4),
			'avatar_original' => "https://himg.baidu.com/sys/portraitl/item/" . substr($user['portrait'], 0, 4) . substr($user['portrait'], -4),
			'gender' => isset($user['sex']) && ($user['sex'] == 1 || $user['sex'] == 2) ? $user['sex'] : 0,
			'birth' => $user['birthday'] != "0000-00-00" ? $user['birthday'] : null,
			'detail' => Arr::get($user, 'userdetail'),
			'openid' => Arr::get($user, 'openid'),
			'unionid' => Arr::get($user, 'unionid'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        if ($this->popup) {
            $fields['display'] = 'popup';
        }

        if ($this->reRequest) {
            $fields['auth_type'] = 'rerequest';
        }

        return $fields;
    }

    /**
     * Set the user fields to request from Facebook.
     *
     * @param  array  $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the dialog to be displayed as a popup.
     *
     * @return $this
     */
    public function asPopup()
    {
        $this->popup = true;

        return $this;
    }

    /**
     * Re-request permissions which were previously declined.
     *
     * @return $this
     */
    public function reRequest()
    {
        $this->reRequest = true;

        return $this;
    }
}
