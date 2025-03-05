<?php
namespace App\Core\Template;

use App\Core\Template\Contracts\TemplateEngine;
use App\Core\Template\Contracts\TemplateParser;
use App\Core\Template\Contracts\View;
use App\Utils\Files;
use App\Utils\Paths;
use App\Utils\Strings;

class TestingserverTemplateEngine implements TemplateEngine
{
    public const SUBDIRECTORY_SEPARATOR = '.';
    private const CACHE_DIRECTORY_SUFFIX = '-cache';
    private const PHP_FILE_EXTENSION = '.php';

    private TemplateParser $parser;
    private string $viewPath;
    private string $viewExtension;
    private array $sharedData;
    private bool $ignoreCache;

    public function __construct(TemplateParser $parser, string $viewPath, string $viewExtension)
    {
        $this->parser = $parser;
        $this->viewPath = Paths::normalize($viewPath);
        $this->viewExtension = $viewExtension;
        $this->sharedData = [];
        $this->ignoreCache = false;
    }

    public function setIgnoreCache(bool $ignoreCache): void
    {
        $this->ignoreCache = $ignoreCache;
    }

    #[\Override]
    public function share(string $key, string $value): self
    {
        $this->sharedData[$key] = $value;
        return $this;
    }

    #[\Override]
    public function view(string $viewName, ?array $context = null): View
    {
        $actualViewName = $this->interpretViewName($viewName);
        $viewFilename = basename($actualViewName);
        $subpath = Strings::rtrimSubstr($actualViewName, $viewFilename);
        $cachePath = $this->getCachePath($subpath);
        
        $outputFilename = $this->getOutputFilename($viewFilename);
        $outputFile = $cachePath . DIRECTORY_SEPARATOR . $outputFilename;

        // Get the parsed content
        $content = $this->parseView($actualViewName);

        // Attempt to cache the file if not ignored
        if (!$this->ignoreCache && Files::createDirectory($cachePath)) {
            ob_start();
            try {
                Files::saveFileContent($content, $cachePath, $outputFilename);
                ob_end_clean();
            } catch (\Exception $e) {
                ob_end_clean();
                error_log("Failed to cache view $viewName: " . $e->getMessage());
                // File won't exist, but we proceed with raw content
            }
        } else {
            error_log("Cache ignored or directory creation failed for $viewName");
        }

        $params = array_merge($context ?? [], $this->sharedData);
        // Pass the cached file if it exists, otherwise null, along with raw content
        return new TestingserverView($viewName, file_exists($outputFile) ? $outputFile : '', $params, $content);
    }

    private function interpretViewName(string $viewName): string
    {
        return str_replace(self::SUBDIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $viewName);
    }

    private function getCachePath(string $subpath): string
    {
        $path = rtrim($this->viewPath, DIRECTORY_SEPARATOR);
        $pathSegments = explode(DIRECTORY_SEPARATOR, $path);
        $lastIdx = count($pathSegments) - 1;

        $viewsDirectory = $pathSegments[$lastIdx];
        $cacheDirectory = $viewsDirectory . self::CACHE_DIRECTORY_SUFFIX;

        $pathSegments[$lastIdx] = $cacheDirectory;
        $cachePath = implode(DIRECTORY_SEPARATOR, $pathSegments);
        if ($subpath) {
            $subpath = trim($subpath, DIRECTORY_SEPARATOR);
            $cachePath .= DIRECTORY_SEPARATOR . $subpath;
        }
        return $cachePath;
    }

    private function getOutputFilename(string $viewFilename): string
    {
        return $viewFilename . self::PHP_FILE_EXTENSION;
    }

    /**
     * @param string $view
     * @param array $context
     * @throws \UnexpectedValueException
     */
    private function parseView(string $actualViewName): string
    {
        $file = $this->viewPath . $actualViewName . $this->viewExtension;
        if (!file_exists($file)) {
            throw new \UnexpectedValueException("The view $actualViewName could not be found on $this->viewPath");
        }

        ob_start();
        $content = file_get_contents($file);
        $parsedContent = $this->parser->parse($content);
        ob_end_clean();
        return $parsedContent;
    }
}
