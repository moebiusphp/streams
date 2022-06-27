<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Streams\{
    Source,
    ReadableStream,
    WritableStream
};

$source = new Source();
$source->on('data', function($e, $chunk) {
    echo "Got chunk '$chunk'\n";
});
$source->on('end', function($e) {
    echo "Source ended\n";
});

$writable = new WritableStream($source);
$writable->write("Hello World!");
$writable->close();
