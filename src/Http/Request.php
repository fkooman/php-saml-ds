<?php

/*
 * Copyright (c) 2019 François Kooman <fkooman@tuxed.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace fkooman\SAML\DS\Http;

use fkooman\SAML\DS\Http\Exception\HttpException;

class Request
{
    /** @var array<string,string> */
    private $serverData;

    /** @var array<string,string> */
    private $getData;

    /** @var array<string,string> */
    private $postData;

    /**
     * @param array<string,string> $serverData
     * @param array<string,string> $getData
     * @param array<string,string> $postData
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
     * @return array<string,string>
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
     * @return array<string,string>
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
