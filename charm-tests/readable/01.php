<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Streams\{
    Source,
    ReadableStream
};

$source = new Source();
$readableStream = new ReadableStream($source);

$source->write("Hello");
$source->write(" World!");
$source->end();
while (!$readableStream->eof()) {
    echo "ReadableStream: '".$readableStream->read(4096)."'\n";
}
