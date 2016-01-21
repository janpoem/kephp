<?php

namespace Agi\Output;

/**
 * Class UrlRedirector
 *
 * @package Agi\Output
 * @author Janpoem created at 2014/9/25 15:17
 */
class UrlRedirection implements Responder
{

    private $url = null;

    private $query = null;

    public function __construct($url = null, $query = null)
    {
        if (!is_string($url))
            throw new Exception('Url should be string type');
        $this->url = $url;
        $this->query = $query;
    }

    public function assign($key, $value = null)
    {
        // TextWriter 就不需要assign了
        return $this;
    }

    public function output()
    {
        header('Location: ' . linkUri($this->url, $this->query));
        exit();
    }
}
