<?php

namespace PulkitJalan\GeoIP;

use PulkitJalan\GeoIP\Exceptions\GeoIPException;

class GeoIP
{
    /**
     * @var string
     */
    protected $ip;

    /**
     * @var \PulkitJalan\GeoIP\Contracts\GeoIPInterface
     */
    protected $driver;

    /**
     * @var array
     */
    protected $store = [];

    /**
     * @var array $config
     */
    public function __construct(array $config = ['driver' => 'ip-api'])
    {
        $this->driver = with(new GeoIPManager($config))->getDriver();
    }

    /**
     * Getter for driver
     *
     * @return \PulkitJalan\GeoIP\Contracts\GeoIPInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set ip
     *
     * @var string $ip
     * @return PulkitJalan\GeoIP\GeoIP
     */
    public function setIP($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip from server info
     *
     * @return string ipaddress
     */
    public function getIP()
    {
        return ($this->ip) ?: array_get($_SERVER, 'HTTP_CLIENT_IP', array_get($_SERVER, 'HTTP_X_FORWARDED_FOR', array_get($_SERVER, 'HTTP_X_FORWARDED', array_get($_SERVER, 'HTTP_FORWARDED_FOR', array_get($_SERVER, 'HTTP_FORWARDED', array_get($_SERVER, 'REMOTE_ADDR', '127.0.0.1'))))));
    }

    /**
     * Get an array or single item of geoip data
     * Also stores data in memory for further requests
     *
     * @param  string       $property
     * @return array|string
     */
    public function get($property = '')
    {
        $data = $this->getData();

        if (!$property) {
            return $data;
        }

        return array_get($data, $property, '');
    }

    /**
     * Get an array or single item of geoip data
     *
     * @return array
     * @throws \PulkitJalan\GeoIP\Exceptions\GeoIPException
     */
    protected function getData()
    {
        $ip = $this->getIP();

        // check ip in memory
        $data = array_get($this->store, $ip);

        if (!$data) {
            try {
                $data = $this->getDriver()->get($ip);
            } catch (\Exception $e) {
                throw new GeoIPException('Failed to get geoip data', 0, $e);
            }

            // cache ip data in memory
            $this->store[$ip] = $data;
        }

        return $data;
    }

    /**
     * Magic call method for get*
     *
     * @param  string                  $method
     * @param  array                   $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (starts_with($method, 'get')) {
            $param = lcfirst(ltrim($method, 'get'));

            return $this->get($param);
        }

        throw new \BadMethodCallException(sprintf('Method [%s] does not exist.', $method));
    }
}