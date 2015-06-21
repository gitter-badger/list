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

use \SQLite3;
use \bpteam\File\LocalFile;

class SQLiteList extends ArrayList implements iList
{
    /**
     * @var SQLite3
     */
    private $handle;

    private $table = 'foo';

    private $ext = 'db';

    /**
     * @param mixed $handle
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    public function __construct($path = false)
    {
        parent::__construct($path);
        $this->path = $path ?: (__DIR__ . '/data');
    }

    public function __destruct()
    {
        $this->close();
    }

    public function open($listName)
    {
        $this->close();
        $this->name = $listName;
        $this->setHandle(new SQLite3($this->getFileName()));
        if (!$this->handle->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='{$this->table}';")) {
            $this->handle->exec("CREATE TABLE {$this->table} (key_row TEXT, parent_row TEXT, value_row TEXT)");
            $this->handle->exec("CREATE INDEX ki ON {$this->table}(key_row)");
            $this->handle->exec("CREATE INDEX pi ON {$this->table}(parent_row)");
            $this->handle->exec("CREATE UNIQUE INDEX kpi ON {$this->table}(key_row,parent_row)");
        }
    }

    protected function getFileName()
    {
        return $this->path . '/' . $this->name . '.' . $this->ext;
    }

    public function close()
    {
        if ($this->handle) {
            $this->handle->close();
        }
    }

    public function deleteList()
    {
        parent::deleteList();
        $this->close();
        return (new LocalFile($this->getFileName()))->delete();
    }

    public function delete($level = self::ROOT)
    {
        $stmt = $this->handle->prepare("SELECT * FROM {$this->table} WHERE parent_row = :pr");
        $stmt->bindValue(':pr', $level, SQLITE3_TEXT);
        $result = $stmt->execute();
        while ($data = $result->fetchArray()) {
            $this->delete($data['key_row']);
        }
        $result->finalize();
        $stmt->close();
        $stmt = $this->handle->prepare("DELETE FROM {$this->table} WHERE key_row = :kr");
        $stmt->bindValue(':kr', $level, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param string     $level
     * @param bool|false $levelData
     * @return null|array
     */
    public function &find($level, &$levelData = false)
    {
        $stmt = $this->handle->prepare("SELECT COUNT(*) AS 'total', key_row, parent_row, value_row FROM {$this->table} WHERE key_row = :kr");
        $stmt->bindValue(':kr', $level, SQLITE3_TEXT);
        $result = $stmt->execute();
        $hasChildren = $this->hasChildren($level);
        $list = [];
        while (($data = $result->fetchArray()) && $data['total']) {
            if ($data['total'] === 1 && !$hasChildren) {
                $list = $data['value_row'];
            } else {
                $list[] = $data['value_row'];
            }
        }
        if ($hasChildren) {
            $keyStmt = $this->handle->prepare("SELECT key_row FROM {$this->table} WHERE parent_row = :pr");
            $keyStmt->bindValue(':pr', $level, SQLITE3_TEXT);
            $keyResult = $keyStmt->execute();
            while ($data = $keyResult->fetchArray()) {
                $children = $this->find($data['key_row']);
                if (is_array($children)) {
                    $list = $list + $children;
                } else {
                    $list[] = $children;
                }
            }
            $keyResult->finalize();
            $keyStmt->close();
        }
        $result->finalize();
        $stmt->close();
        if (isset($list)) {
            return $list;
        } else {
            $null = null;
            return $null;
        }
    }

    public function write($value, $key = null, $level = self::ROOT)
    {
        if ($key === null) {
            $list = $this->find($level);
            if (is_array($list)) {
                array_push($list, $value);
                end($list);
                $key = key($list);
            } else {
                $key = 0;
            }

        }
        if (is_array($value)) {
            foreach ($value as $keyValue => $valueValue) {
                $this->write($valueValue, $keyValue, $key);
            }
        } else {
            $stmt = $this->handle->prepare("INSERT INTO {$this->table} VALUES (:kr, :pr, :vr)");
            $stmt->bindValue(':pr', $level, SQLITE3_TEXT);
            $stmt->bindValue(':kr', $key, SQLITE3_TEXT);
            $stmt->bindValue(':vr', $value, SQLITE3_TEXT);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function read($level = self::ROOT)
    {
        return $this->find($level);
    }

    protected function hasChildren($level = self::ROOT)
    {
        $stmt = $this->handle->prepare("SELECT COUNT(*) AS 'total' FROM {$this->table} WHERE parent_row = :pr");
        $stmt->bindValue(':pr', $level, SQLITE3_TEXT);
        $result = $stmt->execute();
        $count = $result->fetchArray();
        $result->finalize();
        $stmt->close();
        return $count['total'];
    }
}