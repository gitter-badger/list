<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 18.06.15
 * Time: 21:27
 * Project: list
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\BigList;


class SQLiteList implements iList
{
    private $handle;

    /**
     * @param mixed $handle
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    public function __construct($listName)
    {

    }

    public function open($listName)
    {

    }

    public function close()
    {

    }

    public function deleteList()
    {

    }

    public function delete($level = '/')
    {

    }

    public function findByValue($value, $level = '/')
    {

    }

    public function findByKey($key, $level = '/')
    {

    }

    public function findLevel($level, $parentLevel = '/')
    {

    }

    public function write($data, $level = '/')
    {

    }

    public function read($level = '/')
    {

    }

    public function sync()
    {

    }
}