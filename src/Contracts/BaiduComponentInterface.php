<?php

/*
 * This file is part of the Easeava package.
 *
 * (c) Easeava <tthd@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EaseAva\Socialite\Contracts;

interface BaiduComponentInterface
{
    /**
     * Return the Bear tp component client id.
     *
     * @return string
     */
    public function getClientId();

    /**
     * Return the Bear tp component access token string.
     *
     * @return string
     */
    public function getToken();
}