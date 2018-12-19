<?php

/*
 * This file is part of the Easeava package.
 *
 * (c) Easeava <tthd@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EaseAva\Socialite\Providers;

use EaseAva\Socialite\Contracts\BaiduComponentInterface;
use Overtrue\Socialite\User;
use InvalidArgumentException;
use Overtrue\Socialite\AccessTokenInterface;
use Overtrue\Socialite\ProviderInterface;
use Overtrue\Socialite\Providers\AbstractProvider;

/**
 * Class BaiduProvider
 * @package EaseAva\Socialite\Providers
 *
 * @see https://xiongzhang.baidu.com/open/wiki/chapter2/section2.1.html?t=1542016632879 [Baidu - 百度帐号网页授权]
 */
class BaiduProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The base url of Baidu API.
     *
     * @var string
     */
    protected $baseUrl = 'https://openapi.baidu.com';

    /**
     * Version.
     *
     * @var string
     */
    protected $version = '2.0';

    /**
     * @var array
     */
    protected $scopes = ['snsapi_userinfo'];

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = true;

    /**
     * @var BaiduComponentInterface
     */
    protected $component;

    public function component(BaiduComponentInterface $component)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->baseUrl.'/oauth/'.$this->version.'/authorize', $state);
    }

    /**
     * @param null $state
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        if ($this->component) {
            $this->with(['tp_client_id' => $this->component->getClientId()]);
        }

        return array_merge([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'state' => $state ?: md5(time()),
            'pass_no_login' => 1,
        ], $this->parameters);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->baseUrl.'/oauth/'.$this->version.'/token';
    }

    /**
     * Get the user URL for the Provider.
     *
     * @return string
     */
    protected function getUserInfoUrl()
    {
        return $this->baseUrl.'/rest/'.$this->version.'/cambrian/sns/userinfo';
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_filter(array_merge(parent::getTokenFields($code), [
            'grant_type' => $this->component ? 'tp_authorization_code' : 'authorization_code',
            'tp_client_id' => $this->component ? $this->component->getClientId() : null,
            'tp_access_token' => $this->component ? $this->component->getToken() : null,
        ]));
    }

    /**
     * Get the raw user for the given access token.
     *
     * @return array|mixed
     */
    protected function getUserByToken(AccessTokenInterface $token)
    {
        $scopes = explode(',', $token->getAttribute('scope', ''));

        if (in_array('snsapi_base', $scopes)) {
            return $token->toArray();
        }

        if (empty($token['openid'])) {
            throw new InvalidArgumentException('openid of AccessToken is required.');
        }

        $response = $this->getHttpClient()->get($this->getUserInfoUrl(), [
            'query' => array_filter([
                'access_token' => $token->getToken(),
                'openid' => $token['openid'],
            ]),
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'query' => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Overtrue\Socialite\User
     */
    protected function mapUserToObject(array $user)
    {
        return new User([
            'id' => $this->arrayItem($user, 'openid'),
            'name' => $this->arrayItem($user, 'nickname'),
            'nickname' => $this->arrayItem($user, 'nickname'),
            'avatar' => $this->arrayItem($user, 'headimgurl'),
            'email' => null,
            'sex' => $this->arrayItem($user, 'sex'),
            'province' => $this->arrayItem($user, 'province')
        ]);
    }
}
