<?php
namespace Solr;

class QueryParse
{
    /**
     * Request params
     *
     * Multivalue params are supported using a multidimensional array:
     * @var array
     */
    protected $params = array();

    /**
     * Get the base url for all requests
     *
     * Based on host, path, port and core options.
     *
     * @return string
     */
    public function getBaseUri($config)
    {
        $collection = (!empty($config['is_taxonomy'])) ? $config['endpoint']['collection_taxonomy'] : $config['endpoint']['collection'];
        return $config['endpoint']['host'] . $collection . '/';
    }

    /**
     * Get an URI for this request
     *
     * @return string
     */
    public function getUri()
    {
        return 'select?wt=json&indent=true' . $this->getQueryString();
    }

    /**
     * Get the query string for this request
     *
     * @return string
     */
    public function getQueryString()
    {
        $queryString = '';
        if (count($this->params) > 0) {
            foreach ($this->params as $value) {
                $queryString .= $value;
            }
            //$queryString = rawurlencode($queryString);

            $queryString = preg_replace(
                '/%5B(?:[0-9]|[1-9][0-9]+)%5D=|%3D/',
                '=',
                $queryString
            );
        }

        return $queryString;
    }

    /**
     * Get all params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set request params
     *
     * @param  array $params
     * @return self  Provides fluent interface
     */
    public function setParams($params)
    {
        $this->clearParams();
        $this->addParams($params);

        return $this;
    }

    /**
     * Clear all request params
     *
     * @return self Provides fluent interface
     */
    public function clearParams()
    {
        $this->params = array();

        return $this;
    }

    /**
     * Add a request param
     *
     * If you add a request param that already exists the param will be converted into a multivalue param,
     * unless you set the overwrite param to true.
     *
     * Empty params are not added to the request. If you want to empty a param disable it you should use
     * remove param instead.
     *
     * @param  string       $key
     * @param  string|array $value
     * @param  boolean      $overwrite
     * @return self         Provides fluent interface
     */
    public function addParam($key, $value, $overwrite = false)
    {
        if ($value !== null) {
            if (!$overwrite && isset($this->params[$key])) {
                if (!is_array($this->params[$key])) {
                    $this->params[$key] = array($this->params[$key]);
                }
                $this->params[$key][] = $value;
            } else {
                // not all solr handlers support 0/1 as boolean values...
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                }

                $this->params[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Add multiple params to the request
     *
     * @param  array   $params
     * @param  boolean $overwrite
     * @return self    Provides fluent interface
     */
    public function addParams($params, $overwrite = false)
    {
        foreach ($params as $key => $value) {
            $this->addParam($key, $value, $overwrite);
        }

        return $this;
    }
}
