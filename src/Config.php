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

use fkooman\SAML\DS\Exception\ConfigException;

class Config
{
    /** @var array */
    private $data;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string $fileName
     *
     * @return self
     */
    public static function fromFile($fileName)
    {
        if (false === $configData = @include($fileName)) {
            throw new ConfigException(sprintf('unable to read "%s"', $fileName));
        }

        return new self($configData);
    }

    /**
     * @param string $key
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->data)) {
            // consumers MUST check first if a field is available before
            // requesting it
            throw new ConfigException(sprintf('missing field "%s" in configuration', $key));
        }

        if (is_array($this->data[$key])) {
            if (0 === count($this->data[$key])) {
                return [];
            }
            // if all we get is a "flat" array with sequential numeric keys
            // return the array instead of an object
            $k = array_keys($this->data[$key]);
            if ($k === range(0, count($k) - 1)) {
                return $this->data[$key];
            }

            return new self($this->data[$key]);
        }

        return $this->data[$key];
    }
}
