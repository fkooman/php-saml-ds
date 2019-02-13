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

namespace fkooman\SAML\DS\HttpClient;

use RuntimeException;

class Response
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $responseBody;

    /** @var array<string,string> */
    private $responseHeaders;

    /**
     * @param int                  $statusCode
     * @param string               $responseBody
     * @param array<string,string> $responseHeaders
     */
    public function __construct($statusCode, $responseBody, array $responseHeaders = [])
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $fmtHdrs = '';
        foreach ($this->responseHeaders as $k => $v) {
            $fmtHdrs .= \sprintf('%s: %s', $k, $v).PHP_EOL;
        }

        return \implode(
            PHP_EOL,
            [
                $this->statusCode,
                '',
                $fmtHdrs,
                '',
                $this->responseBody,
            ]
        );
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getHeader($key)
    {
        foreach ($this->responseHeaders as $k => $v) {
            if (\strtoupper($key) === \strtoupper($k)) {
                return $v;
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function json()
    {
        $decodedJson = \json_decode($this->responseBody, true);
        if (null === $decodedJson && JSON_ERROR_NONE !== \json_last_error()) {
            // XXX better exception!!!
            throw new RuntimeException('unable to decode JSON');
        }

        return $decodedJson;
    }

    /**
     * @return bool
     */
    public function isOkay()
    {
        return 200 <= $this->statusCode && 300 > $this->statusCode;
    }
}
