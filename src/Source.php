<?php
namespace Moebius\Streams;

use Moebius\Coroutine as Co;
use Moebius\Deferred;
use Moebius\Events\EventEmitterTrait;

class Source implements SourceInterface {
    use EventEmitterTrait;

    protected bool $ended = false;
    protected int $pauses = 0;
    protected ?Deferred $pauseDone = null;
    protected ?string $chunk = null;

/*
    public function __construct(mixed $fp = null) {
        if ($fp !== null && (!is_resource($fp) || get_resource_type($fp) !== 'stream') ) {
            throw new \InvalidArgumentException("Expecting a stream resource for argument 1");
        }
        $this->fp = $fp;
    }
*/

    public function __destruct() {
        if (!$this->ended) {
            throw new \RuntimeException(static::class."::end() was not called");
        }
    }

    public function getLastChunk(): ?string {
        return $this->chunk;
    }

    public function eof(): bool {
        return $this->ended;
    }

    public function write(string $chunk): void {
        if ($this->pauses > 0) {
            Co::await($this->pauseDone);
        }
        if ($this->ended) {
            throw new \LogicException("Stream has ended");
        }
        $this->chunk = $chunk;
        $this->emit('data', $chunk);
    }

    public function end(): void {
        if ($this->pauses > 0) {
            Co::await($this->pauseDone);
        }
        if ($this->ended) {
            throw new \LogicException("Stream already ended");
        }
        $this->ended = true;
        $this->emit('end');
    }

    public function pause(): void {
        if ($this->pauses++ === 0) {
            $this->pauseDone = new Deferred();
        }
    }

    public function resume(): void {
        if ($this->pauses === 0) {
            throw new \LogicException("Not paused");
        }
        if (--$this->pauses === 0) {
            $this->pauseDone->fulfill($this);
            $this->pauseDone = null;
        }
    }

}
