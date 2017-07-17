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

    /** @var string|null */
    private $entityID = null;

    /**
     * @param string                            $dataDir
     * @param Config                            $config
     * @param TplInterface                      $tplInterface
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
     * @param string $entityID
     */
    public function setEntityID($entityID)
    {
        $this->entityID = $entityID;
    }

    /**
     * @return Http\Response
     */
    public function run(Request $request)
    {
        try {
            Validate::request($request);
            $requestMethod = strtolower($request->getMethod());

            return $this->$requestMethod($request);
        } catch (HttpException $e) {
            return new Response(
                $e->getCode(),
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
        if (0 === count($idpList)) {
            throw new HttpException(sprintf('the SP "%s" has no IdPs configured', $spEntityID), 500);
        }
        if (1 === count($idpList)) {
            // we only have exactly 1 IdP, so redirect immediately back to the SP
            $idpEntityID = array_keys($idpList)[0];

            return $this->returnTo($return, $returnIDParam, $idpEntityID);
        }

        $displayName = $this->config->get('spList')->get($spEntityID)->get('displayName');

        // do we have an already previous chosen IdP?
        $lastChosen = false;
        if (!is_null($this->entityID)) {
            if (in_array($this->entityID, $this->config->get('spList')->get($spEntityID)->get('idpList'))) {
                $lastChosen = $idpList[$this->entityID];
                // remove the last chosen IdP from the list of IdPs
                unset($idpList[$this->entityID]);
            }
        }

        if ($filter) {
            // remove entries not matching the value in filter
            $idpListCount = count($idpList);
            foreach ($idpList as $k => $v) {
                $inKeywords = false !== stripos(implode(' ', $v['keywords']), $filter);
                if (!$inKeywords) {
                    unset($idpList[$k]);
                }
            }
        }

        $discoveryPage = $this->tpl->render(
            'discovery',
            [
                'useLogos' => $this->config->get('useLogos'),
                'filter' => $filter,
                'entityID' => $spEntityID,
                'encodedEntityID' => self::encodeEntityID($spEntityID),
                'returnIDParam' => $returnIDParam,
                'return' => $return,
                'displayName' => $displayName,
                'lastChosen' => $lastChosen,
                'idpList' => array_values($idpList),
            ]
        );

        return new Response(200, [], $discoveryPage);
    }

    /**
     * @return Http\Response
     */
    private function post(Request $request)
    {
        $spEntityID = Validate::spEntityID($request->getQueryParameter('entityID'), $this->config->get('spList')->keys());
        $returnIDParam = Validate::returnIDParam($request->getQueryParameter('returnIDParam'));
        $return = Validate::returnUrl($request->getQueryParameter('return'));
        $idpEntityID = Validate::idpEntityID($request->getPostParameter('idpEntityID'), $this->config->get('spList')->get($spEntityID)->get('idpList'));

        $this->cookie->set('entityID', $idpEntityID);

        return $this->returnTo($return, $returnIDParam, $idpEntityID);
    }

    /**
     * @param string $entityID
     *
     * @return string
     */
    private static function encodeEntityID($entityID)
    {
        return preg_replace('/__*/', '_', preg_replace('/[^A-Za-z.]/', '_', $entityID));
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
        $idpListFile = sprintf('%s/%s.json', $this->dataDir, $encodedEntityID);
        if (false === $jsonData = @file_get_contents($idpListFile)) {
            throw new RuntimeException(sprintf('unable to read "%s"', $idpListFile));
        }

        $idpList = json_decode($jsonData, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf('unable to decode "%s"', $idpListFile));
        }

        uasort($idpList, function ($a, $b) {
            if (!array_key_exists('displayName', $a) || !array_key_exists('displayName', $b)) {
                throw new RuntimeException('missing "displayName" in IdP data');
            }

            return strcasecmp($a['displayName'], $b['displayName']);
        });

        return $idpList;
    }

    /**
     * @return Http\Response
     */
    private function returnTo($return, $returnIDParam, $idpEntityID)
    {
        $returnTo = sprintf(
            '%s&%s',
            $return,
            http_build_query(
                [
                    $returnIDParam => $idpEntityID,
                ]
            )
        );

        return new Response(302, ['Location' => $returnTo]);
    }
}
