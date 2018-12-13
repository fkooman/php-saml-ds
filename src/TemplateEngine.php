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

use fkooman\SAML\DS\Exception\TemplateException;

class TemplateEngine implements TplInterface
{
    /** @var array */
    private $templateDirList;

    /** @var null|string */
    private $activeSectionName = null;

    /** @var array */
    private $sectionList = [];

    /** @var array */
    private $layoutList = [];

    /**
     * @param array $templateDirList
     */
    public function __construct(array $templateDirList)
    {
        $this->templateDirList = $templateDirList;
    }

    /**
     * @param string $templateName
     * @param array  $templateVariables
     *
     * @return string
     */
    public function render($templateName, array $templateVariables = [])
    {
        \extract($templateVariables);
        \ob_start();
        /** @psalm-suppress UnresolvableInclude */
        include $this->templatePath($templateName);
        $templateStr = \ob_get_clean();

        if (0 !== \count($this->layoutList)) {
            $templateName = \array_keys($this->layoutList)[0];
            $templateVariables = $this->layoutList[$templateName];

            // because we use render we must empty the layoutlist
            $this->layoutList = [];

            return $this->render($templateName, $templateVariables);
        }

        return $templateStr;
    }

    /**
     * @param string $sectionName
     *
     * @return void
     */
    public function start($sectionName)
    {
        if (null !== $this->activeSectionName) {
            throw new TemplateException(\sprintf('section "%s" already started', $this->activeSectionName));
        }

        $this->activeSectionName = $sectionName;
        \ob_start();
    }

    /**
     * @return void
     */
    public function stop()
    {
        if (null === $this->activeSectionName) {
            throw new TemplateException('no section started');
        }

        $this->sectionList[$this->activeSectionName] = \ob_get_clean();
        $this->activeSectionName = null;
    }

    /**
     * @param string $layoutName
     * @param array  $templateVariables
     *
     * @return void
     */
    public function layout($layoutName, array $templateVariables = [])
    {
        $this->layoutList[$layoutName] = $templateVariables;
    }

    /**
     * @param string $sectionName
     *
     * @return string
     */
    public function section($sectionName)
    {
        if (!\array_key_exists($sectionName, $this->sectionList)) {
            throw new TemplateException(\sprintf('section "%s" does not exist', $sectionName));
        }

        return $this->sectionList[$sectionName];
    }

    /**
     * @param string $v
     *
     * @return string
     */
    private function e($v)
    {
        return \htmlentities($v, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $templateName
     *
     * @return string
     */
    private function templatePath($templateName)
    {
        foreach ($this->templateDirList as $templateDir) {
            if (\file_exists($templateDir.'/'.$templateName)) {
                return $templateDir.'/'.$templateName;
            }
            if (\file_exists($templateDir.'/'.$templateName.'.php')) {
                return $templateDir.'/'.$templateName.'.php';
            }
        }

        throw new TemplateException(\sprintf('template "%s" does not exist', $templateName));
    }
}
