<?php


namespace Uptime;


class Instance extends \ArrayIterator
{


    public function __construct(array $instance = [])
    {

        array_walk($instance, function ($value, $key) {
            $this->offsetSet($key, $value);
        });

    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->offsetGet('url');
    }


    /**
     * @return mixed
     */
    public function getSentryDsn()
    {
        return $this->offsetGet('sentry_dsn');
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

}