<?php
namespace Test\Storage\Database;

use \Lysine\Service\DB\Expr;

class SelectTest extends \PHPUnit_Framework_TestCase {
    protected function select($table) {
        return service('pgsql.local')->select($table);
    }

    public function testStandard() {
        $select = $this->select('mytable');
        $this->assertEquals((string)$select, 'SELECT * FROM "mytable"');

        $select->order('id desc');
        $this->assertEquals((string)$select, 'SELECT * FROM "mytable" ORDER BY id desc');

        $select->limit(10);
        $this->assertEquals((string)$select, 'SELECT * FROM "mytable" ORDER BY id desc LIMIT 10');

        $select->offset(10);
        $this->assertEquals((string)$select, 'SELECT * FROM "mytable" ORDER BY id desc LIMIT 10 OFFSET 10');

        $select->setCols('id', 'email');
        $this->assertEquals((string)$select, 'SELECT "id", "email" FROM "mytable" ORDER BY id desc LIMIT 10 OFFSET 10');

        $select->setCols(array('id', 'email'));
        $this->assertEquals((string)$select, 'SELECT "id", "email" FROM "mytable" ORDER BY id desc LIMIT 10 OFFSET 10');

        $select->setCols(new Expr('count(1)'));
        $this->assertEquals((string)$select, 'SELECT count(1) FROM "mytable" ORDER BY id desc LIMIT 10 OFFSET 10');

        $select->limit('a')->offset('b');
        $this->assertEquals((string)$select, 'SELECT count(1) FROM "mytable" ORDER BY id desc');
    }

    public function testWhere() {
        $select = $this->select('mytable');

        $select->where('name = ?', 'yangyi');
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (name = ?)');
        $this->assertEquals($params, array('yangyi'));

        $select->where('email = ? and active = 1', 'yangyi.cn.gz@gmail.com');
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (name = ?) AND (email = ? and active = 1)');
        $this->assertEquals($params, array('yangyi', 'yangyi.cn.gz@gmail.com'));

        $other_select = $this->select('other_table')->setCols('user_id')->where('other = ?', 'other');
        $select->whereIn('id', $other_select);
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (name = ?) AND (email = ? and active = 1) AND ("id" IN (SELECT "user_id" FROM "other_table" WHERE (other = ?)))');
        $this->assertEquals($params, array('yangyi', 'yangyi.cn.gz@gmail.com', 'other'));

        //////////////////////////////
        $select = $this->select('mytable');
        $select->where('email = ? and passwd = ?', 'yangyi.cn.gz@gmail.com', 'abc');
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (email = ? and passwd = ?)');
        $this->assertEquals($params, array('yangyi.cn.gz@gmail.com', 'abc'));

        //////////////////////////////
        $select = $this->select('mytable');
        $select->where('email = ? and passwd = ?', array('yangyi.cn.gz@gmail.com', 'abc'));
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE (email = ? and passwd = ?)');
        $this->assertEquals($params, array('yangyi.cn.gz@gmail.com', 'abc'));

        //////////////////////////////
        $select = $this->select('mytable');
        $select->whereIn('id', array(1, 2, 3));
        list($sql, $params) = $select->compile();

        $this->assertEquals($sql, 'SELECT * FROM "mytable" WHERE ("id" IN (1,2,3))');
    }

    public function testUpdateWithoutWhere() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->update(array('name' => 'yangyi'));
    }

    public function testUpdateWithLimit() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->where('id = 1')->limit(10)->update(array('name' => 'yangyi'));
    }

    public function testUpdateWithOffset() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->where('id = 1')->offset(10)->update(array('name' => 'yangyi'));
    }

    public function testUpdateWithGroupBy() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->where('id = 1')->group('email')->update(array('name' => 'yangyi'));
    }

    public function testDeleteWithoutWhere() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->delete();
    }

    public function testDeleteWithLimit() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->where('id = 1')->limit(10)->delete();
    }

    public function testDeleteWithOffset() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->where('id = 1')->offset(10)->delete();
    }

    public function testDeleteWithGroupBy() {
        $this->setExpectedException('\LogicException');
        $this->select('mytable')->where('id = 1')->group('email')->delete();
    }
}
