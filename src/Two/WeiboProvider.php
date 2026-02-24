<?php

namespace Laravel\Socialite\Two;

use Illuminate\Support\Arr;
use GuzzleHttp\ClientInterface;
use Config;

class WeiboProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://api.weibo.com';

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
    protected $fields = ['userid', 'username', 'portrait', 'userdetail', 'birthday', 'sex', 'nickname', 'name', 'id'];

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
        return $this->buildAuthUrlFromBase($this->graphUrl.'/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->graphUrl.'/oauth2/access_token';
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
    public function user()
    {
        if ($this->hasInvalidState()) {
            // throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByTokenAndUid(
            $token = Arr::get($response, 'access_token'), $uid = Arr::get($response, 'uid')
				));

        return $user->setToken($token)
                    ->setRefreshToken(Arr::get($response, 'refresh_token'))
                    ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByTokenAndUid($token, $uid)
    {
        $meUrl = $this->graphUrl.'/2/users/show.json?access_token='.$token.'&uid='.$uid;

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

		protected function getUserByToken($token) {
				return null;
		}

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
			'type' => Config::get('const.USERTYPE_WEIBO'),
			'nickname' => isset($user['screen_name']) ? $user['screen_name'] : null, 
			'name' => isset($user['name']) ? $user['name'] : null,
            'email' => isset($user['email']) ? $user['email'] : null, 
			'avatar' => $user['avatar_large'],
			'avatar_original' => $user['avatar_hd'],
			'gender' => $user['gender'] == "m" ? 1 : ($user['gender'] == "f" ? 2 : 0),
			'detail' => Arr::get($user, 'description'),
			'location' => Arr::get($user, 'location'),
			'profileUrl' => 'http://weibo.com/u/' . $user['id'],
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
