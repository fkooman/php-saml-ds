<?php

/*
 * Copyright 2017,2018,2019  François Kooman <fkooman@tuxed.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\SAML\DS\Http;

use fkooman\SAML\DS\Http\Exception\HttpException;

class Request
{
    /** @var array */
    private $serverData;

    /** @var array */
    private $getData;

    /** @var array */
    private $postData;

    /**
     * @param array $serverData
     * @param array $getData
     * @param array $postData
     */
    public function __construct(array $serverData, array $getData, array $postData)
    {
        $this->serverData = $serverData;
        $this->getData = $getData;
        $this->postData = $postData;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->serverData['REQUEST_METHOD'];
    }

    /**
     * @return string
     */
    public function getServerName()
    {
        return $this->serverData['SERVER_NAME'];
    }

    /**
     * @return array
     */
    public function getQueryParameters()
    {
        return $this->getData;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasQueryParameter($key)
    {
        return \array_key_exists($key, $this->getData) && !empty($this->getData[$key]);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getQueryParameter($key)
    {
        if (!$this->hasQueryParameter($key)) {
            throw new HttpException(\sprintf('query parameter "%s" not provided', $key), 400);
        }

        return $this->getData[$key];
    }

    /**
     * @return array
     */
    public function getPostParameters()
    {
        return $this->postData;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasPostParameter($key)
    {
        return \array_key_exists($key, $this->postData) && !empty($this->postData[$key]);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getPostParameter($key)
    {
        if (!$this->hasPostParameter($key)) {
            throw new HttpException(\sprintf('post parameter "%s" not provided', $key), 400);
        }

        return $this->postData[$key];
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getHeader($key)
    {
        return \array_key_exists($key, $this->serverData) ? $this->serverData[$key] : null;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        $rootDir = \dirname($this->serverData['SCRIPT_NAME']);
        if ('/' !== $rootDir) {
            return \sprintf('%s/', $rootDir);
        }

        return $rootDir;
    }
}
