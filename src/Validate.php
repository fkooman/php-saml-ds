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

namespace fkooman\SAML\DS;

use fkooman\SAML\DS\Http\Exception\HttpException;
use fkooman\SAML\DS\Http\Request;

class Validate
{
    /**
     * @return void
     */
    public static function request(Request $request)
    {
        if (!\in_array($request->getMethod(), ['GET', 'POST', 'HEAD'], true)) {
            $e = new HttpException('only "GET", "HEAD" and "POST" are supported', 405);
            $e->setHeaders(['Allow' => 'GET,HEAD,POST']);

            throw $e;
        }
    }

    /**
     * @param string        $spEntityID
     * @param array<string> $spEntityIDs
     *
     * @return string
     */
    public static function spEntityID($spEntityID, array $spEntityIDs)
    {
        if (!\in_array($spEntityID, $spEntityIDs, true)) {
            throw new HttpException(\sprintf('SP with entityID "%s" not registered in discovery service', $spEntityID), 400);
        }

        return $spEntityID;
    }

    /**
     * @param string $returnIDParam
     *
     * @return string
     */
    public static function returnIDParam($returnIDParam)
    {
        if (!\in_array($returnIDParam, ['IdP', 'idpentityid'], true)) {
            throw new HttpException('unsupported "returnIDParam"', 400);
        }

        return $returnIDParam;
    }

    /**
     * @param string $return
     *
     * @return string
     */
    public static function returnUrl($return)
    {
        $filterFlags = FILTER_FLAG_PATH_REQUIRED | FILTER_FLAG_QUERY_REQUIRED;
        if (false === \filter_var($return, FILTER_VALIDATE_URL, $filterFlags)) {
            throw new HttpException('invalid "return" URL', 400);
        }

        return $return;
    }

    /**
     * @param string $filter
     *
     * @return string
     */
    public static function filter($filter)
    {
        if (1 !== \preg_match('/^[a-zA-Z0-9]*$/', $filter)) {
            throw new HttpException('invalid "filter" string', 400);
        }

        return $filter;
    }

    /**
     * @param string        $idpEntityID
     * @param array<string> $idpEntityIDs
     *
     * @return string
     */
    public static function idpEntityID($idpEntityID, array $idpEntityIDs)
    {
        if (!\in_array($idpEntityID, $idpEntityIDs, true)) {
            throw new HttpException(\sprintf('IdP with entityID "%s" not available for this SP', $idpEntityID), 400);
        }

        return $idpEntityID;
    }
}
