<?php

namespace Lurker\StateChecker;

use ClxProductNet_Log;
use Lurker\Event\FilesystemEvent;
use Lurker\Resource\GlobResource;
use Lurker\Resource\ResourceInterface;

/**
 * Recursive directory state checker.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class GlobStateChecker implements StateCheckerInterface
{
    private $resource;

    private $timestamp;

    private $eventsMask;

    private $deleted = false;

    /**
     * Initializes checker.
     *
     * @param ResourceInterface $resource   resource
     * @param integer           $eventsMask event types bitmask
     */
    public function __construct(ResourceInterface $resource, $eventsMask = FilesystemEvent::ALL)
    {
        $this->resource = $resource;
        $this->timestamp = $resource->getModificationTime() + 1;
        //$this->eventsMask = $eventsMask;
        $this->eventsMask = FilesystemEvent::MODIFY;
        $this->deleted = !$resource->exists();
        $this->log = ClxProductNet_Log::create();
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Returns events mask for checker.
     *
     * @return integer
     */
    public function getEventsMask()
    {
        return $this->eventsMask;
    }

    public function getChangeset()
    {
        $changeset = array();

        if ($this->deleted) {
            if ($this->resource->exists()) {
                $this->timestamp = $this->resource->getModificationTime() + 1;
                $this->deleted = false;

                if ($this->supportsEvent($event = FilesystemEvent::CREATE)) {
                    $changeset[] = array(
                        'event' => $event,
                        'resource' => $this->resource
                    );
                }
            }
        } elseif (!$this->resource->exists()) {
            $this->deleted = true;

            if ($this->supportsEvent($event = FilesystemEvent::DELETE)) {
                $changeset[] = array(
                    'event' => $event,
                    'resource' => $this->resource
                );
            }
        } elseif (!$this->resource->isFresh($this->timestamp)) {
            $this->timestamp = $this->resource->getModificationTime() + 1;

            if ($this->supportsEvent($event = FilesystemEvent::MODIFY)) {
                $changeset[] = array(
                    'event' => FilesystemEvent::CREATE,
                    //'event' => $event,
                    'resource' => $this->resource
                );
            }
        }

        if (!empty($changeset)) {
            //$this->log->debug(var_export($changeset, true));
        }

        return $changeset;
    }

    /**
     * Checks whether checker supports provided resource event.
     *
     * @param integer $event
     *
     * @return Boolean
     */
    protected function supportsEvent($event)
    {
        return 0 !== ($this->eventsMask & $event);
    }

    /**
     * Checks whether resource have been previously deleted.
     *
     * @return Boolean
     */
    protected function isDeleted()
    {
        return $this->deleted;
    }
}
