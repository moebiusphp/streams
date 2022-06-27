<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Coroutine as Co;
use Moebius\Streams\{
    BufferedSource,
    ReadableStream
};
function test() {
    $fp = fopen('php://temp', 'a+b');
    $source = new BufferedSource($fp);
    $source->on('data', function($e, $chunk) use ($source) {
        echo "Got chunk '$chunk'\n";
        echo "Size: ".$source->getSize()." Offset: ".$source->tell()."\n";
        echo "Read: ".$source->read(5)."\n";
        echo "Read: ".$source->read(5)."\n";
        echo "Read: ".$source->read(5)."\n";
        echo "Read: ".$source->read(5)."\n";
        echo "Read: ".$source->read(5)."\n";
    });
    $source->on('end', function($e) {
        echo "Got EOF\n";
    });
    $source->write("Hello world!");
    echo "Ending source\n";
    Co::go(function() use ($source) {
        Co::sleep(1);
        $source->end();
    });
}
test();
