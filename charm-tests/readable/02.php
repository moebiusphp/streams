<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Streams\{
    Source,
    ReadableStream
};
use Moebius\Coroutine as Co;

function test() {
    $source = new Source();
    Co::go(function() use ($source) {
        for ($i = 0; $i < 10; $i++) {
            $source->write("Line $i");
            Co::sleep(0.025);
        }
        $source->end();
    });

    $readableStream = new ReadableStream($source);
    while (!$readableStream->eof()) {
        echo "ReadableStream: '".$readableStream->read(4096)."'\n";
    }
    echo "No more readable stream\n";
}

test();
