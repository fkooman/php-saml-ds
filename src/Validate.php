<?php
/**
 * Copyright 2017 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\SAML\DS;

use fkooman\SAML\DS\Http\Exception\HttpException;
use fkooman\SAML\DS\Http\Request;

class Validate
{
    public static function request(Request $request)
    {
        if (!in_array($request->getMethod(), ['GET', 'POST', 'HEAD'])) {
            $e = new HttpException('only "GET", "HEAD" and "POST" are supported', 405);
            $e->setHeaders(['Allow' => 'GET,HEAD,POST']);

            throw $e;
        }
    }

    public static function spEntityID($spEntityID, array $spEntityIDs)
    {
        if (!in_array($spEntityID, $spEntityIDs)) {
            throw new HttpException(
                sprintf('SP with entityID "%s" not registered in discovery service', $spEntityID),
                400
            );
        }

        return $spEntityID;
    }

    public static function returnIDParam($returnIDParam)
    {
        if (!in_array($returnIDParam, ['IdP', 'idpentityid'])) {
            throw new HttpException('unsupported "returnIDParam"', 400);
        }

        return $returnIDParam;
    }

    public static function returnUrl($return)
    {
        $filterFlags = FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED | FILTER_FLAG_QUERY_REQUIRED;
        if (false === filter_var($return, FILTER_VALIDATE_URL, $filterFlags)) {
            throw new HttpException('invalid "return" URL', 400);
        }

        return $return;
    }

    public static function filter($filter)
    {
        if (1 !== preg_match('/^[a-zA-Z0-9]*$/', $filter)) {
            throw new HttpException('invalid "filter" string', 400);
        }

        return $filter;
    }

    public static function idpEntityID($idpEntityID, array $idpEntityIDs)
    {
        if (!in_array($idpEntityID, $idpEntityIDs)) {
            throw new HttpException(
                sprintf('IdP with entityID "%s" not available for this SP', $idpEntityID),
                400
            );
        }

        return $idpEntityID;
    }
}
