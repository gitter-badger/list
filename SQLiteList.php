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
    protected function setHandle($handle)
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
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
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
     * @param string            $level
     * @param bool|string $levelData
     * @return array|NULL
     */
    public function &find($level, &$levelData = false)
    {
        if ($levelData) {
            $stmt = $this->handle->prepare("SELECT * FROM {$this->table} WHERE key_row = :kr AND parent_row = :pr");
            $stmt->bindValue(':kr', $level, SQLITE3_TEXT);
            $stmt->bindValue(':pr', $levelData, SQLITE3_TEXT);
        } else {
            $stmt = $this->handle->prepare("SELECT * FROM {$this->table} WHERE key_row = :kr");
            $stmt->bindValue(':kr', $level, SQLITE3_TEXT);
        }
        $result = $stmt->execute();
        while (($data = $result->fetchArray(SQLITE3_ASSOC)) && $data['value_row'] !== NULL) {
            return $data['value_row'];
        }
        if ($this->hasChildren($level)) {
            $list = $this->getChildren($level);
        }
        $result->finalize();
        $stmt->close();
        if (isset($list)) {
            return $list;
        } else {
            $null = NULL;
            return $null;
        }
    }

    protected function getChildren($level)
    {
        $keyStmt = $this->handle->prepare("SELECT key_row FROM {$this->table} WHERE parent_row = :pr");
        $keyStmt->bindValue(':pr', $level, SQLITE3_TEXT);
        $keyResult = $keyStmt->execute();
        $data = $keyResult->fetchArray(SQLITE3_ASSOC);
        $list = [];
        do {
            $children[$data['key_row']] = $this->find($data['key_row'], $level);
            if (is_array($children)) {
                $list = $list + $children;
            } else {
                $list[$data['key_row']] = $children;
            }
            $data = $keyResult->fetchArray(SQLITE3_ASSOC);
        } while ($data);
        $keyResult->finalize();
        $keyStmt->close();
        return $list;
    }

    public function write($value, $key = NULL, $level = self::ROOT)
    {
        if ($key === NULL) {
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
            $this->addLevel($key, $level);
            foreach ($value as $keyValue => $valueValue) {
                $this->write($valueValue, $keyValue, $key);
            }
        } else {
            $stmt = $this->handle->prepare("INSERT OR REPLACE INTO {$this->table} VALUES (:kr, :pr, :vr)");
            $stmt->bindValue(':pr', $level, SQLITE3_TEXT);
            $stmt->bindValue(':kr', $key, SQLITE3_TEXT);
            $stmt->bindValue(':vr', $value, SQLITE3_TEXT);
            $stmt->execute();
            $stmt->close();
        }
    }

    protected function addLevel($level, $parent = self::ROOT)
    {

        $stmt = $this->handle->prepare("SELECT COUNT(*) AS 'total' FROM {$this->table} WHERE key_row = :kr AND parent_row = :pr");
        $stmt->bindValue(':pr', $parent, SQLITE3_TEXT);
        $stmt->bindValue(':kr', $level, SQLITE3_TEXT);
        $result = $stmt->execute();
        $count = $result->fetchArray(SQLITE3_ASSOC);
        $result->finalize();
        $stmt->close();
        if (!$count['total']) {
            $this->write(NULL, $level, $parent);
        }
    }

    protected function hasChildren($level = self::ROOT)
    {
        $stmt = $this->handle->prepare("SELECT COUNT(*) AS 'total' FROM {$this->table} WHERE parent_row = :pr");
        $stmt->bindValue(':pr', $level, SQLITE3_TEXT);
        $result = $stmt->execute();
        $count = $result->fetchArray(SQLITE3_ASSOC);
        $result->finalize();
        $stmt->close();
        return $count['total'];
    }
}