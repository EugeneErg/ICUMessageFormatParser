<?php

declare(strict_types = 1);

use EugeneErg\ICUMessageFormatParser\Parser;
use EugeneErg\StringParser\DataTransferObjects\Item;
use EugeneErg\StringParser\DataTransferObjects\Root;

$quota = new Item(
    startPattern: '\'(?=\')',
    endPattern: '\'',
    includeStart: false,
    includeEnd: true,
    save: null,
);
$root = new Root(
    name: Parser::PATTERN,
    exclude: null,
    children: [
        Parser::QUOTA => $quota,
        Parser::TEXT => new Item(
            startPattern: '\'(?=[\\{\\}])',
            endPattern: '\'(?!\'|\\z)',
            includeStart: false,
            includeEnd: true,
            save: true,
            exclude: '\'',
            children: [Parser::QUOTA => $quota],
        ),
        Parser::OBJECT => new Item(
            startPattern: '\\{',
            endPattern: '\\}',
            exclude: '[\\s\\}]',
            children: [
                Parser::VARIABLE => new Item(
                    startPattern: '[a-zA-Z_]',
                    endPattern: '[\\s\\}]',
                    includeStart: true,
                    includeEnd: false,
                    save: null,
                    exception: '[^a-zA-Z_]',
                    children: [
                        Parser::TYPE => new Item(
                            startPattern: ',',
                            endPattern: '[\\}]',
                            includeEnd: false,
                            save: null,
                            exception: '[^a-zA-Z_]',
                            children: [
                                Parser::OPTIONS => new Item(
                                    startPattern: ',',
                                    endPattern: '[\\}]',
                                    includeEnd: false,
                                    save: null,
                                    exception: '.',
                                    children: [
                                        Parser::NESTED => new Item(
                                            startPattern: '\\A.*[=\\{]',
                                            endPattern: '\\}',
                                            includeStart: true,
                                            includeEnd: false,
                                            children: [
                                                Parser::OPTION => new Item(
                                                    startPattern: '[a-zA-Z_=]',
                                                    endPattern: '\\}',
                                                    includeStart: true,
                                                    includeEnd: true,
                                                    exclude: '[\\s\\}]',
                                                    children: [
                                                        Parser::PATTERN => new Item(
                                                            startPattern: '\\{',
                                                            endPattern: '\\}',
                                                            includeStart: false,
                                                            includeEnd: false,
                                                            exclude: null,
                                                            children: [
                                                                Parser::QUOTA => $quota,
                                                                Parser::TEXT => new Item(
                                                                    startPattern: '\'(?=[\\{\\}#])',
                                                                    endPattern: '\'(?!\'|\\z)',
                                                                    includeStart: false,
                                                                    includeEnd: true,
                                                                    save: true,
                                                                    exclude: '\'',
                                                                    children: [Parser::QUOTA => $quota],
                                                                ),
                                                                Parser::OBJECT => &$class,
                                                            ],
                                                        ),
                                                    ],
                                                ),
                                            ],
                                        ),
                                        Parser::SKELETON => new Item(
                                            startPattern: '\\A\\s*::',
                                            endPattern: '\\}',
                                            includeEnd: false,
                                            children: [
                                                Parser::OPTION => new Item(
                                                    startPattern: '[^\\s\\}]',
                                                    endPattern: '(?:(?<!\\s)(?<!\\A))\\s|\\}',
                                                    includeStart: true,
                                                    includeEnd: false,
                                                    save: null,
                                                ),
                                            ],
                                        ),
                                        Parser::PATTERN => new Item(
                                            startPattern: '\\A.+[\'\\}]',
                                            endPattern: '\\}',
                                            includeStart: true,
                                            includeEnd: false,
                                            exclude: null,
                                            children: [
                                                Parser::QUOTA => $quota,
                                                Parser::TEXT => new Item(
                                                    startPattern: '\'(?!\'|\\z)',
                                                    endPattern: '\'(?!\'|\\z)',
                                                    includeStart: false,
                                                    includeEnd: true,
                                                    exclude: '\'',
                                                    children: [Parser::QUOTA => $quota],
                                                ),
                                            ],
                                        ),
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
            ],
        ),
    ],
);
$class = $root->children[Parser::OBJECT];

return $root;