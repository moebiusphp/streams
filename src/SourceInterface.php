<?php
namespace Moebius\Streams;

use Moebius\Events\EventEmitterInterface;

interface SourceInterface extends EventEmitterInterface {

    /**
     * Write data to the source
     */
    public function write(string $chunk): void;

    /**
     * Notify source that no more data will arrive
     */
    public function end(): void;

    /**
     * Notify source to not accept more data
     */
    public function pause(): void;

    /**
     * Notify source that new data can be accepted
     */
    public function resume(): void;

    /**
     * Has the source reached end of file?
     */
    public function eof(): bool;

    /**
     * Get the last chunk written to the source, to allow
     * multiple subscribers start at the same offset.
     */
    public function getLastChunk(): ?string;
}
