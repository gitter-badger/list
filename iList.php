<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 18.06.15
 * Time: 20:40
 * Project: list
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\BigList;

interface iList
{

    public function __construct($path);
    public function open($name);
    public function close();
    public function deleteList();
    public function delete($level);
    public function &find($level, &$levelData = false);
    public function write($value, $key, $level);
    public function read($level = '/');

}