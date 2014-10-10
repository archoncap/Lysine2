<?php
namespace Test;

use \Lysine\DataMapper\Data;

class DataTest extends \PHPUnit_Framework_TestCase {
    protected $class = '\Test\Mock\DataMapper\Data';

    protected function setAttributes(array $attributes) {
        $class = $this->class;
        $class::getMapper()->setAttributes($attributes);
    }

    public function testConstruct() {
        $class = $this->class;

        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'default' => 'foo'),
            'bar' => array('type' => 'string', 'default' => 'bar', 'allow_null' => true),
        ));

        $data = new $class;

        $this->assertTrue($data->isFresh());
        $this->assertTrue($data->isDirty());
        $this->assertEquals($data->foo, 'foo');
        $this->assertNull($data->bar);

        $data = new $class(array(
            'bar' => 'bar'
        ));

        $this->assertEquals($data->bar, 'bar');

        $data = new $class(array(), array('fresh' => false));

        $this->assertFalse($data->isFresh());
        $this->assertFalse($data->isDirty());
        $this->assertNull($data->foo);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /primary key/
     */
    public function testBadConstruct() {
        $this->setAttributes(array());
    }

    public function testSetStrict() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'strict' => true),
        ));

        $data = new $this->class;

        $data->merge(array('foo' => 'foo'));
        $this->assertFalse($data->isDirty('foo'));

        $data->set('foo', 'foo', array('strict' => false));
        $this->assertFalse($data->isDirty('foo'));

        $data->set('foo', 'foo', array('strict' => true));
        $this->assertTrue($data->isDirty('foo'));

        $data->foo = 'bar';
        $this->assertEquals($data->foo, 'bar');
    }

    public function testSetRefuseUpdate() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'refuse_update' => true),
        ));

        $class = $this->class;

        $data = new $class;
        $data->foo = 'foo';

        $this->assertEquals($data->foo, 'foo');

        $data = new $class(array('foo' => 'foo'), array('fresh' => false));

        // test force set
        $data->set('foo', 'bar', array('force' => true));
        $this->assertEquals($data->foo, 'bar');

        $this->setExpectedExceptionRegExp('\RuntimeException', '/refuse update/');
        $data->foo = 'foo';
    }

    public function testSetNull() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'allow_null' => true),
            'bar' => array('type' => 'string'),
        ));

        $data = new $this->class;

        $data->foo = null;

        $this->setExpectedExceptionRegExp('\UnexpectedValueException', '/not allow null/');
        $data->bar = null;
    }

    public function testSetSame() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'allow_null' => true),
            'bar' => array('type' => 'string'),
        ));

        $class = $this->class;
        $data = new $class(array('bar' => 'bar'), array('fresh' => false));

        $this->assertFalse($data->isDirty());

        $data->foo = null;
        $this->assertFalse($data->isDirty('foo'));

        $data->bar = 'bar';
        $this->assertFalse($data->isDirty('bar'));
    }

    public function testSetEmptyString() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'allow_null' => true),
            'bar' => array('type' => 'integer'),
        ));

        $data = new $this->class;

        $data->foo = '';
        $this->assertNull($data->foo);

        $this->setExpectedExceptionRegExp('\UnexpectedValueException', '/not allow null/');
        $data->bar = '';
    }

    public function testSetUndefined() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
        ));

        $data = new $this->class;

        $data->set('bar', 'bar', array('strict' => false));
        $data->merge(array('bar' => 'bar'));

        $this->setExpectedExceptionRegExp('\UnexpectedValueException', '/undefined property/i');
        $data->bar = 'bar';
    }

    public function testGetUndefined() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
        ));

        $data = new $this->class;

        $this->setExpectedExceptionRegExp('\UnexpectedValueException', '/undefined property/i');
        $data->foo;
    }

    public function testPick() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'protected' => true),
            'bar' => array('type' => 'string'),
        ));

        $class = $this->class;

        $data = new $class(array(
            'foo' => 'foo',
            'bar' => 'bar',
        ), array('fresh' => false));

        $values = $data->pick();

        $this->assertFalse(array_key_exists('id', $values));
        $this->assertFalse(array_key_exists('foo', $values));
        $this->assertTrue(array_key_exists('bar', $values));

        $values = $data->pick('foo', 'bar', 'baz');

        $this->assertTrue(array_key_exists('foo', $values));
        $this->assertTrue(array_key_exists('bar', $values));
        $this->assertFalse(array_key_exists('baz', $values));
    }

    public function testGetID() {
        $class = $this->class;

        $this->setAttributes(array(
            'foo' => array('type' => 'string', 'primary_key' => true),
        ));

        $data = new $class(array('foo' => 'foo'));
        $this->assertEquals($data->id(), 'foo');

        $this->setAttributes(array(
            'foo' => array('type' => 'string', 'primary_key' => true),
            'bar' => array('type' => 'string', 'primary_key' => true),
        ));

        $data = new $class(array('foo' => 'foo', 'bar' => 'bar'));
        $this->assertSame($data->id(), array('foo' => 'foo', 'bar' => 'bar'));
    }

    public function testGetOptions() {
        $foo_options = \Test\Mock\DataMapper\FooData::getOptions();

        $this->assertEquals($foo_options['service'], 'foo.service');
        $this->assertEquals($foo_options['collection'], 'foo.collection');
        $this->assertEquals(count($foo_options['attributes']), 2);
        $this->assertArrayHasKey('readonly', $foo_options);
        $this->assertArrayHasKey('strict', $foo_options);

        $bar_options = \Test\Mock\DataMapper\BarData::getOptions();

        $this->assertEquals($bar_options['service'], 'bar.service');
        $this->assertEquals($bar_options['collection'], 'bar.collection');
        $this->assertEquals(count($bar_options['attributes']), 3);
    }
}

namespace Test\Mock\DataMapper;

class FooData extends \Lysine\DataMapper\Data {
    static protected $service = 'foo.service';
    static protected $collection = 'foo.collection';
    static protected $attributes = array(
        'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
        'foo' => array('type' => 'string'),
    );
}

class BarData extends FooData {
    static protected $service = 'bar.service';
    static protected $collection = 'bar.collection';
    static protected $attributes = array(
        'bar' => array('type' => 'string'),
    );
}
