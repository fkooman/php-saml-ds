<?php
/**
 *  Copyright (C) 2017 François Kooman <fkooman@tuxed.net>.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace fkooman\SAML\DS\HttpClient;

use RuntimeException;

class CurlHttpClient implements HttpClientInterface
{
    /** @var resource */
    private $curlChannel;

    /** @var bool */
    private $httpsOnly = true;

    public function __construct(array $configData = [])
    {
        if (false === $this->curlChannel = curl_init()) {
            throw new RuntimeException('unable to create cURL channel');
        }
        if (array_key_exists('httpsOnly', $configData)) {
            $this->httpsOnly = (bool) $configData['httpsOnly'];
        }
    }

    public function __destruct()
    {
        curl_close($this->curlChannel);
    }

    public function get($requestUri, array $requestHeaders = [])
    {
        return $this->exec(
            [
                CURLOPT_URL => $requestUri,
            ],
            $requestHeaders
        );
    }

    private function exec(array $curlOptions, array $requestHeaders)
    {
        // reset all cURL options
        $this->curlReset();

        $headerList = [];

        $defaultCurlOptions = [
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => $this->httpsOnly ? CURLPROTO_HTTPS : CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_HEADERFUNCTION => function ($curlChannel, $headerData) use (&$headerList) {
                // XXX is this secure? mb_strlen?
                if (false !== strpos($headerData, ':')) {
                    list($key, $value) = explode(':', $headerData, 2);
                    $headerList[trim($key)] = trim($value);
                }

                return strlen($headerData);
            },
        ];

        if (0 !== count($requestHeaders)) {
            $curlRequestHeaders = [];
            foreach ($requestHeaders as $k => $v) {
                $curlRequestHeaders[] = sprintf('%s: %s', $k, $v);
            }
            $defaultCurlOptions[CURLOPT_HTTPHEADER] = $curlRequestHeaders;
        }

        if (false === curl_setopt_array($this->curlChannel, $curlOptions + $defaultCurlOptions)) {
            throw new RuntimeException('unable to set cURL options');
        }

        if (false === $responseData = curl_exec($this->curlChannel)) {
            $curlError = curl_error($this->curlChannel);
            throw new RuntimeException(sprintf('failure performing the HTTP request: "%s"', $curlError));
        }

        return new Response(
            curl_getinfo($this->curlChannel, CURLINFO_HTTP_CODE),
            $responseData,
            $headerList
        );
    }

    private function curlReset()
    {
        // requires PHP >= 5.5 for curl_reset
        if (function_exists('curl_reset')) {
            curl_reset($this->curlChannel);

            return;
        }

        // reset the request method to GET, that is enough to allow for
        // multiple requests using the same cURL channel
        if (false === curl_setopt_array(
            $this->curlChannel,
            [
                CURLOPT_HTTPGET => true,
                CURLOPT_HTTPHEADER => [],
            ]
        )) {
            throw new RuntimeException('unable to set cURL options');
        }
    }
}
