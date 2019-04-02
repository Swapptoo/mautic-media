<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMediaBundle\Helper;

use Exception;
use stdClass;

/**
 * Class JSONHelper.
 */
class JSONHelper
{
    /**
     * @param        $string
     * @param string $fieldName
     * @param bool   $assoc
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function decodeArray($string, $fieldName = null, $assoc = false)
    {
        if (empty($string)) {
            return [];
        }
        $array = self::decode($string, $fieldName, $assoc);
        if (is_string($array)) {
            $array = self::decode($array, $fieldName, $assoc);
        }
        if (!is_array($array)) {
            throw new Exception('The field '.$fieldName.' is not a JSON array as expected.');
        }

        return $array;
    }

    /**
     * @param      $string
     * @param      $fieldName
     * @param bool $assoc
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function decode($string, $fieldName, $assoc = false)
    {
        $jsonError = false;
        $result    = json_decode($string, $assoc);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                $jsonError = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $jsonError = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $jsonError = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $jsonError = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $jsonError = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $jsonError = 'Unknown error';
                break;
        }
        if ($jsonError) {
            throw new Exception('JSON is invalid in field '.$fieldName.' JSON error: '.$jsonError);
        }

        return $result;
    }

    /**
     * @param        $string
     * @param string $fieldName
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function decodeObject($string, $fieldName = null)
    {
        if (empty($string)) {
            return new stdClass();
        }
        $object = self::decode($string, $fieldName);
        if (is_string($object)) {
            $object = self::decode($object, $fieldName);
        }
        if (!is_object($object)) {
            throw new Exception('The field '.$fieldName.' is not a JSON object as expected.');
        }

        return $object;
    }
}
