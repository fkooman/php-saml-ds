<?php

/*
 * Copyright 2017,2018  François Kooman <fkooman@tuxed.net>
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

namespace fkooman\SAML\DS\HttpClient;

use RuntimeException;

class Response
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $responseBody;

    /** @var array */
    private $responseHeaders;

    /**
     * @param int    $statusCode
     * @param string $responseBody
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
            $fmtHdrs .= sprintf('%s: %s', $k, $v).PHP_EOL;
        }

        return implode(
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
     * @return array
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
            if (strtoupper($key) === strtoupper($k)) {
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
        $decodedJson = json_decode($this->responseBody, true);
        if (null === $decodedJson && JSON_ERROR_NONE !== json_last_error()) {
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
