<?php
namespace Moebius\Streams;

use Moebius\Coroutine as Co;
use Moebius\Deferred;
use Moebius\PromiseInterface;
use Moebius\Events\EventEmitterTrait;

class BufferedSource extends Source implements BufferedSourceInterface {

    protected $fp;
    protected int $readOffset;
    protected int $length;
    protected ?PromiseInterface $waitPromise = null;

    public function __construct(mixed $fp) {
        if ($fp !== null && (!is_resource($fp) || get_resource_type($fp) !== 'stream') ) {
            throw new \InvalidArgumentException("Expecting a stream resource for argument 1");
        }
        $this->fp = $fp;
        \stream_set_read_buffer($fp, 0);
        \stream_set_write_buffer($fp, 0);
        \stream_set_blocking($fp, false);
        $meta = \stream_get_meta_data($fp);
        $readable = \strpos($meta['mode'], 'r') !== false || \strpos($meta['mode'], '+') !== false;
        if (!$readable) {
            throw new \LogicException("Buffer is not readable");
        }
        $writable = \strpos($meta['mode'], '+') !== false || \strpos($meta['mode'], 'r') === false;
        if (!$writable) {
            throw new \LogicException("Buffer is not writable");
        }
        $this->readOffset = 0;
        $stat = \fstat($fp);
        $this->length = $stat['size'];
    }

    public function getSize(): int {
        return $this->length;
    }

    public function write(string $chunk): void {
        Co::writable($this->fp);
        \fseek($this->fp, $this->length);
        $written = \fwrite($this->fp, $chunk);
        if ($written === false) {
            throw new \RuntimeException("Write error");
        }
        $this->length += \strlen($chunk);
        parent::write($chunk);
        if ($this->waitPromise) {
            $waitPromise = $this->waitPromise;
            $this->waitPromise = null;
            $waitPromise->fulfill(true);
        }
    }

    public function end(): void {
        parent::end();
        if ($this->waitPromise) {
            $waitPromise = $this->waitPromise;
            $this->waitPromise = null;
            $waitPromise->fulfill(true);
        }
    }

    /**
     * Block until the buffer changes
     */
    protected function wait(): void {
        if (!$this->waitPromise) {
            $this->waitPromise = new Deferred();
        }
        Co::await($this->waitPromise);
    }

    public function seek(int $offset): void {
        if ($offset < 0) {
            $offset = 0;
        }
        $this->readOffset = $offset;
    }

    public function tell(): int {
        return $this->readOffset;
    }

    /**
     * Read a chunk of data from the buffer. If the chunk is unavailable, block until the
     * chunk becomes available.
     */
    public function read(int $length): string {
        if ($length <= 0) {
            return '';
        }
        if ($length > 65536) {
            $length = 65536;
        }

        while ($this->readOffset >= $this->length && !$this->ended) {
            $this->wait();
        }

        Co::readable($this->fp);
        \fseek($this->fp, $this->readOffset);
        $chunk = \fread($this->fp, $length);
        if (!is_string($chunk)) {
            throw new \RuntimeException("Read error");
        }
        $this->readOffset += \strlen($chunk);
        return $chunk;
    }

}
