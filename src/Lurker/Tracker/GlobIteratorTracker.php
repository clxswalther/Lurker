<?php

namespace Lurker\Tracker;

use Lurker\Exception\InvalidArgumentException;
use Lurker\Resource\DirectoryResource;
use Lurker\Event\FilesystemEvent;
use Lurker\Resource\GlobResource;
use Lurker\Resource\TrackedResource;
use Lurker\StateChecker\DirectoryStateChecker;
use Lurker\StateChecker\FileStateChecker;
use Lurker\StateChecker\GlobStateChecker;

use ClxProductNet_Log;

/**
 * Recursive iterator resources tracker.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class GlobIteratorTracker implements TrackerInterface
{
    private $checkers = array();

    private $globPattern;

    public function __construct(string $globPattern = '*')
    {
        $this->globPattern = $globPattern;
    }

    public function getGlobPattern()
    {
        return $this->globPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function track(TrackedResource $resource, $eventsMask = FilesystemEvent::ALL)
    {
        $trackingId = $resource->getTrackingId();

        if ($resource->getOriginalResource() instanceof GlobResource) {
            $checker = new GlobStateChecker($resource->getOriginalResource(), $eventsMask);
        }
        else {
            throw new InvalidArgumentException(sprintf(
                'Second argument to track() should be a glob resource, ' .
                'but got "%s"',
                $resource
            ));
        }

        $this->checkers[$trackingId] = array(
            'tracked' => $resource,
            'checker' => $checker
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents()
    {
        $events = array();
        $log = ClxProductNet_Log::create();
        foreach ($this->checkers as $trackingId => $meta) {
            $tracked = $meta['tracked'];
            $checker = $meta['checker'];

            foreach ($checker->getChangeset() as $change) {
                $log->debug('Checker: ' . var_export($change, true));
                $events[] = new FilesystemEvent($tracked, $change['resource'], $change['event']);
            }
        }

        return $events;
    }
}
