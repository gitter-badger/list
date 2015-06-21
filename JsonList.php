<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 07.06.15
 * Time: 18:06
 * Project: big-list
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\BigList;

use \bpteam\File\LocalFile;

class JsonList extends ArrayList implements iList
{

    /**
     * @var \bpteam\File\LocalFile $handle
     */
    private $handle;

    private $ext = 'json';


    /**
     * @param mixed $handle
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function __construct($path = null)
    {
        parent::__construct($path);
        $this->path = $path ?: (__DIR__ . '/data');
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function open($name)
    {
        $this->name = $name;
        $this->setHandle(new LocalFile());
        $this->handle->open($this->getFileName());
        $this->sync();
    }

    protected function getFileName()
    {
        return $this->path . '/' . $this->name . '.' . $this->ext;
    }

    public function close()
    {
        return $this->handle->close();
    }

    public function deleteList()
    {
        parent::deleteList();
        return $this->handle->delete();
    }

    public function delete($level)
    {
        $this->sync(false);
        parent::delete($level);
        $this->sync();
    }

    public function write($value, $key = null, $level = self::ROOT)
    {
        $this->sync(false);
        parent::write($value, $key, $level);
        return $this->sync();
    }

    public function read($level = self::ROOT)
    {
        $this->sync();
        return $this->find($level);
    }

    protected function sync($free = true)
    {
        $this->handle->lock();
        $oldList = json_decode($this->handle->read(), true);
        $currentList = $this->list;
        if (is_array($currentList) && is_array($oldList)) {
            $this->list[self::ROOT] = $currentList[self::ROOT] + $oldList[self::ROOT];
        }
        $json = json_encode($this->list);
        $this->handle->clear();
        $writeResult = $this->handle->write($json);
        if ($free) {
            $this->handle->free();
        }

        return $writeResult;
    }
}