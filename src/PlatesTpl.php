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

namespace fkooman\SAML\DS;

use League\Plates\Engine;

class PlatesTpl implements TplInterface
{
    /** @var \League\Plates\Engine */
    private $engine;

    /**
     * @param string $templateDir
     */
    public function __construct($templateDir)
    {
        $this->engine = new Engine($templateDir);
    }

    /**
     * @param string $templateName      the name of the template
     * @param array  $templateVariables the variables to be used in the
     *                                  template
     *
     * @return string
     */
    public function render($templateName, array $templateVariables)
    {
        return $this->engine->render(
            \sprintf(
                '%s',
                $templateName
            ),
            $templateVariables
        );
    }
}
