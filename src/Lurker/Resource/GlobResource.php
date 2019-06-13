<?php

namespace Lurker\Resource;

use Lurker\Tracker\GlobIteratorTracker;
use Lurker\Resource\DirectoryResource;
use ClxProductNet_Log;

/**
 * @package Lurker
 */
class GlobResource extends DirectoryResource
{

    private $log;

    private $globPattern;

    public function __construct($resource, $pattern = null, $globPattern = '*')
    {
        $this->log = ClxProductNet_Log::create();

        parent::__construct($resource, $pattern);
        $this->globPattern = $globPattern;

        $this->log->debug('GLOB => ' . $this->getResource() . $this->getGlobPattern());
    }

    public function getGlobPattern()
    {
        return $this->globPattern;
    }

    public function getFilteredResources()
    {
        if (!$this->exists()) {
            $log->debug("GlobResource::getFilteredResources don`t exist.");
            return array();
        }

        // race conditions
        try {
            $iterator = new \GlobIterator($this->getResource() . $this->getGlobPattern(), 4352);
            $log->debug("GlobIterator:\n" . var_export($iterator, true));
        } catch (\UnexpectedValueException $e) {
            $this->log->debug($this->getResource() . $this->getGlobPattern(). "\n");
            return array();
        }

        $resources = array();
        foreach ($iterator as $key => $file) {
            $this->log->debug("Found file '" . $key . "' => '" . $file . "'\n");
            //// if regex filtering is enabled only return matching files
            //if ($file->isFile() && !$this->hasFile($file)) {
            //    continue;
            //}
            //
            //// always monitor directories for changes, except the .. entries
            //// (otherwise deleted files wouldn't get detected)
            //if ($file->isDir() && '/..' === substr($file, -3)) {
            //    continue;
            //}
            //
            //// if file is dot - continue
            //if ($file->isDot()) {
            //    continue;
            //}

            if ($file->isFile()) {
                $resources[] = new FileResource($file->getRealPath());
            }
        }

        return $resources;
    }

    public function exists()
    {
        clearstatcache(true, $resource = $this->getResource());

        return is_dir($resource);
    }

    public function getModificationTime()
    {
        if (!$this->exists()) {
            return -1;
        }

        clearstatcache(true, $this->getResource());
        if (false === $mtime = @filemtime($this->getResource())) {
            return -1;
        }

        return $mtime;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        if (!is_dir($this->getResource())) {
            return false;
        }

        if ($timestamp < filemtime($this->getResource())) {
            return false;
        }

        //$this->log->debug('Update timestamp for resource: ' . $this->getResource());
        return true;
    }
}
