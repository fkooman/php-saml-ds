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

require_once \dirname(__DIR__).'/vendor/autoload.php';
$baseDir = \dirname(__DIR__);

use fkooman\SAML\DS\Config;
use fkooman\SAML\DS\Http\Request;
use fkooman\SAML\DS\Http\Response;
use fkooman\SAML\DS\Json;
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
        $wayf->setFavoriteIdPs(Json::decode($_COOKIE['favoriteIdPs']));
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
