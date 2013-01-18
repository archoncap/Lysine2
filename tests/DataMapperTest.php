<?php
namespace Test;

use \Lysine\DataMapper\Data;

class DataMapperData extends \PHPUnit_Framework_TestCase {
    protected $class = '\Test\Mock\DataMapper\Data';

    protected function tearDown() {
        \Test\Mock\DataMapper\Meta::reset();
    }

    protected function setPropsMeta(array $props_meta) {
        \Test\Mock\DataMapper\Meta::reset();
        \Test\Mock\DataMapper\Data::setPropsMeta($props_meta);
    }

    public function testDefaultValue() {
        $this->setPropsMeta(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_increase' => true),
            'datetime1' => array('type' => 'datetime', 'default' => Data::CURRENT_DATETIME),
            'datetime2' => array('type' => 'datetime', 'allow_null' => true, 'default' => Data::CURRENT_DATETIME),
            'timestamp' => array('type' => 'datetime', 'default' => Data::CURRENT_TIMESTAMP),
            'date' => array('type' => 'datetime', 'default' => Data::CURRENT_DATE),
            'time' => array('type' => 'datetime', 'default' => Data::CURRENT_TIME),
            'p' => array('type' => 'integer', 'default' => 0),
        ));

        $class = $this->class;
        $d = new $class;

        $this->assertNull($d->id);

        $this->assertEquals($d->datetime1, strftime('%F %T'));
        $this->assertTrue(isset($d->datetime1));

        $this->assertEquals($d->datetime2, strftime('%F %T'));
        $this->assertFalse(isset($d->datetime2));

        $this->assertEquals($d->timestamp, time());

        $this->assertEquals($d->date, strftime('%F'));

        $this->assertEquals($d->time, strftime('%T'));

        $this->assertEquals($d->p, 0);
    }

    public function testSetProp() {
        $this->setPropsMeta(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_increase' => true),
            'email' => array('type' => 'string', 'refuse_update' => true, 'pattern' => "/^([a-z0-9_\-\.])+\@([a-z0-9_\-\.])+\.([a-z]{2,4})$/i"),
            'name' => array('type' => 'string', 'allow_null' => true),
        ));

        $class = $this->class;
        $data = new $class;
        $this->assertTrue($data->isFresh());

        $test = false;
        try {
            $data->email = 'yangyi';
        } catch (\Lysine\DataMapper\UnexpectedValueError $ex) {
            $test = true;
        }
        $this->assertTrue($test, '属性pattern检查没有生效');

        $data->name = '';
        $this->assertNull($data->name, '对string类型的属性赋值\'\'应该转换为null');

        $data->email = 'yangyi.cn.gz@gmail.com';
        $data->name = 'yangyi';
        $this->assertTrue($data->isDirty());

        $data->save();
        $this->assertFalse($data->isFresh());
        $this->assertFalse($data->isDirty());

        // 对属性设置同样的值，应该不会把属性标记为已修改
        $data->name = 'yangyi';
        $this->assertFalse($data->isDirty());
        $data->destroy();

        $data = new $class;
        try {
            $data->save();
            $this->fail('属性的not allow null没有生效');
        } catch (\Lysine\DataMapper\NullNotAllowedError $ex) {
        }

        //////////////////// refuse update ////////////////////
        $props = array(
            'id' => 1,
            'email' => 'yangyi.cn.gz@gmail.com',
            'name' => 'yangyi',
        );
        $data = new $class($props, $is_fresh = false);

        $test = false;
        try {
            $data->email = 'yangyi.cn.gz@gmail.com';
        } catch (\Lysine\DataMapper\RefuseUpdateError $ex) {
            $test = true;
        }
        $this->assertTrue($test, '属性的refuse update没有生效');

        $test = false;
        try {
            $data->id = 2;
        } catch (\Lysine\DataMapper\RefuseUpdateError $ex) {
            $test = true;
        }
        $this->assertTrue($test, '主键应该refuse update');

        //////////////////// null not allowed ////////////////////
        $data = new $class();

        $test = false;
        try {
            $data->save();
        } catch (\Lysine\DataMapper\NullNotAllowedError $ex) {
            $test = true;
        }
        $this->assertTrue($test);

        $test = false;
        $data->email = 'yangyi.cn.gz@gmail.com';
        try {
            $data->save();
        } catch (\Lysine\DataMapper\NullNotAllowedError $ex) {
            $test = true;
        }
        $this->assertFalse($test);

        $this->setPropsMeta(array(
            'id' => array('type' => 'integer', 'primary_key' => true),
        ));

        $class = $this->class;
        $data = new $class();

        $test = false;
        try {
            $data->save();
        } catch (\Lysine\DataMapper\NullNotAllowedError $ex) {
            $test = true;
        }
        $this->assertTrue($test);
    }

    public function testGetProp() {
        $this->setPropsMeta(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_increase' => true),
            'email' => array('type' => 'string', 'refuse_update' => true),
            'name' => array('type' => 'string', 'allow_null' => true),
        ));

        $class = $this->class;
        $data = new $class(array(
            'email' => 'yangyi.cn.gz@gmail.com',
            'name' => 'yangyi',
        ));

        $this->assertEquals($data->email, 'yangyi.cn.gz@gmail.com');
        $this->assertEquals($data->name, 'yangyi');

        $test = false;
        try {
            $data->passwd;
        } catch (\Lysine\DataMapper\UndefinedPropertyError $ex) {
            $test = true;
        }
        $this->assertTrue($test);
    }

    public function testStrictProp() {
        $this->setPropsMeta(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_increase' => true),
            'name' => array('type' => 'string', 'strict' => true),
            'address' => array('type' => 'string'),
        ));

        $class = $this->class;
        $data = new $class;

        $data->setProps(array(
            'name' => 'abc',
            'address' => 'def',
            'other' => 'xyz',
        ));
        $this->assertFalse(isset($data->name), 'setProps()没有忽略strict属性');
        $this->assertFalse(isset($data->other), 'setProps()没有忽略不存在的属性');
        $this->assertTrue(isset($data->address), 'setProps()应该可以修改非strict属性');

        $data->name = 'abc';
        $this->assertTrue(isset($data->name), 'strict属性应该允许->设置');
    }
}
