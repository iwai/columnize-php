#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: iwai
 * Date: 2017/02/03
 * Time: 13:05
 */

ini_set('date.timezone', 'Asia/Tokyo');

if (PHP_SAPI !== 'cli') {
    echo sprintf('Warning: %s should be invoked via the CLI version of PHP, not the %s SAPI'.PHP_EOL, $argv[0], PHP_SAPI);
    exit(1);
}

require_once __DIR__.'/../vendor/autoload.php';

use CHH\Optparse;

$parser = new Optparse\Parser();

function usage() {
    global $parser;
    fwrite(STDERR, "{$parser->usage()}\n");
    exit(1);
}

$parser->setExamples([
    sprintf("%s -c 2 ./lines.txt", $argv[0]),
]);

$columns   = null;
$delimiter = null;

$parser->addFlag('help', [ 'alias' => '-h' ], 'usage');
$parser->addFlag('verbose', [ 'alias' => '-v' ]);
$parser->addFlagVar('columns', $columns, [ 'alias' => '-c', 'has_value' => true, 'required' => true ]);
$parser->addFlagVar('delimiter', $delimiter, [ 'alias' => '-d', 'has_value' => true, 'default' => " " ]);
$parser->addArgument('file', [ 'required' => false ]);

try {
    $parser->parse();
} catch (\Exception $e) {
    usage();
}

$file_path = $parser['file'];

try {
    if (!$columns) {
        usage();
    }

    if ($file_path) {
        if (($fp = fopen($file_path, 'r')) === false) {
            die('Could not open '.$file_path);
        }
    } else {
        if (($fp = fopen('php://stdin', 'r')) === false) {
            usage();
        }
        $read = [$fp];
        $w = $e = null;
        $num_changed_streams = stream_select($read, $w, $e, 1);

        if (!$num_changed_streams) {
            usage();
        }
    }

    $rows    = 0;
    $buffers = null;

    $delimiter = str_replace('\r', "\r", $delimiter);
    $delimiter = str_replace('\n', "\n", $delimiter);
    $delimiter = str_replace('\t', "\t", $delimiter);

    while (!feof($fp)) {
        $line = trim(fgets($fp));

        if (!$buffers)
            $buffers = [];
        $buffers[] = $line;

        $rows = $rows + 1;

        if ($columns == $rows) {
            echo implode($delimiter, $buffers), PHP_EOL;
            $rows = 0;
            unset($buffers);
        }
    }
    fclose($fp);

    echo implode($delimiter, $buffers), PHP_EOL;

} catch (\Exception $e) {
    throw $e;
}
