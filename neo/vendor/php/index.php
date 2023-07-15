<?php

/*
 * Constants.
 */
define('ROOT', dirname(__DIR__, 2));

/*
 * Routes.
 */
if (isset($_GET['action']) && $_GET['action'] === 'compile') {
    compileHtmlTemplates();
}

/*
 * Functions.
 */
function compileHtmlTemplates(): void
{
    $htmlFolder = ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'html';
    $htmlFiles = walkFiles($htmlFolder);

    foreach ($htmlFiles as $htmlPath) {
        $htmlContent = file_get_contents($htmlPath);

        compileMediaQueries($htmlContent, $htmlPath);
    }
}

function compileMediaQueries(string $htmlTemplate, string $htmlPath): string
{
    $regexMediaQuery = '/@media\\((\\d+)\\)\\s(.+)\\n/';

    return preg_replace_callback($regexMediaQuery, function ($matches) use ($htmlTemplate) {
        $fullMediaString = $matches[0];
        $screenWidth = $matches[1];
        $classes = explode(' ', $matches[2]);

        dd(parseClassFromCss($classes[0], $htmlTemplate));
    }, $htmlTemplate);
}

function parseClassFromCss(string $className, string $htmlTemplate)
{
    $regexIncludedStylesheets = '/<link\srel="stylesheet"\shref=".+"/';

    preg_replace_callback($regexIncludedStylesheets, function ($matches) {
    }, $htmlTemplate);
}

function makeStyleMediaQuery()
{
}

function walkFiles(string $path, array $files = []): array
{
    $directories = scandir($path);

    if ($directories === false) {
        throw new \Exception('Damn, boy. Something bad happens with our PHP brother\' scandir() function.');
    }

    unset($directories[0]);
    unset($directories[1]);

    foreach ($directories as $directory) {
        $filePath = $path . DIRECTORY_SEPARATOR . $directory;

        if (is_dir($filePath)) {
            return walkFiles($filePath, $files);
        }

        $files[] = $filePath;
    }

    return $files;
}

function dd(...$args)
{
    var_dump($args);
    exit;
}

function dump(...$args)
{
    var_dump($args);
}