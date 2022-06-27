<?php
namespace Moebius\Streams;

interface BufferedSourceInterface extends SourceInterface {

    /**
     * Set the read offset
     */
    public function seek(int $offset): void;

    /**
     * Read a chunk of data from the buffer.
     */
    public function read(int $length): string;

    /**
     * Get the total length of the buffer.
     */
    public function getSize(): int;

}
