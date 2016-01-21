<?php

namespace Agi\Util;

use Imagick;
use \Exception;

/**
 * Class ImageImagick
 *
 * 图片处理，基于Imagick
 * @package Agi\Util
 * @author  Janpoem created at 2014/10/15 3:46
 */
class ImagickImage
{

    private static $transparentFormats = [
        'PNG'  => 1,
        'PNG8' => 1,
        'GIF'  => 1,
        'SVG'  => 1,
    ];

    protected $jpegCompressSize = 1048576; // 1MB

    protected $jpegCompressQuality = 75;

    private $file = null;

    private $isLoad = false;

    /** @var \Imagick */
    private $imagick = null;

    public static function optimizeJpeg(Imagick $im, $compressQuality = 0)
    {
        try {
            $im->stripImage();
            $im->setImageFormat('JPEG');
            $im->setInterlaceScheme(imagick::INTERLACE_PLANE);
            $size = $im->getImageLength();
            if ($compressQuality > 0) {
                if ($compressQuality < 10) $compressQuality = 10;
                elseif ($compressQuality > 100) $compressQuality = 100;
                $im->setImageCompression(Imagick::COMPRESSION_JPEG);
                $quality = $im->getImageCompressionQuality() * ($compressQuality / 100);
                if ($quality <= 0)
                    $quality = $compressQuality;
                $im->setImageCompressionQuality($quality);
            }
        }
        catch (Exception $ex) {
        }
        return $im;
    }

    public static function optimizePng(Imagick $im)
    {
        try {
            $im->stripImage();
            // png强制转png8还是不妥
//            $alpha = $im->getImageAlphaChannel();
//            if ($alpha === Imagick::ALPHACHANNEL_UNDEFINED || $alpha === Imagick::ALPHACHANNEL_DEACTIVATE) {
//                $im->setImageFormat('PNG8');
//                $im->setImageDepth(8);//设定图片位深
//                $im->setColorspace(imagick::COLORSPACE_RGB);//设定颜色空间
//                $im->setImageType(imagick::IMGTYPE_PALETTE); //核心------设定图片类型为绘图板（Palette）
//            }
        }
        catch (Exception $ex) {
        }
        return $im;
    }

    public static function optimizePng8(Imagick $im)
    {
        try {
            $im->stripImage();
            $alpha = $im->getImageAlphaChannel();
            if ($alpha === Imagick::ALPHACHANNEL_UNDEFINED || $alpha === Imagick::ALPHACHANNEL_DEACTIVATE) {
                $im->setImageFormat('PNG8');
                $im->setImageDepth(8);//设定图片位深
                $im->setColorspace(imagick::COLORSPACE_RGB);//设定颜色空间
                $im->setImageType(imagick::IMGTYPE_PALETTE); //核心------设定图片类型为绘图板（Palette）
            }
        }
        catch (Exception $ex) {
        }
        return $im;
    }

    public static function optimizeGif(Imagick $im)
    {
        try {
            $format = $im->getImageFormat();
            if ($format === 'GIF') {
                $im = $im->coalesceImages();
                $multiFrame = false;
                foreach ($im as $index => $frame) {
                    if ($index > 3) {
                        $multiFrame = true;
                        break;
                    }
                }
                if (!$multiFrame)
                    static::optimizePng8($im);
            }
            // 不是GIF不做任何处理
        }
        catch (Exception $ex) {
        }
        return $im;
    }

    public static function getTransparentColorByFormat($format)
    {
        $format = strtoupper($format);
        if (isset(self::$transparentFormats[$format]))
            return 'transparent';
        else
            return 'white';
    }

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function __destruct()
    {
        if (!empty($this->imagick)) {
            $this->imagick->clear();
            $this->imagick->destroy();
            unset($this->imagick);
        }
    }

    public function load()
    {
        if ($this->isLoad)
            return $this;
        if (!isset($this->imagick))
            $this->imagick = new Imagick();
        if (!empty($this->file) && is_file($this->file) && is_readable($this->file)) {
            $this->imagick->readImage($this->file);
            $this->isLoad = true;
        }
        return $this;
    }

    public function getImagick()
    {
        if (!$this->isLoad)
            $this->load();
        return $this->imagick;
    }

    public function setJpegCompressSize($value)
    {
        if (is_numeric($value) && $value > 0)
            $this->jpegCompressSize = intval($value);
        return $this;
    }

    public function optimize($compressQuality = 0)
    {
        $im = $this->getImagick();
        $format = $im->getImageFormat();
        if ($format === 'JPEG') {
            $size = $im->getImageLength();
            if ($size > $this->jpegCompressSize && $compressQuality <= 0)
                $compressQuality = $this->jpegCompressQuality;
            static::optimizeJpeg($im, $compressQuality);
        }
        elseif ($format === 'PNG') {
            static::optimizePng($im);
        }
        elseif ($format === 'GIF') {
            static::optimizeGif($im);
        }
        return $this;
    }

    public function save($path)
    {
        if (empty($path) || !is_string($path) || !$this->isLoad)
            return false;
        if ($path === $this->file)
            return false;
        $dir = dirname($path);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        try {
            $this->getImagick()->writeImage($path);
            return true;
        }
        catch (Exception $ex) {
            return false;
        }
    }

    /**
     * 这个方法，先等比例缩小原图到指定的width和height，然后再创建一张width 、height图片，
     * 将缩放的图片按照水平居中、垂直居中的方式存放。并且文件格式自动化按照最优的格式处理。
     *
     * 比如，一张图，原图是400 x 300，调用该方法时指定width = 200, height = 200
     * 1. 原图缩放到200 x 150
     * 2. 创建白底（透明）画布200 x 200
     * 3. 将缩放的图居中放置在200 x 200的画布上
     *
     * @param string $path    要保存的路径
     * @param int    $width   宽
     * @param int    $height  高
     * @param int    $quality 精度
     * @param int    $maxSize
     * @param bool   $bgColor
     * @return bool
     */
    public function thumbnailFixedSizeAutoFormat($path, $width, $height, $quality = 80, $maxSize = 0, $bgColor = false)
    {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        try {
            $format = 'JPEG';
            $iw = $im->getImageWidth();
            $ih = $im->getImageHeight();
            if (($iw > $width || $ih > $height)) {
                $im->resizeImage($width, $height, Imagick::FILTER_CATROM, 1, true);
            }
            $nw = $im->getImageWidth();
            $nh = $im->getImageHeight();

            $left = $nw < $width ? ($width - $nw) / 2 : 0;
            $top = $nh < $height ? ($height - $nh) / 2 : 0;

            $color = $im->getImageColors();
            if ($color <= 128) {
                $format = 'GIF';
            }

            if (empty($bgColor) || !$bgColor)
                $bgColor = static::getTransparentColorByFormat($format);

            $clone = new Imagick();
            $clone->newImage($width, $height, new \ImagickPixel($bgColor), $format);
            $clone->compositeImage($this->getImagick(), Imagick::COMPOSITE_OVER, $left, $top);

            $cloneFormat = $clone->getImageFormat();

            if ($cloneFormat === 'JPEG')
                static::optimizeJpeg($clone, $quality);

            $size = strlen($clone->getImageBlob());

            // 超出尺寸的，暂时只再压缩10%的精度
            if ($maxSize >= 10 * 1024 && $size > $maxSize) {
                static::optimizeJpeg($clone, $quality * 0.9);
            }

            $dir = dirname($path);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $clone->writeImage(strtolower($format) . ':' . $path);

            $im->clear();
            $im->destroy();

            $clone->clear();
            $clone->destroy();

            return true;
        }
        catch (Exception $ex) {

            return false;
        }
    }

    /**
     * 这个方法的处理逻辑类同于thumbnailFixedSizeAutoFormat，但是因为是指定格式的，所以就直接去掉精度了
     *
     * 并且因为指定格式处理，很难对尺寸进行限制了，所以就不再限制尺寸的问题
     *
     * 去除背景色的问题，主要针对于当格式为png的时候，而且也只是针对原图为jpg的时候，才会去背景
     *
     * @param string $path 要保存的路径
     * @param int    $width
     * @param int    $height
     * @param string $format
     * @param bool   $bgColor
     * @param bool   $removeBgColor
     * @return bool
     */
    public function thumbnailFixedSizeByFormat(
        $path,
        $width,
        $height,
        $format = 'jpg',
        $bgColor = false,
        $removeBgColor = false
    ) {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        try {
            $format = strtoupper(trim($format));
            if ($format !== 'PNG' && $format !== 'GIF' && $format !== 'PNG8') {
                $format = 'JPEG';
                $removeBgColor = false;
            }
            $if = $im->getImageFormat();
            $iw = $im->getImageWidth();
            $ih = $im->getImageHeight();
            if (($iw > $width || $ih > $height)) {
                $im->resizeImage($width, $height, Imagick::FILTER_CATROM, 1, true);
            }

            if ($removeBgColor && $if === 'JPEG')
                $removeBgColor = true;

            if ($removeBgColor)
                $im->trimImage(0);

            // 默认给一个透明背景色，jpeg就是白色
            if (empty($bgColor) || !$bgColor)
                $bgColor = static::getTransparentColorByFormat($format);

            $nw = $im->getImageWidth();
            $nh = $im->getImageHeight();

            $left = $nw < $width ? ($width - $nw) / 2 : 0;
            $top = $nh < $height ? ($height - $nh) / 2 : 0;

            $clone = new Imagick();
            $clone->newImage($width, $height, new \ImagickPixel($bgColor), $format);
            $clone->compositeImage($this->getImagick(), Imagick::COMPOSITE_OVER, $left, $top);

            $dir = dirname($path);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $clone->writeImage(strtolower($format) . ':' . $path);

            $im->clear();
            $im->destroy();

            $clone->clear();
            $clone->destroy();

            return true;
        }
        catch (Exception $ex) {
            var_dump($ex);
            return false;
        }
    }

    public function forceResizeToJpeg($path, $width, $height, $quality = 80)
    {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        try {
            $format = 'JPEG';

            $iw = $im->getImageWidth();
            $ih = $im->getImageHeight();
            $maxSide = $width > $height ? $width : $height;

            // 原图宽小于高，则只放大宽度
            if ($iw < $ih)
                $im->resizeImage($maxSide, 0, Imagick::FILTER_CATROM, 1);
            // 原图高小于宽，则只放大高度
            elseif ($ih < $iw)
                $im->resizeImage(0, $maxSide, Imagick::FILTER_CATROM, 1);
            // 等比放大
            else
                $im->resizeImage($maxSide, $maxSide, Imagick::FILTER_CATROM, 1);

            $nw = $im->getImageWidth();
            $nh = $im->getImageHeight();

            $x = 0;
            $y = 0;

            // 新图片宽度超出了
            if ($nw > $width)
                $x = intval(($nw - $width) / 2);
            // 新图片高度超出了
            if ($nh > $height)
                $y = intval(($nh - $height) / 2);

            if ($x > 0 || $y > 0)
                $im->cropImage($width, $height, $x, $y);

            static::optimizeJpeg($im, $quality);

            $dir = dirname($path);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $im->writeImage(strtolower($format) . ':' . $path);

            $im->clear();
            $im->destroy();

            return true;
        }
        catch (Exception $ex) {

            return false;
        }
    }

    public function saveThumbnail($path, $width, $height, $quality = 80, $forceSize = false)
    {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        $iw = $im->getImageWidth();
        $ih = $im->getImageHeight();
//        if ($forceSize || ($iw > $width || $ih > $height)) {
//            $im->cropThumbnailImage($width, $height);
//            $im->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);
//        }
        $im->cropThumbnailImage($width, $height);
//        $im->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);

        $format = $im->getImageFormat();
        if ($format === 'JPEG') {
            static::optimizeJpeg($im, $quality);
        }
        else if ($format === 'GIF') {
            static::optimizePng8($im);
        }
        elseif ($format === 'PNG') {
            static::optimizePng8($im);
        }
//        $im->setFormat('JPEG');
//        static::optimizeJpeg($im, $quality);

        $dir = dirname($path);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        try {
            $this->getImagick()->writeImage($path);
            return true;
        }
        catch (Exception $ex) {
            return false;
        }
    }

    public function cropSizeToJpeg($path, $width, $height, $quality = 80)
    {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        try {
            $format = 'JPEG';
            $iw = $im->getImageWidth();
            $ih = $im->getImageHeight();

            $left = 0;
            $top = 0;

            if ($iw > $width) {
                $left = round(($width - $iw) / 2, 0);
            }
            else {
                $width = $iw;
            }
            if ($ih > $height) {
                $top = round(($height - $ih) / 2, 0);
            }
            else {
                $height = $ih;
            }

            $bgColor = static::getTransparentColorByFormat($format);

            $clone = new Imagick();
            $clone->newImage($width, $height, new \ImagickPixel($bgColor), $format);
            $clone->compositeImage($this->getImagick(), Imagick::COMPOSITE_OVER, $left, $top);

            static::optimizeJpeg($clone, $quality);
            $dir = dirname($path);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $clone->writeImage(strtolower($format) . ':' . $path);

            $clone->clear();
            $clone->destroy();
            $im->clear();
            $im->destroy();
            return true;
        }
        catch (Exception $ex) {
            return false;
        }
    }

    /**
     * 以宽度为基础缩小为JPEG，小于指定的宽度，则忽略不计
     *
     * @param     $path
     * @param     $width
     * @param int $quality
     * @return bool
     */
    public function narrowByShortSideToJpeg($path, $size, $quality = 80)
    {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        try {
            $format = 'JPEG';

            $iw = $im->getImageWidth();
            $ih = $im->getImageHeight();

            if ($iw <= $ih) {
                if ($iw > $size) {
                    $height = round($size / $iw * $ih, 0);
                    $im->resizeImage($size, $height, Imagick::FILTER_CATROM, 1);
                }
            }
            elseif ($ih < $iw) {
                if ($ih > $size) {
                    $width = round($size / $ih * $iw, 0);
                    $im->resizeImage($width, $size, Imagick::FILTER_CATROM, 1);
                }
            }

            static::optimizeJpeg($im, $quality);
            $dir = dirname($path);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $im->writeImage(strtolower($format) . ':' . $path);

            $im->clear();
            $im->destroy();
            return true;
        }
        catch (Exception $ex) {
            return false;
        }
    }

    /**
     * 以宽度为基础缩小为JPEG，小于指定的宽度，则忽略不计
     *
     * @param     $path
     * @param     $width
     * @param int $quality
     * @return bool
     */
    public function narrowByWidthToJpeg($path, $width, $quality = 80)
    {
        $im = $this->getImagick();
        if (!$this->isLoad)
            return false;
        try {
            $format = 'JPEG';

            $iw = $im->getImageWidth();
            $ih = $im->getImageHeight();

            if ($iw > $width) {
                $height = round($width / $iw * $ih, 0);
                $im->resizeImage($width, $height, Imagick::FILTER_CATROM, 1);
            }

            static::optimizeJpeg($im, $quality);
            $dir = dirname($path);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $im->writeImage(strtolower($format) . ':' . $path);

            $im->clear();
            $im->destroy();
//
//            // 原图宽小于高，则只放大宽度
//            if ($iw < $ih)
//                $im->resizeImage($maxSide, 0, Imagick::FILTER_CATROM, 1);
//            // 原图高小于宽，则只放大高度
//            elseif ($ih < $iw)
//                $im->resizeImage(0, $maxSide, Imagick::FILTER_CATROM, 1);
//            // 等比放大
//            else
//                $im->resizeImage($maxSide, $maxSide, Imagick::FILTER_CATROM, 1);
//
//            $nw = $im->getImageWidth();
//            $nh = $im->getImageHeight();
//
//            $x = 0;
//            $y = 0;
//
//            // 新图片宽度超出了
//            if ($nw > $width)
//                $x = intval(($nw - $width) / 2);
//            // 新图片高度超出了
//            if ($nh > $height)
//                $y = intval(($nh - $height) / 2);
//
//            if ($x > 0 || $y > 0)
//                $im->cropImage($width, $height, $x, $y);
//
//            static::optimizeJpeg($im, $quality);
//
//            $dir = dirname($path);
//            if (!is_dir($dir))
//                mkdir($dir, 0755, true);
//
//            $im->writeImage(strtolower($format) . ':' . $path);
//
//            $im->clear();
//            $im->destroy();

            return true;
        }
        catch (Exception $ex) {
            return false;
        }
    }


}

 