<?php

namespace Lazysess;

class Merge
{
    private $cur = array();
    private $new = array();
    private $old = array();

    private $prev_add = array();
    private $prev_sub = array();

    const COLLISION_VALUE_VOID = '__VOID';
    const COLLISION_VALUE_UNKNOWN = '__UNKNOWN';
    private $collisions = array();

    public function __construct($cur, $new, $old = null)
    {
        $this->cur = $cur;
        $this->new = $new;
        $this->old = $old === null ? $cur : $old;
    }

    public function getDiffAdd()
    {
        $this->prev_add = array();
        return $this->getDiffAddRecursive($this->old, $this->new, $this->prev_add);
    }

    private function getDiffAddRecursive($arr1, $arr2, &$prev)
    {
        $result = array();
        foreach ($arr2 as $key => $value) {
            if (!array_key_exists($key, $arr1)) {
                $result[$key] = $value;
            } elseif ($arr1[$key] !== $value) {
                if (is_array($value) && is_array($arr1[$key])) {
                    $prev[$key] = array();
                    $result[$key] = $this->getDiffAddRecursive($arr1[$key], $value, $prev[$key]);
                } else {
                    $prev[$key] = $arr1[$key];
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    public function getDiffSub()
    {
        $this->prev_sub = array();
        return $this->getDiffSubRecursive($this->old, $this->new, $this->prev_sub);
    }

    private function getDiffSubRecursive($arr1, $arr2, &$prev)
    {
        $result = array();
        foreach ($arr1 as $key => $value) {
            if (!array_key_exists($key, $arr2)) {
                $prev[$key] = $arr1[$key];
                $result[$key] = null;
            } elseif (is_array($value) && is_array($arr2[$key]) && $arr2[$key] !== $value) {
                $prev[$key] = array();
                $diff = $this->getDiffSubRecursive($value, $arr2[$key], $prev[$key]);
                if ($diff === null || !empty($diff)) {
                    $result[$key] = $diff;
                } else {
                    unset($prev[$key]);
                }
            }
        }
        return $result;
    }

    public function getMergeAdd()
    {
        return $this->getMergeAddRecursive($this->cur, $this->getDiffAdd(), $this->prev_add);
    }

    private function getMergeAddRecursive($arr1, $arr2, $prev, $path = '')
    {
        foreach ($arr2 as $key => $value) {
            if (!array_key_exists($key, $arr1)) {
                if (array_key_exists($key, $prev)) {
                    $this->addCollision($path, $key, self::COLLISION_VALUE_VOID, $value, $prev[$key]);
                }
            } elseif ($arr1[$key] !== $value) {
                if (is_array($value) && is_array($arr1[$key])) {
                    $value = $this->getMergeAddRecursive($arr1[$key], $value, $prev[$key], $path . '.' . $key);
                } elseif ($arr1[$key] !== $prev[$key]) {
                    $this->addCollision($path, $key, $arr1[$key], $value, $prev[$key]);
                }
            } elseif (array_key_exists($key, $arr1) && !array_key_exists($key, $prev)) {
                $this->addCollision($path, $key, $arr1[$key], $value, self::COLLISION_VALUE_VOID);
            } elseif (!array_key_exists($key, $arr1) && array_key_exists($key, $prev)) {
                $this->addCollision($path, $key, self::COLLISION_VALUE_VOID, $value, $prev[$key]);
            } elseif ($arr1[$key] !== $prev[$key]) {
                $this->addCollision($path, $key, $arr1[$key], $value, $prev[$key]);
            }
            $arr1[$key] = $value;
        }
        return $arr1;
    }

    public function getMergeSub()
    {
        return $this->getMergeSubRecursive($this->cur, $this->getDiffSub(), $this->prev_sub);
    }

    private function getMergeSubRecursive($arr1, $arr2, $prev, $path = '')
    {
        foreach ($arr2 as $key => $value) {
            if (is_array($value)) {
                $arr1[$key] = $this->getMergeSubRecursive($arr1[$key], $value, $prev[$key], $path . '.' . $key);
            } else {
                if ($arr1[$key] !== $prev[$key]) {
                    $this->addCollision($path, $key, $arr1[$key], self::COLLISION_VALUE_VOID, $prev[$key]);
                }
                unset($arr1[$key]);
            }
        }
        return $arr1;
    }

    public function getMerge()
    {
        $result = $this->getMergeAddRecursive($this->cur, $this->getDiffAdd(), $this->prev_add);
        $result = $this->getMergeSubRecursive($result, $this->getDiffSub(), $this->prev_sub);
        return $result;
    }

    private function addCollision($path, $key, $cur_value, $new_value, $old_value)
    {
        $this->collisions[] = array(
            'path' => $path,
            'key' => $key,
            'cur_value' => $cur_value,
            'new_value' => $new_value,
            'old_value' => $old_value,
            'page' => $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'],
        );
    }

    public function getCollisions()
    {
        return $this->collisions;
    }

}
