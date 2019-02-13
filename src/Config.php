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
     * @psalm-suppress UnresolvableInclude
     *
     * @param string $fileName
     *
     * @return self
     */
    public static function fromFile($fileName)
    {
        if (false === $configData = @include($fileName)) {
            throw new ConfigException(\sprintf('unable to read "%s"', $fileName));
        }

        return new self($configData);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * @return array
     */
    public function keys()
    {
        return \array_keys($this->data);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!\array_key_exists($key, $this->data)) {
            // consumers MUST check first if a field is available before
            // requesting it
            throw new ConfigException(\sprintf('missing field "%s" in configuration', $key));
        }

        if (\is_array($this->data[$key])) {
            if (0 === \count($this->data[$key])) {
                return [];
            }
            // if all we get is a "flat" array with sequential numeric keys
            // return the array instead of an object
            $k = \array_keys($this->data[$key]);
            if ($k === \range(0, \count($k) - 1)) {
                return $this->data[$key];
            }

            return new self($this->data[$key]);
        }

        return $this->data[$key];
    }
}
