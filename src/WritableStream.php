<?php
namespace Moebius\Streams;

class WritableStream extends AbstractStream {

    protected ?SourceInterface $sink = null;
    protected bool $ended = false;

    public function __construct(SourceInterface $sink) {
        $this->sink = $sink;
    }

    public function __toString() {
        return '';
    }

    public function close() {
        if (!$this->ended) {
            $this->ended = true;
            $this->sink->end();
            $this->sink = null;
        }
    }

    public function eof() {
        return false;
    }

    public function isWritable() {
        return !!$this->sink;
    }

    public function write($chunk) {
        $this->assertUsable();
        $this->sink->write($chunk);
        return \strlen($chunk);
    }

    protected function assertUsable(): void {
        if (!$this->sink) {
            throw new \RuntimeException("Stream is not usable");
        }
    }
}
