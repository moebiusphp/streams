Moebius/Streams
===============

Provides a powerful API for generating PSR-7 compliant streams.

Allows connecting a writable stream to a readable stream. When writing to the writable
stream, the readable stream can be read and vice versa.


Moebius\Streams\SourceInterface
-------------------------------

Represents a stream of bytes coming from any source.

`SourceInterface::write(string $chunk)` adds a chunk of bytes to the byte stream. The
function will block if any of the readers have requested that the byte stream pause.

`SourceInterface::end()` signals that the byte stream is complete, and is interpreted
as end-of-file when consumed by a ReadableStream.


Moebius\Streams\ReadableStream
------------------------------

Class implements the `Psr\Http\Message\StreamInterface` and provides read access to
the data stream.


Moebius\Stream\WritableStream
-----------------------------

Class implements the `Psr\Http\Message\StreamInterface` and provides write access to
the data stream.
