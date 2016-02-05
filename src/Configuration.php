<?php


namespace Uptime;


class Configuration
{
    private $instances;

    public function __construct()
    {
        $this->instances = new \SplObjectStorage();
    }

    public function addInstance(Instance $instance)
    {
        if (!$this->instances->contains($instance)) {
            $this->instances->attach($instance);
        }
    }

    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * @param $url
     * @return null|Instance
     */
    public function findInstanceByUrl($url)
    {
        /** @var Instance $instance */
        foreach ($this->instances as $instance) {
            if ($instance->getUrl() == $url) {
                return $instance;
            }
        }
        return null;
    }
}