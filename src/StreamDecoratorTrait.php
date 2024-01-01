<?php

/**
 * @package     PSR-7 (Localzet Version)
 * @link        https://github.com/localzet/PSR-7
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     https://www.gnu.org/licenses/agpl AGPL-3.0 license
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace localzet\PSR7;

use BadMethodCallException;
use Exception;
use localzet\PSR\Http\Message\StreamInterface;
use UnexpectedValueException;

/**
 * Stream decorator trait
 * @property StreamInterface stream
 */
trait StreamDecoratorTrait
{
    /**
     * @param StreamInterface $stream Stream to decorate
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Magic method used to create a new stream if streams are not added in
     * the constructor of a decorator (e.g., LazyOpenStream).
     *
     * @param string $name Name of the property (allows "stream" only).
     *
     * @return StreamInterface
     */
    public function __get(string $name)
    {
        if ($name == 'stream') {
            $this->stream = $this->createStream();
            return $this->stream;
        }

        throw new UnexpectedValueException("$name not found on class");
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (Exception $e) {
            // Really, PHP? https://bugs.php.net/bug.php?id=53648
            trigger_error('StreamDecorator::__toString exception: '
                . $e, E_USER_ERROR);
            return '';
        }
    }

    public function getContents(): string
    {
        return copy_to_string($this);
    }

    /**
     * Allow decorators to implement custom methods
     *
     * @param string $method Missing method name
     * @param array $args Method arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        $result = call_user_func_array([$this->stream, $method], $args);

        // Always return the wrapped object if the result is a return $this
        return $result === $this->stream ? $this : $result;
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    public function read($length): string
    {
        return $this->stream->read($length);
    }

    public function write($string): int
    {
        return $this->stream->write($string);
    }

    /**
     * Implement in subclasses to dynamically create streams when requested.
     *
     * @return StreamInterface
     * @throws BadMethodCallException
     */
    protected function createStream(): StreamInterface
    {
        throw new BadMethodCallException('Not implemented');
    }
}
