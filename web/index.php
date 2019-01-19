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

require_once \dirname(__DIR__).'/vendor/autoload.php';
$baseDir = \dirname(__DIR__);

use fkooman\SAML\DS\Config;
use fkooman\SAML\DS\Http\Request;
use fkooman\SAML\DS\Http\Response;
use fkooman\SAML\DS\TemplateEngine;
use fkooman\SAML\DS\Wayf;
use fkooman\SeCookie\Cookie;

try {
    $config = Config::fromFile(\sprintf('%s/config/config.php', $baseDir));

    $templateDirs = [
        \sprintf('%s/views', $baseDir),
        \sprintf('%s/config/views', $baseDir),
    ];
    if ($config->has('styleName')) {
        $templateDirs[] = \sprintf('%s/views/%s', $baseDir, $config->get('styleName'));
    }

    $secureCookie = $config->has('secureCookie') ? $config->get('secureCookie') : true;

    $templateEngine = new TemplateEngine($templateDirs);
    $request = new Request($_SERVER, $_GET, $_POST);
    $cookie = new Cookie(
        [
            'SameSite' => 'Lax',
            'Secure' => $secureCookie,
            'Max-Age' => 60 * 60 * 24 * 90,   // 90 days
        ]
    );

    $wayf = new Wayf(
        \sprintf('%s/data', $baseDir),
        $config,
        $templateEngine,
        $cookie
    );

    // provide the favorite IdP list
    if (\array_key_exists('favoriteIdPs', $_COOKIE)) {
        $favoriteIdPs = \json_decode($_COOKIE['favoriteIdPs'], true);
        // json_decode returns null on error
        if (\is_array($favoriteIdPs)) {
            $wayf->setFavoriteIdPs($favoriteIdPs);
        }
    } else {
        // legacy, migrate old 'entityID' cookie to new 'favoriteIdPs' and
        // delete the old cookie
        if (\array_key_exists('entityID', $_COOKIE)) {
            $entityID = $_COOKIE['entityID'];
            if (\is_string($entityID)) {
                $cookie->set('favoriteIdPs', \json_encode([$entityID]));
                $wayf->setFavoriteIdPs([$entityID]);
            }
            $cookie->delete('entityID');
        }
    }

    $wayf->run($request)->send();
} catch (Exception $e) {
    $errorMessage = \sprintf('[500] (%s): %s', \get_class($e), $e->getMessage());
    $response = new Response(
        500,
        ['Content-Type' => 'text/plain'],
        \htmlentities($errorMessage, ENT_QUOTES, 'UTF-8')
    );
    $response->send();
    \error_log(\sprintf('%s {%s}', $errorMessage, $e->getTraceAsString()));
}
