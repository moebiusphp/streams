<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Streams\{
    Source,
    ReadableStream
};
function test() {
    $source = new Source();
    $source->on('data', function($e, $chunk) {
        echo "Got chunk '$chunk'\n";
    });
    $source->on('end', function($e) {
        echo "Got EOF\n";
    });
    $source->write("Hello world!");
    $source->end();
}
test();
