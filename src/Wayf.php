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
use fkooman\SAML\DS\Http\Response;
use fkooman\SeCookie\CookieInterface;
use RuntimeException;

class Wayf
{
    /** @var Config */
    private $config;

    /** @var TplInterface */
    private $tpl;

    /** @var \fkooman\SeCookie\CookieInterface */
    private $cookie;

    /** @var string */
    private $dataDir;

    /** @var array<string> */
    private $favoriteIdPs = [];

    /**
     * @param string                            $dataDir
     * @param Config                            $config
     * @param TplInterface                      $tpl
     * @param \fkooman\SeCookie\CookieInterface $cookie
     */
    public function __construct($dataDir, Config $config, TplInterface $tpl, CookieInterface $cookie)
    {
        $this->config = $config;
        $this->tpl = $tpl;
        $this->cookie = $cookie;
        $this->dataDir = $dataDir;
    }

    /**
     * @param array $favoriteIdPs
     *
     * @return void
     */
    public function setFavoriteIdPs(array $favoriteIdPs)
    {
        // this information comes directly from cookie, so user can manipulate
        // this!
        foreach ($favoriteIdPs as $favoriteIdP) {
            if (!\is_string($favoriteIdP)) {
                // ignore non-string values, we should probably be more
                // strict here and just fail instead
                continue;
            }

            $this->favoriteIdPs[] = $favoriteIdP;
        }
    }

    /**
     * @param Http\Request $request
     *
     * @return Http\Response
     */
    public function run(Request $request)
    {
        try {
            Validate::request($request);
            $requestMethod = \strtolower($request->getMethod());

            return $this->$requestMethod($request);
        } catch (HttpException $e) {
            return new Response(
                (int) $e->getCode(),
                $e->getHeaders(),
                $this->tpl->render(
                    'error',
                    [
                        'errorCode' => $e->getCode(),
                        'errorMessage' => $e->getMessage(),
                    ]
                )
            );
        }
    }

    /**
     * @param Http\Request $request
     *
     * @return Http\Response
     */
    private function get(Request $request)
    {
        $spEntityID = Validate::spEntityID($request->getQueryParameter('entityID'), $this->config->get('spList')->keys());
        $returnIDParam = Validate::returnIDParam($request->getQueryParameter('returnIDParam'));
        $return = Validate::returnUrl($request->getQueryParameter('return'));
        $filter = false;
        if ($request->hasQueryParameter('filter')) {
            $filter = Validate::filter($request->getQueryParameter('filter'));
        }

        $idpList = $this->getIdPList($spEntityID);
        if (0 === \count($idpList)) {
            throw new HttpException(\sprintf('the SP "%s" has no IdPs configured', $spEntityID), 500);
        }
        if (1 === \count($idpList)) {
            // we only have exactly 1 IdP, so redirect immediately back to the SP
            $idpEntityID = \array_keys($idpList)[0];

            return $this->returnTo($return, $returnIDParam, $idpEntityID);
        }

        $displayName = $this->config->get('spList')->get($spEntityID)->get('displayName');

        // do we have an already previous chosen IdP?
        $lastChosenList = [];
        foreach ($this->favoriteIdPs as $favoriteIdP) {
            if (\in_array($favoriteIdP, $this->config->get('spList')->get($spEntityID)->get('idpList'), true)) {
                $lastChosenList[] = $idpList[$favoriteIdP];
                // remove the last chosen IdP from the list of IdPs
                unset($idpList[$favoriteIdP]);
            }
        }

        // put lastChosen in the front
        $idpList = \array_merge($lastChosenList, $idpList);

        if ($filter) {
            // remove entries not matching the value in filter
            foreach ($idpList as $k => $v) {
                $inKeywords = false !== \stripos(\implode(' ', $v['keywords']), $filter);
                if (!$inKeywords) {
                    unset($idpList[$k]);
                }
            }
        }

        $discoveryPage = $this->tpl->render(
            'discovery',
            [
                'filter' => $filter,
                'entityID' => $spEntityID,
                'returnIDParam' => $returnIDParam,
                'return' => $return,
                'displayName' => $displayName,
                'idpList' => \array_values($idpList),
            ]
        );

        return new Response(200, [], $discoveryPage);
    }

    /**
     * @param Http\Request $request
     *
     * @return Http\Response
     */
    private function post(Request $request)
    {
        $spEntityID = Validate::spEntityID($request->getQueryParameter('entityID'), $this->config->get('spList')->keys());
        $returnIDParam = Validate::returnIDParam($request->getQueryParameter('returnIDParam'));
        $return = Validate::returnUrl($request->getQueryParameter('return'));
        $idpEntityID = Validate::idpEntityID($request->getPostParameter('idpEntityID'), $this->config->get('spList')->get($spEntityID)->get('idpList'));

        // add the chosen IdP to the cookie if it is not yet there, or move
        // it to the first position if it was there already
        $favoriteList = [$idpEntityID];
        foreach ($this->favoriteIdPs as $favoriteIdP) {
            if (!\in_array($favoriteIdP, $favoriteList, true)) {
                $favoriteList[] = $favoriteIdP;
            }
        }
        // make sure we only store a maximum 3 favorite IdPs, that seems to be
        // enough for most use cases
        $this->cookie->set(
            'favoriteIdPs',
            \json_encode(
                \array_slice($favoriteList, 0, 3)
            )
        );

        return $this->returnTo($return, $returnIDParam, $idpEntityID);
    }

    /**
     * @param string $entityID
     *
     * @return string
     */
    private static function encodeEntityID($entityID)
    {
        return \preg_replace('/__*/', '_', \preg_replace('/[^A-Za-z.]/', '_', $entityID));
    }

    /**
     * @param string $spEntityID
     *
     * @return array
     */
    private function getIdPList($spEntityID)
    {
        // load the IdP List of this SP
        $encodedEntityID = self::encodeEntityID($spEntityID);
        $idpListFile = \sprintf('%s/%s.json', $this->dataDir, $encodedEntityID);
        if (false === $jsonData = @\file_get_contents($idpListFile)) {
            throw new RuntimeException(\sprintf('unable to read "%s"', $idpListFile));
        }

        $idpList = \json_decode($jsonData, true);
        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw new RuntimeException(\sprintf('unable to decode "%s"', $idpListFile));
        }

        \uasort($idpList,
        /**
         * @param array $a
         * @param array $b
         *
         * @return int
         */
        function ($a, $b) {
            if (!\array_key_exists('displayName', $a) || !\array_key_exists('displayName', $b)) {
                throw new RuntimeException('missing "displayName" in IdP data');
            }

            return \strcasecmp($a['displayName'], $b['displayName']);
        });

        return $idpList;
    }

    /**
     * @param string $return
     * @param string $returnIDParam
     * @param string $idpEntityID
     *
     * @return Http\Response
     */
    private function returnTo($return, $returnIDParam, $idpEntityID)
    {
        $returnTo = \sprintf(
            '%s&%s',
            $return,
            \http_build_query(
                [
                    $returnIDParam => $idpEntityID,
                ]
            )
        );

        return new Response(302, ['Location' => $returnTo]);
    }
}
