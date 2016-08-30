<?php
return [
    'sourcePath' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR,
    'languages' => ['fr-CH'], //Add languages to the array for the language files to be generated.
    'translator' => 'Yii::t',
    'sort' => true,
    'removeUnused' => false,
    'only' => ['*.php'],
    'except' => [
        '.svn',
        '.git',
        '.gitignore',
        '.gitkeep',
        '.hgignore',
        '.hgkeep',
        '/messages',
        '/vendor',
        '/assets',
    ],
    'format' => 'php',
    'messagePath' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR,
    'overwrite' => true,
];