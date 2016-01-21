<?php

namespace Agi\Html;

/**
 * Class Reference
 *
 * @package Agi\Html
 * @author Janpoem created at 2014/10/4 8:14
 */
class Reference
{

    const GLOBAL_REF = 'global';

    const JS = 'js';

    const CSS = 'css';

    const ICON = 'icon';

    const LESS = 'less';

    const LIB = 'lib';

    const HEADER = 'header';

    const FOOTER = 'footer';

    private static $instances = array();

    protected static $libraries = array(
//        'normalize'       => array(
//            array('css', '//static.agimvc.com/stable/normalize.css/3.0.2/normalize.min.css'),
//        ),
//        'bootstrap-css'   => array(
//            array('css', '//static.agimvc.com/??stable/bootstrap/3.2/css/bootstrap.min.css,stable/bootstrap/3.2/css/bootstrap-theme.min.css'),
//        ),
//        'bootstrap-js'   => array(
//            array('js', '//static.agimvc.com/stable/??jquery/1.11.1/jquery.min.js,bootstrap/3.2/js/bootstrap.min.js'),
//        ),
//        'fontawesome'     => array(
//            array('css', '//static.agimvc.com/stable/fontawesome/4.2.0/css/font-awesome.min.css'),
//        ),
//        'fontawesome-3.2' => array(
//            'where' => array(
//                array(
//                    array('css', '//static.agimvc.com/stable/fontawesome/3.2.1/css/font-awesome.min.css',),
//                ),
//                'IE 7' => array(
//                    array('css', '//static.agimvc.com/stable/fontawesome/3.2.1/css/font-awesome-ie7.min.css'),
//                )
//            ),
//        ),
//        'jquery-core'     => array(
//            array('js', '//static.agimvc.com/stable/jquery/1.11.1/jquery.min.js'),
//        ),
//        'mootools-all'    => array(
//            array('js', '//static.agimvc.com/stable/??mootools/1.5.0/mootools-core-full-nocompat.min.js,mootools/1.5.0/mootools-more-yui-compressed.min.js'),
//        ),
    );

    protected static $types = array(
        self::JS   => array('js', 'js', 'type="text/javascript"'),
        self::CSS  => array('css', 'css', 'rel="stylesheet" type="text/css"'),
        self::LESS => array('less', 'less', 'rel="stylesheet/less" type="text/css"'),
        self::ICON => array(null, null, 'rel="shortcut icon" type="image/x-icon"'),
    );

    protected static $home = array(
        self::JS   => 'js',
        self::CSS  => 'css',
        self::LESS => 'less',
        self::ICON => '',
    );

    protected $indexes = array();

    protected $requireGroups = array(
        self::HEADER => array(),
        self::FOOTER => array(),
    );

    protected $name = null;

    protected $defaultGroup = self::FOOTER;

    private $loaded = array();

    private $loadedLibrary = array();

    private $rand = -1;

    /**
     * @param string $name
     *
     * @return Reference
     */
    public static function getInstance($name = self::GLOBAL_REF)
    {
        if (empty($name) || !is_string($name)) {
            $name = self::GLOBAL_REF;
        }
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new static($name);
        }

        return self::$instances[$name];
    }

    public static function setLibrary($name, array $settings)
    {
        if (empty($name) || empty($settings)) {
            return false;
        }
        self::$libraries[$name] = $settings;

        return true;
    }

    public static function setLibraries(array $libraries)
    {
        foreach ($libraries as $name => $settings) {
            if (is_array($settings)) {
                static::setLibrary($name, $settings);
            }
        }
    }

    final private function __construct($name)
    {
        $this->name = $name;
    }

    public function getRand() {
        if ($this->rand < 0) {
            $this->rand = mt_rand(100000, 9999999);
        }
        return $this->rand;
    }

    public function filterUrl($ref, $type = null)
    {
        if (empty($ref) || !is_string($ref)) {
            return false;
        }
        if (!preg_match('#^((?:([a-z]+)\:)?\/\/)#i', $ref)) {
            if (!empty($type) && isset(self::$types[$type])) {
                list($home, $ext,) = self::$types[$type];
                if (!empty($home)) {
                    $ref = $home . DS . $ref;
                }
                if (!empty($ext)) {
                    $ref = ext($ref, $ext);
                }
            }
//            if (APP_ENV === \App::ENV_DEV)
//                $ref = httpUri($ref, array('rand' => $this->getRand()));
//            else
            $ref = httpUri($ref);
        }

        return $ref;
    }

    protected function putIndex($ref, $type, $group, $forceLib = false)
    {
        $index = false;
        if (isset(self::$libraries[$ref])) {
            $type = self::LIB;
            $index = $ref;
        } elseif (!$forceLib) {
            $index = $this->filterUrl($ref, $type);
        }
        if (empty($index)) {
            return $this;
        }
        if (!isset($this->indexes[$index])) {
            $this->indexes[$index] = array($type, $index);
            if (!isset($this->requireGroups[$group])) {
                $this->requireGroups[$group] = array();
            }
            if (!isset($this->requireGroups[$group][$index])) {
                $this->requireGroups[$group][$index] = true;
            }
        }

        return $this->indexes[$index];
    }

    public function getRequires()
    {
        return $this->requireGroups;
    }

    public function addRequire($type, $ref, $group = null)
    {
        if (empty($ref)) {
            return $this;
        }
        if (empty($group)) {
            $group = $this->defaultGroup;
        }
        $refType = gettype($ref);
        if (isset(self::$types[$type])) {
            if ($refType === PHP_ARY) {
                foreach ($ref as $item) {
                    $this->putIndex($item, $type, $group);
                }
            } elseif ($refType === PHP_STR) {
                $this->putIndex($ref, $type, $group);
            }
        } else {
            // 未知的$type
            if ($refType === PHP_ARY) {
                // array('js', 'jquery')
                if (!empty($ref[0]) && !empty($ref[1])) {
                    $this->addRequire($ref[0], $ref[1], $group);
                }
            } else {
                // library_name
                $this->putIndex($ref, null, $group, true);
            }
        }

        return $this;
    }

    public function requires($type, $ref = null, $group = null)
    {
        if (empty($type)) {
            return $this;
        }
        $valType = gettype($type);
        if ($valType === PHP_OBJ) {
            // Object暂时不知道该如何处理
            return $this;
        } elseif ($valType === PHP_ARY) {
            // $ref->req(array('js' => 'xxx', 'css' => 'yyy'), 'header')
            $group = $ref;
            foreach ($type as $key => $value) {
                $this->addRequire($key, $value, $group);
            }
        } else {
            if (isset(self::$libraries[$type])) {
                $group = $ref;
                $this->addRequire(null, $type, $group);
            } else {
                $this->addRequire($type, $ref, $group);
            }
        }

        return $this;
    }

    public function load($group = null)
    {
        if (empty($group)) {
            $group = $this->defaultGroup;
        }
        if (isset($this->requireGroups[$group])) {
            $group = $this->requireGroups[$group];
        } elseif (isset(self::$libraries[$group])) {
            $group = self::$libraries[$group];
        } else {
            $group = null;
        }
        if (empty($group)) {
            return $this;
        }
        foreach ($group as $index => $value) {
            if (!isset($this->indexes[$index])) {
                continue;
            }
            $this->loadRef($this->indexes[$index]);
        }

        return $this;
    }

    protected function loadRef(array $ref, $where = null)
    {
        if (empty($ref[0]) || empty($ref[1])) {
            return $this;
        }
        $isWhere = !empty($where) && is_string($where);
        if ($isWhere) {
            echo "<!--[if {$where}]>\r\n";
        }
        list($type, $url) = $ref;
        switch ($type) {
            case self::LIB :
                $this->loadLib($url);
                break;
            case self::JS :
                $this->loadJs($url);
                break;
            case self::CSS :
                $this->loadCss($url);
                break;
            case self::LESS :
                $this->loadLess($url);
                break;
        }
        if ($isWhere) {
            echo "<![endif]-->\r\n";
        }

        return $this;
    }

    public function loadLib($lib)
    {
        if (!isset(self::$libraries[$lib]) || isset($this->loadedLibrary[$lib])) {
            return $this;
        }
        $this->loadedLibrary[$lib] = true;
        if (isset(self::$libraries[$lib]['where'])) {
            foreach (self::$libraries[$lib]['where'] as $where => $ref) {
                foreach ($ref as $item) {
                    $this->loadRef($item, $where);
                }
            }
        } else {
            foreach (self::$libraries[$lib] as $ref) {
                $this->loadRef($ref);
            }
        }
        return $this;
    }

    public function loadJs($js)
    {
        if (empty($js)) {
            return $this;
        }
        if (is_array($js)) {
            foreach ($js as $item) {
                $this->loadJs($item);
            }

            return $this;
        } elseif (is_string($js)) {
            if (!isset($this->indexes[$js])) {
                $js = $this->filterUrl($js, self::JS);
            }
            $this->loaded[$js] = true;
            $attr = self::$types[self::JS][2];
            $html = '<script ' . $attr . ' src="' . $js . '"></script>' . "\r\n";
            echo $html;
        }

        return $this;
    }

    public function loadCss($css, $media = null)
    {
        if (empty($css)) {
            return $this;
        }
        if (is_array($css)) {
            foreach ($css as $item) {
                $this->loadCss($css, $media);
            }

            return $this;
        } elseif (is_string($css)) {
            if (!isset($this->indexes[$css])) {
                $css = $this->filterUrl($css, self::CSS);
            }
            $attr = self::$types[self::CSS][2];
            if (!empty($media) && is_string($media)) {
                $attr .= ' media="' . $media . '"';
            }
            $html = '<link ' . $attr . ' href="' . $css . '" />' . "\r\n";
            echo $html;
        }

        return $this;
    }

    public function loadLess($css, $media = null)
    {
        if (empty($css)) {
            return $this;
        }
        if (is_array($css)) {
            foreach ($css as $item) {
                $this->loadCss($css, $media);
            }

            return $this;
        } elseif (is_string($css)) {
            if (!isset($this->indexes[$css])) {
                $css = $this->filterUrl($css, self::LESS);
            }
            $attr = self::$types[self::LESS][2];
            if (!empty($media) && is_string($media)) {
                $attr .= ' media="' . $media . '"';
            }
            $html = '<link ' . $attr . ' href="' . $css . '" />' . "\r\n";
            echo $html;
        }

        return $this;
    }
}
