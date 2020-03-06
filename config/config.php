<?php

use Signifly\EventSourceGenerator\FilePerNamespaceWriter;

return [

    /*
     * The file writer you want to use.
     *
     * It must implement the \Signifly\EventSourceGenerator\FileWriter interface.
     *
     * By default it uses \Signifly\EventSourceGenerator\FilePerNamespaceWriter.
     */
    'writer' => FilePerNamespaceWriter::class,

    /*
     * The arguments that will be passed to the file writer.
     *
     * It must be a key (argument), value pair.
     */
    'writer_arguments' => [
        'fileName' => 'events_and_commands.php',
    ],

    /*
     * The level of error output, when examples are missing.
     *
     * Valid values are: ['error', 'warn', 'ignore']
     */
    'missing_examples' => 'warn',

    /*
     * The files to parse.
     */
    'definitions' => [
        //
    ],

];
