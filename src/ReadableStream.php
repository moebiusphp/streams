<?php
namespace Moebius\Streams;

use Closure;
use Moebius\Deferred;
use Moebius\Coroutine as Co;
use Moebius\Events\EventInterface;

class ReadableStream extends AbstractStream {

    protected ?SourceInterface $source;
    protected bool $sourcePaused = false;
    protected Closure $onData;
    protected Closure $onEnd;

    // The source will notify us when EOF is reached
    protected bool $eofSignalled = false;

    // When EOF is signalled, EOF is false until we've tried to read beyond the
    // end of the buffer
    protected bool $eofActivated = false;

    protected ?Deferred $readBlock = null;
    protected string $buffer = '';
    protected int $bufferLength = 0xFFF;

    public function __construct(SourceInterface $source) {
        $this->source = $source;

        $this->onData = $this->onData(...);
        $source->on('data', $this->onData);

        $this->onEnd = $this->onEnd(...);
        $source->on('end', $this->onEnd);

        $this->buffer = $source->getLastChunk() ?? '';
        $this->eofSignalled = $source->eof();

        $this->checkBuffer();
    }

    public function __destruct() {
        $this->close();
    }

    /**
     * When our buffer is full, we can call this method to request that the Source object
     * pauses feeding us with new data. This will pause all other subscribers to the data
     * feed as well.
     */
    protected function pauseSource(): void {
        if ($this->eofSignalled) {
            throw new \LogicException("No point in pausing source when we have received EOF");
        }
        if ($this->readBlock) {
            throw new \LogicException("No point in pausing source when we're blocking reads");
        }
        if (!$this->sourcePaused) {
            $this->sourcePaused = true;
            $this->source->pause();
        }
    }

    /**
     * When our buffer is available again, we can call this method to re-enable the Source
     * object.
     */
    protected function resumeSource(): void {
        if ($this->sourcePaused && $this->source) {
            $this->sourcePaused = false;
            $this->source->resume();
        }
    }

    /**
     * Check the buffer level to determine if we need to pause or resume the source and
     * if reading from the buffer needs to block or not.
     */
    protected function checkBuffer(): void {
        if ($this->eofSignalled) {
            // We have received EOF, so source should no longer be blocked by us
            $this->resumeSource();
            if ($this->readBlock) {
                // We have all the data we can ever return, so no point in blocking reads.
                $readBlock = $this->readBlock;
                $this->readBlock = null;
                $readBlock->fulfill(true);
            }
            return;
        }

        $length = \strlen($this->buffer);
        if ($length > $this->bufferLength) {
            // Our buffer is too full, so we have to pause the source
            $this->pauseSource();
        } else {
            // We have space in our buffer, so ensure the source is not paused
            $this->resumeSource();
        }

        if ($length === 0) {
            // Our buffer is empty, so reads must block
            if (!$this->readBlock) {
                $this->readBlock = new Deferred();
            }
        } else {
            // Our buffer is not empty, so reads should not block
            if ($this->readBlock) {
                $readBlock = $this->readBlock;
                $this->readBlock = null;
                $readBlock->fulfill(true);
            }
        }
    }

    protected function awaitSource(): void {
        assert(!$this->sourcePaused, "Source is paused while we're awaiting new data");
        if ($this->readBlock) {
            Co::await($this->readBlock);
        }
    }

    protected function onData(EventInterface $event, string $chunk) {
        $this->buffer .= $chunk;
        $this->checkBuffer();
    }

    protected function onEnd(EventInterface $event) {
        $this->eofSignalled = true;
        $this->checkBuffer();
    }

    public function read($length) {
        // Once EOF has been activated, always return empty string
        if ($this->eofActivated) {
            return '';
        }

        /**
         * If we have availbale data in the buffer, return that
         * data.
         */
        if ($this->buffer !== '') {
            $chunk = substr($this->buffer, 0, $length);
            $this->buffer = substr($this->buffer, strlen($chunk));
            $this->checkBuffer();
            return $chunk;
        }

        /**
         * If we don't have available data in the buffer, if
         * end-of-file - return an empty string.
         */
        if ($this->eofSignalled) {
            $this->eofActivated = true;
            return '';
        }

        /**
         * We're expecting data to arrive or EOF to be signalled.
         */
        $this->awaitSource();

        return $this->read($length);
    }

    public function close() {
        if ($this->source) {
            // Since we're closing, we must ensure we're not causing the source
            // to pause.
            $this->resumeSource();

            // Remove event listeners to avoid memory leaks
            $this->source->off('data', $this->onData);
            $this->source->off('end', $this->onEnd);
            $this->source = null;
        }
    }

    public function detach() {
        $this->close();
        return null;
    }

    public function getSize() {
        return null;
    }

    public function tell() {
        $this->assertUsable();
        return null;
    }

    public function eof() {
        // We're at EOF when a read caused us to try to read beyond the buffer
        return $this->eofActivated;
    }

    public function isReadable() {
        return !!$this->source;
    }

    public function getContents() {
        $this->assertUsable();
        $buffer = '';
        while (!$this->eof()) {
            $buffer .= $this->read(65536);
        }
        return $buffer;
    }

    public function __toString() {
        try {
            return $this->getContents();
        } catch (\Throwable $e) {
            return get_class($e).': '.$e->getMessage();
        }
    }

    protected function assertUsable(): void {
        if (!$this->source) {
            throw new \RuntimeException("Stream is unusable");
        }
    }

}
