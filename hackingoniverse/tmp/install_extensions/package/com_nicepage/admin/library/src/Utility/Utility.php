<?php
/**
 * @package Nicepage Website Builder
 * @author Nicepage https://www.nicepage.com
 * @copyright Copyright (c) 2016 - 2019 Nicepage
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace NP\Utility;

defined('_JEXEC') or die;

use \JFactory, \JURI;

class Utility
{
    /**
     * Defines site is ssl
     *
     * @return bool
     */
    public static function isSSL()
    {
        $isSSL = false;

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $_SERVER['HTTPS'] = 'on';
        }

        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS'])) {
                $isSSL = true;
            }
            if ('1' == $_SERVER['HTTPS']) {
                $isSSL = true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            $isSSL = true;
        }
        return $isSSL;
    }

    /**
     * Defines site is localhost
     *
     * @return bool
     */
    public static function isLocalhost()
    {
        $whitelist = array(
            // IPv4 address
            '127.0.0.1',
            // IPv6 address
            '::1'
        );

        if (filter_has_var(INPUT_SERVER, 'REMOTE_ADDR')) {
            $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        } else if (filter_has_var(INPUT_ENV, 'REMOTE_ADDR')) {
            $ip = filter_input(INPUT_ENV, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        } else {
            $ip = null;
        }
        return $ip && in_array($ip, $whitelist);
    }

    /**
     * Defines site is https and localhost
     *
     * @return bool
     */
    public static function siteIsSecureAndLocalhost() {
        return self::isSSL() && self::isLocalhost();
    }

    /**
     * Option value to bytes value
     *
     * @param string $str Option value
     *
     * @return int
     */
    public static function toBytes($str)
    {
        $str = strtolower(trim($str));
        $size = intval($str);
        if ($str && strlen($size) !== strlen($str)) {
            $unit = $str[strlen($str) - 1];
            $size = substr($str, 0, -1);
            switch ($unit) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
            }
        }
        return $size;
    }

    /**
     * Get max request size
     *
     * @return mixed
     */
    public static function getMaxRequestSize()
    {
        $postSize = self::toBytes(ini_get('post_max_size'));
        $uploadSize = self::toBytes(ini_get('upload_max_filesize'));
        $memorySize = self::toBytes(ini_get('memory_limit'));

        return min($postSize, $uploadSize, $memorySize);
    }

    /**
     * Get name of default template style
     *
     * @return string
     */
    public static function getActiveTemplate()
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__template_styles');
        $query->where('client_id = 0');
        $query->where('home=\'1\'');
        $db->setQuery($query);
        $ret = $db->loadObject();
        return $ret ? $ret->template : '';
    }

    /**
     * Include froala editor styles
     */
    public static function includeFroalaStyles() {
        $name = array('n', 'i', 'c', 'e', 'p', 'a', 'g', 'e');
        $paramValue = JFactory::getApplication()->getTemplate(true)->params->get(implode('', $name) . 'theme', '0');
        $isThirdPartyTheme = $paramValue == '0' ? true : false;
        if ($isThirdPartyTheme) {
            $froalaLink = '<link href="' . JURI::root(true) . '/components/com_nicepage/assets/css/froala.css" rel="stylesheet">';
            JFactory::getDocument()->addCustomTag($froalaLink);
        }
    }

    /**
     * Quick sort function
     *
     * @param array  $array  Input array data
     * @param string $filter Filter function
     *
     * @return mixed
     */
    public static function quickSort($array, $filter = '') {
        if (count($array) < 2) {
            return $array;
        }
        $left = 0;
        $right = count($array) - 1;
        self::innerSort($array, $left, $right, $filter);
        return $array;
    }

    /**
     * Recursive sort function
     *
     * @param array  $array  Input array data
     * @param int    $left   Start index
     * @param int    $right  Last index
     * @param string $filter Filter function
     */
    public static function innerSort(&$array, $left, $right, $filter)
    {
        //Создаем копии пришедших переменных, с которыми будем манипулировать в дальнейшем.
        $l = $left;
        $r = $right;

        //Вычисляем 'центр', на который будем опираться. Берем значение ~центральной ячейки массива.
        $center = self::applyFilter($array[(int)($left + $right) / 2], $filter);
        //Цикл, начинающий саму сортировку
        do {
            //Ищем значения больше 'центра'
            while (self::applyFilter($array[$r], $filter) > $center) {
                $r--;
            }
            //Ищем значения меньше 'центра'
            while (self::applyFilter($array[$l], $filter) < $center) {
                $l++;
            }
            //После прохода циклов проверяем счетчики циклов
            if ($l <= $r) {
                //И если условие true, то меняем ячейки друг с другом.
                list($array[$r], $array[$l]) = array($array[$l], $array[$r]);
                //И переводим счетчики на следующий элементы
                $l++;
                $r--;
            }
            //Повторяем цикл, если true
        } while ($l <= $r);

        if ($r > $left) {
            //Если условие true, совершаем рекурсию
            //Передаем массив, исходное начало и текущий конец
            self::innerSort($array, $left, $r, $filter);
        }

        if ($l < $right) {
            //Если условие true, совершаем рекурсию
            //Передаем массив, текущие начало и конец
            self::innerSort($array, $l, $right, $filter);
        }
        //Сортировка завершена
    }

    /**
     * Apply filter function
     *
     * @param int    $value  Array value
     * @param string $filter Filter function
     *
     * @return mixed
     */
    public static function applyFilter($value, $filter)
    {
        if (!$filter) {
            return $value;
        }
        return call_user_func('self::' . $filter, $value);
    }

    /**
     * Filter function
     *
     * @param string $file File path
     *
     * @return false|float|int
     */
    public function getFileModifiedTime($file)
    {
        $time = @filemtime($file);
        if (!$time) {
            $time = round(microtime(true));
        }
        return $time;
    }
}