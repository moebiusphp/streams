<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Streams\{
    Source,
    ReadableStream,
    WritableStream
};
use Moebius\Coroutine as Co;

function test() {
    $source = new Source();
    $source->on('data', function($e, $chunk) {
        echo "Got chunk '$chunk'\n";
    });
    $source->on('end', function($e) {
        echo "Source ended\n";
    });

    $writable = new WritableStream($source);
    $readable = new ReadableStream($source);
    Co::go(function() use ($readable) {
        while (!$readable->eof()) {
            echo "ReadableStream->read: ".json_encode($readable->read(16))."\n";
        }
        echo "ReadableStream got eof\n";
    });
    $writable->write("Hello World! ".str_repeat("1234", 128));
    $writable->close();
}

test();
