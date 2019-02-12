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

namespace fkooman\SAML\DS\Tests;

use fkooman\SAML\DS\XmlIdpInfoSource;
use PHPUnit\Framework\TestCase;

class XmlIdpInfoSourceTest extends TestCase
{
    public function testSURFconextOld()
    {
        $xmlIdpInfoSource = new XmlIdpInfoSource(__DIR__.'/data/SURFconext_old.xml');
        $idpInfo = $xmlIdpInfoSource->get('https://idp.surfnet.nl');
        $this->assertSame('https://idp.surfnet.nl', $idpInfo->getEntityId());
    }

    public function testSURFconextNew()
    {
        $xmlIdpInfoSource = new XmlIdpInfoSource(__DIR__.'/data/SURFconext_new.xml');
        $idpInfo = $xmlIdpInfoSource->get('https://idp.surfnet.nl');
        $this->assertSame('https://idp.surfnet.nl', $idpInfo->getEntityId());
    }
}
