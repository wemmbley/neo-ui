<?php

/*
 * Constants.
 */
define('ROOT', dirname(__DIR__, 2));

/*
 * Routes.
 */
if (isset($_GET['action']) && $_GET['action'] === 'compile') {
    try {
        compileHtmlTemplates();
    } catch (Throwable $exception) {
        echo $exception->getFile() . '(' . $exception->getLine() . '): ' . $exception->getMessage();
    }
}

/*
 * Functions.
 */
function compileHtmlTemplates(): void
{
    $htmlFolder = ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'html';
    $htmlFiles = getFilesPathsFromDirectory($htmlFolder);

    foreach ($htmlFiles as $htmlPath) {
        $htmlContent = file_get_contents($htmlPath);
        $stylesheetsPaths = parseStylesheetPathsFromTemplate($htmlContent);
        $stylesheetsProperties = parseStylesheetClassesPropertiesFromFile__And__SetIdentificationForMediaTokenElements(
            $htmlContent,
            $stylesheetsPaths
        );

        $stylesheetsMediaQueries = generateStylesheetMediaQueries($stylesheetsProperties);
        $newHtmlContent = generateHtmlWithMediaQueries($stylesheetsMediaQueries, $htmlContent);

        $htmlPath = preg_replace('/.*neo\/src\//s', "$1", $htmlPath);
        $htmlPath = ROOT . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . $htmlPath;
        createOrReplaceFile($htmlPath, $newHtmlContent);
    }
}

function parseStylesheetClassesPropertiesFromFile__And__SetIdentificationForMediaTokenElements(
    string &$htmlTemplate,
    array $stylesheetPaths
): array {
    $regexMediaQuery = '/@media\\((\\d+)\\)\\s(.+)\\n/';
    $stylesheetClassProperties = [];
    $tokenIdentifier = 0;

    preg_replace_callback(
        $regexMediaQuery,
        function ($matches) use ($stylesheetPaths, &$stylesheetClassProperties, &$htmlTemplate, &$tokenIdentifier) {
            // This ID needs for identifying our HTML-tags for applying @media queries for it.
            // We delegate class identification from user to our side.
            $tokenIdentifier++;

            $fullMediaString = $matches[0];
            $screenWidth = $matches[1];

            $tokenIdentifierName = setIdentificationForMediaTokenElement__And__DeleteMediaTokensFromTemplate(
                $tokenIdentifier,
                $fullMediaString,
                $htmlTemplate
            );

            $htmlDomElementClasses = explode(' ', $matches[2]);

            foreach ($stylesheetPaths as $stylesheetPath) {
                if (!file_exists($stylesheetPath)) {
                    continue;
                }

                $stylesheetContent = file_get_contents($stylesheetPath);

                foreach ($htmlDomElementClasses as $nodeClass) {
                    $stylesheetClassProperties[$screenWidth][$tokenIdentifierName][$nodeClass] = parseClassPropertiesFromStylesheet(
                        $nodeClass,
                        $stylesheetContent
                    );
                }
            }
        },
        $htmlTemplate
    );

    return $stylesheetClassProperties;
}

function setIdentificationForMediaTokenElement__And__DeleteMediaTokensFromTemplate(
    int $tokenIdentifier,
    string $fullMediaString,
    string &$htmlTemplate
): string {
    $preparedStringForRegex = str_replace('(', '\(', $fullMediaString);
    $preparedStringForRegex = str_replace(')', '\)', $preparedStringForRegex);
    $htmlElementRegex = "/($preparedStringForRegex.*?)\s+(.*?[^<]*)(<.*?>)/s";
    $classNameForMediaQueries = "neo-media-$tokenIdentifier";

    $htmlTemplate = preg_replace_callback(
        $htmlElementRegex,
        function ($matches) use ($classNameForMediaQueries, $tokenIdentifier) {
            $mediaTokens = $matches[2];
            $htmlElement = $matches[3];

            // Add new class to existing classes.
            if (str_contains($htmlElement, 'class')) {
                $htmlElementClassesRegex = '/class="(.*?)"/s';

                $htmlElement = preg_replace(
                    $htmlElementClassesRegex,
                    "class=\"$classNameForMediaQueries $1\"",
                    $htmlElement
                );

                return $mediaTokens . $htmlElement;
            }

            // If we not found existing "class" identifier, than we create our own.
            // Here we're step-by-step constructing new HTML-tag but with class="neo-media-$i"
            $htmlElement = str_replace('>', ' ', $htmlElement);
            $htmlElement = $htmlElement . "class=\"$classNameForMediaQueries\">";

            return $mediaTokens . $htmlElement;
        },
        $htmlTemplate,
        1
    );

    return $classNameForMediaQueries;
}

function parseStylesheetPathsFromTemplate(string &$htmlTemplate): array
{
    $regexIncludedStylesheets = '/<link\s+rel="stylesheet"\s+href="(.*?)"/s';
    $resultStylesheetPaths = [];

    $htmlTemplate = preg_replace_callback($regexIncludedStylesheets, function ($matches) use (&$resultStylesheetPaths) {
        $stylesheetPathLink = $matches[1];
        $neoRelativePath = 'neo';

        $stylesheetRelativePath = str_replace($neoRelativePath, '', $stylesheetPathLink);

        $resultStylesheetPaths[] = $stylesheetRelativePath;

        return "<link rel=\"stylesheet\" href=\"{$stylesheetRelativePath}\"";
    }, $htmlTemplate);

    return $resultStylesheetPaths;
}

function parseClassPropertiesFromStylesheet(string $className, string $stylesheetContent): string
{
    $regexClassProperties = "/\s*([^}]*?\.$className.+?)(?={)\{\s*([^}]*?)\s*}/s";
    $classProperties = [];

    preg_replace_callback($regexClassProperties, function ($matches) use (&$classProperties, $className) {
        // We can check if $className already exists, and write some additional logic here.
        // But now it takes the last match, and it's enough. Maybe in future I'm done with it.
        $classProperties[] = $matches[2];
    }, $stylesheetContent);

    return end($classProperties);
}

function generateStylesheetMediaQueries(array $stylesheetsProperties): string
{
    $isMinify = isset($_GET['minify']);
    $resultHtmlWithMediaQueries = PHP_EOL . '<style>
    /* 
    * Proudly generated with NEO-framework.
    * These styles needed for page adaptive.
    */
    
    ';

    foreach ($stylesheetsProperties as $screenWidth => $tokenNames) {
        if ($isMinify) {
            $resultHtmlWithMediaQueries .= "@media screen and (max-width: {$screenWidth}px){";
        } else {
            $resultHtmlWithMediaQueries .= PHP_EOL . "\t@media screen and (max-width: {$screenWidth}px) " . PHP_EOL . "\t{";
        }

        foreach ($tokenNames as $tokenClass => $stylesheetsClasses) {
            if ($isMinify) {
                $resultHtmlWithMediaQueries .= ".{$tokenClass}";
            } else {
                $resultHtmlWithMediaQueries .= PHP_EOL . "\t\t.{$tokenClass} " . PHP_EOL . "\t\t{";
            }

            foreach ($stylesheetsClasses as $stylesheetClass => $stylesheetProperties) {
                if ($isMinify) {
                    $stylesheetProperties = str_replace(PHP_EOL, '', $stylesheetProperties);
                }

                // After three loops we got everything that we need for constructing result HTML.

                if ($isMinify) {
                    $resultHtmlWithMediaQueries .= $stylesheetProperties;

                    continue;
                }

                $stylesheetProperties = str_replace(PHP_EOL, PHP_EOL . "\t\t\t", $stylesheetProperties);
                $resultHtmlWithMediaQueries .= PHP_EOL . "\t\t\t{$stylesheetProperties} ";
            }

            if ($isMinify) {
                $resultHtmlWithMediaQueries .= "}";
            } else {
                $resultHtmlWithMediaQueries .= PHP_EOL . "\t\t}";
            }
        }

        if ($isMinify) {
            $resultHtmlWithMediaQueries .= "}";
        } else {
            $resultHtmlWithMediaQueries .= PHP_EOL . "\t}";
        }
    }

    $resultHtmlWithMediaQueries .= PHP_EOL . '</style>';

    return $resultHtmlWithMediaQueries;
}

function generateHtmlWithMediaQueries(string $stylesheetsMediaQueries, string $htmlTemplate): array|string|null
{
    $regexEndOfHtml = '/<\/body>\s*([^<]*?)<\/html>/s';

    return preg_replace($regexEndOfHtml, "</body>{$stylesheetsMediaQueries}$1" . PHP_EOL . "</html>", $htmlTemplate);
}

/*
 * Helpers.
 */
function getFilesPathsFromDirectory(string $path, array $files = []): array
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
            return getFilesPathsFromDirectory($filePath, $files);
        }

        $files[] = $filePath;
    }

    return $files;
}

function createOrReplaceFile(string $filePath, string $fileContent): void
{
    try {
        $isInFolder = preg_match("/^(.*)\/([^\/]+)$/", $filePath, $filepathMatches);

        if ($isInFolder) {
            $folderName = $filepathMatches[1];
            $fileName = $filepathMatches[2];

            if (!is_dir($folderName)) {
                mkdir($folderName, 0777, true);
            }
        }

        file_put_contents($filePath, $fileContent);
    } catch (Exception $e) {
        echo "ERR: error writing '$fileContent' to '$filePath', " . $e->getMessage();
    }
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