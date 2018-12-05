<?php

/*
 * This file is part of the Easeava package.
 *
 * (c) Easeava <tthd@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EaseAva\Socialite;

use Overtrue\Socialite\SocialiteManager as OvertrueSocialiteManager;
use Symfony\Component\HttpFoundation\Request;

class SocialiteManager extends OvertrueSocialiteManager
{
    protected $appendDrivers = [
        'baidu' => 'Baidu',
    ];

    public function __construct(array $config, Request $request = null)
    {
        $this->initialDrivers = array_merge($this->initialDrivers, $this->appendDrivers);
        parent::__construct($config, $request);
    }

    /**
     * @param string $driver
     * @return \Overtrue\Socialite\ProviderInterface
     */
    protected function createDriver($driver)
    {
        if (isset($this->initialDrivers[$driver])) {
            $provider = $this->initialDrivers[$driver];
            $suffix = '\\Providers\\'.$provider.'Provider';
            $providerClassName = class_exists(__NAMESPACE__.$suffix)
                ? __NAMESPACE__.$suffix :
                '\\Overtrue\\Socialite'.$suffix ;

            return $this->buildProvider($providerClassName, $this->formatConfig($this->config->get($driver)));
        }

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        throw new \InvalidArgumentException("Driver [$driver] not supported.");
    }
}