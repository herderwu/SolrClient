<?php
namespace Solr;

class Client
{
    private static $_instance = null;

    /**
     * @return CurlSingleton
     */
    public static function singleton()
    {
        if (is_null(static::$_instance)) {
            static::$_instance = new CurlSingleton();
        }

        return static::$_instance;
    }

    /**
     * @return CurlMulti
     */
    public static function multi()
    {
        return new CurlMulti();
    }
}

class CurlBase
{
    protected static $options = array(
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => 1,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true
    );

    public function setOptions($custom_options)
    {
        static::$options = $custom_options + static::$options;
    }

    protected function postOptions($url, $data = array())
    {
        $data = empty($data) ? $data : array();
        return array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ) + static::$options;
    }

    protected function getOptions($url, $data = array())
    {
        $data = empty($data) ? $data : array();
        return array(
            CURLOPT_URL => $url . (count($data) > 0 ?
                    (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data)
                    : ''),
            CURLOPT_HTTPGET => true
        ) + static::$options;
    }

}

class CurlSingleton extends CurlBase
{
    private static $_curl = null;
    private $_url = null;
    private $_data = array();
    private $_method = 'GET';

    public function close()
    {
        if (is_resource(static::$_curl)) {
            curl_close(static::$_curl);
        }
        static::$_curl = null;
    }

    public function addUrl($url, $data = array(), $method = 'GET')
    {
        $this->_url = $url;
        $this->_data = $data;
        $this->_method = $method;
    }

    public function execute()
    {
        if(empty($this->_url))
            return false;

        $options = strtoupper($this->_method) == 'POST'
            ? $this->postOptions($this->_url, $this->_data)
            : $this->getOptions($this->_url, $this->_data);

        if (!is_resource(static::$_curl)) {
            static::$_curl = curl_init();
        }

        curl_setopt_array(static::$_curl, $options);
        $response = curl_exec(static::$_curl);

        return !curl_errno(static::$_curl) ? $response : false;
    }

    function __destruct() {
        $this->close();
    }

}

class CurlMulti extends CurlBase
{
    public $processLength = 10;
    public $delay = 10;
    private $_urls = array();

    public function addUrl($url, $data = array(), $method = 'GET')
    {
        array_push($this->_urls, array($url, $data, $method));
    }

    public function addUrls($urls)
    {
        foreach($urls as $value){
            $url = $value;
            $data = null;
            $method = null;
            if(is_array($value)){
                list($url, $data, $method) = $value + array(null,null,null);
            }
            $this->addUrl($url, $data, $method);
        }
    }

    public function execute()
    {
        if(empty($this->_urls))
            return false;

        $queue = curl_multi_init();
        $map = array();

        foreach ($this->_urls as $key => $val) {
            $ch = curl_init();
            list($url, $data, $method) = $val + array(null,null,null);

            if(empty($url))
                continue;

            $options = strtoupper($val[2]) == 'POST'
                ? $this->postOptions($url, $data)
                : $this->getOptions($url, $method);
            curl_setopt_array($ch, array(
                    CURLOPT_NOSIGNAL => true
                ) + $options);

            curl_multi_add_handle($queue, $ch);
            $map[(string)$ch] = $key;
        }

        $responses = array();
        do {
            while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

            if ($code != CURLM_OK) {
                break;
            }

            while ($done = curl_multi_info_read($queue)) {
                $results = curl_multi_getcontent($done['handle']);
                $responses[$map[(string)$done['handle']]] = !curl_errno($done['handle']) ? $results : false;

                curl_multi_remove_handle($queue, $done['handle']);
                curl_close($done['handle']);
            }

            if ($active > 0) {
                curl_multi_select($queue, 0.5);
            }

        } while ($active);

        curl_multi_close($queue);
        $this->_urls = array();
        return $responses;
    }

}
