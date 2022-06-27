<?php
require_once(__DIR__."/../../vendor/autoload.php");

use Moebius\Streams\{
    Source,
    ReadableStream
};

$source = new Source();
$source->on('data', function($e, $chunk) {
    echo "Got chunk '$chunk'\n";
});
$source->write("Hello world!");
$source->end();
try {
    $source->write("Not here");
} catch (\LogicException $e) {
    echo "Error as expected\n";
}
