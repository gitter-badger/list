<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 21.06.15
 * Time: 22:59
 * Project: list
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\BigList;


abstract class ArrayList implements iList
{
    const ROOT = '/';

    protected $name;

    protected $path;

    protected $list = [self::ROOT=>[]];

    public function init()
    {
        $this->list = [self::ROOT=>[]];
    }

    public function __construct($path = null)
    {
        $this->init();
    }

    public function __destruct()
    {
        $this->close();
    }
    abstract public function open($name);
    abstract public function close();
    public function deleteList()
    {
        unset($this->list[self::ROOT]);
    }

    public function delete($level)
    {
        $list =& $this->find($level);
        if (!empty($list)) {
            $list = null;
        }
    }

    public function &find($level, &$levelData = false)
    {
        if ($levelData === false) {
            $levelData =& $this->list;
        }
        if (is_array($levelData)) {
            if (isset($levelData[$level])) {
                return $levelData[$level];
            } else {
                foreach ($levelData as &$subLevel) {
                    $result =& $this->find($level, $subLevel);
                    if (!empty($result)) {
                        return $result;
                    }
                }
            }
        }

        $null = null;
        return $null;
    }

    public function write($value, $key = null, $level = self::ROOT)
    {
        $list =& $this->find($level);
        if ($key === null && is_array($list)) {
            array_push($list, $value);
        } elseif(is_array($list)) {
            $list[$key] = $value;
        } else {
            $list = $value;
        }
    }

    public function read($level = '/')
    {

    }
}