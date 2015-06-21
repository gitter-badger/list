<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 18.06.15
 * Time: 21:14
 * Project: list
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\BigList;

use \PHPUnit_Framework_Testcase;
use \ReflectionClass;

class SQLiteListTest extends PHPUnit_Framework_TestCase
{
    public static $name;

    public static function setUpBeforeClass()
    {
        self::$name = 'unit_test';
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionMethod
     */
    protected static function getMethod($name, $className = 'bpteam\BigList\SQLiteList')
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionProperty
     */
    protected static function getProperty($name, $className = 'bpteam\BigList\SQLiteList')
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }

    public function testOpen()
    {
        $list = new SQLiteList();
        $list->open(self::$name);
        $fileName = $this->getMethod('getFileName')->invoke($list);
        $this->assertFileExists($fileName);
        $list->close();
    }

    public function testDeleteList()
    {
        $list = new SQLiteList();
        $list->open(self::$name);
        $fileName = $this->getMethod('getFileName')->invoke($list);
        $this->assertFileExists($fileName);
        $list->deleteList();
        $this->assertFileNotExists($fileName);
        $list->close();
    }

    public function testReadWrite()
    {
        $list = new SQLiteList();
        $list->open(self::$name);
        $list->write(['msg' => 'hello']);
        $hello = $list->read('msg');
        $this->assertEquals('hello', $hello);
        $list->write('test', 'demo');
        $test = $list->read('demo');
        $this->assertEquals('test', $test);
        $list->write(['lvl'=>['ku']], 'test');
        $ku = $list->read('lvl');
        $this->assertEquals('ku', current($ku));
        $list->write('asdf', 'test2', 'lvl');
        $test2 = $list->read('test2');
        $this->assertEquals('asdf', $test2);
        $list->close();
    }
    public function testDelete()
    {
        $list = new SQLiteList();
        $list->open(self::$name);
        $list->write(['delete_me' => 'test3'], 'demo3');
        $testVal = $list->read('delete_me');
        $this->assertEquals('test3', $testVal);
        $list->delete('delete_me');
        $testVal = $list->read('delete_me');
        $this->assertFalse((bool)$testVal);
    }
}