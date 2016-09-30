<?php
namespace Tests;

use mmghv\DependencyPocket;

class DependencyPocketTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pocket = new DependencyPocket;
    }

    public function testHasReturnsFalseIfDependencyNotExist()
    {
        $actual = $this->pocket->has('dep');

        $this->assertFalse($actual, 'should return false');
    }

    public function testHasReturnsTrueIfDependencyExists()
    {
        $this->pocket->define('dep');
        $actual = $this->pocket->has('dep');

        $this->assertTrue($actual, 'should return true');
    }

    public function testHasReturnsFalseIfDependencyExistsWhenCheckingForValue()
    {
        $this->pocket->define('dep');
        $actual = $this->pocket->has('dep', true);

        $this->assertFalse($actual, 'should return false');
    }

    public function testHasAlwaysReturnsTrueIfDependencyExistsAndHasValue()
    {
        $this->pocket->define('dep')
                     ->set('dep', 'val');

        $actual1 = $this->pocket->has('dep', false);
        $actual2 = $this->pocket->has('dep', true);

        $this->assertTrue($actual1, 'should return true');
        $this->assertTrue($actual2, 'should return true');
    }

    public function testDependencyNamesIsCaseSenstive()
    {
        $this->pocket->define('dep')
                     ->define('DEP');

        $actual1 = $this->pocket->has('dep');
        $actual2 = $this->pocket->has('DEP');
        $actual3 = $this->pocket->has('Dep');

        $this->assertTrue($actual1, 'should return true');
        $this->assertTrue($actual2, 'should return true');
        $this->assertFalse($actual3, 'should return false');
    }

    public function testDefineDependenciesAsArrayWorks()
    {
        $this->pocket->define([
            'dep1',
            'dep2' => '',
            'dep3' => 'integer',
            'dep4' => 'array',
            'dep5' => 'object',
            'dep6' => 'Illuminate\Database\Eloquent\Model',
        ]);

        $this->assertTrue($this->pocket->has('dep1'), 'should has dependency');
        $this->assertTrue($this->pocket->has('dep2'), 'should has dependency');
        $this->assertTrue($this->pocket->has('dep3'), 'should has dependency');
        $this->assertTrue($this->pocket->has('dep4'), 'should has dependency');
        $this->assertTrue($this->pocket->has('dep5'), 'should has dependency');
        $this->assertTrue($this->pocket->has('dep6'), 'should has dependency');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDefineSquawksIfNameIsEmpty()
    {
        $this->pocket->define('', 'boolean');
    }

    /**
     * @expectedException \Exception
     */
    public function testDefineSquawksIfAlreadyExists()
    {
        $this->pocket->define('dep', 'integer')
                     ->define('dep', 'string');
    }

    public function testSetWorks()
    {
        $this->pocket->define('dep')
                     ->set('dep', 'some value');

        $actual = $this->pocket->get('dep');

        $this->assertEquals('some value', $actual);
    }

    /**
     * @expectedException \Exception
     */
    public function testSetSquawksIfNotFound()
    {
        $this->pocket->set('dep', 'value');
    }

    /**
     * @dataProvider provideInvalidTypes
     * @expectedException \InvalidArgumentException
     */
    public function testSetSquawksIfNotTheRequiredType($type, $value)
    {
        $this->pocket->define('dep', $type)
                     ->set('dep', $value);
    }

    /**
     * @dataProvider provideValidTypes
     */
    public function testSetWorksIfItsTheRequiredType($type, $value)
    {
        $this->pocket->define('dep', $type)
                     ->set('dep', $value);
    }

    /**
     * @dataProvider provideValidTypes
     */
    public function testSetWorksWithAnyTypeIfNotSpecified($type, $value)
    {
        $this->pocket->define('dep')
                     ->set('dep', $value);
    }

    public function provideInvalidTypes()
    {
        return [
            ['null', ''],
            ['integer', 'some string'],
            ['string', 12],
            ['array', new \stdClass],
            ['object', [1, 2]],
            ['Illuminate\Database\Eloquent\Model', new \stdClass],
            ['closure', 'should be closure{}'],
        ];
    }

    public function provideValidTypes()
    {
        $model = $this->getMock('\Illuminate\Database\Eloquent\Model');

        return [
            ['null', null],
            ['integer', 12],
            ['string', ''],
            ['string', 'some string'],
            ['string', null],
            ['array', [1, '2']],
            ['object', new \stdClass],
            ['object', $model],
            ['object', null],
            ['Illuminate\Database\Eloquent\Model', $model],
            [get_class($model), $model],
            [$model, $model],
            ['closure', function ($a) {
                return $a;
            }],
        ];
    }

    public function testDefineSetGetDependenciesAsArrayWorks()
    {
        $depsTypes = [
            'dep1' => 'integer',
            'dep2' => 'string',
            'dep3' => 'array',
            'dep4' => 'object'
        ];

        $depsValues = [
            'dep1' => 12,
            'dep2' => 'some string',
            'dep3' => [12, 'sss'],
            'dep4' => new \stdClass
        ];

        $this->pocket->define($depsTypes)
                     ->set($depsValues);

        $all = $this->pocket->get();

        $this->assertSame($depsValues, $all, 'message');

        unset($depsValues['dep3']);
        $partial = $this->pocket->get(array_keys($depsValues));

        $this->assertSame($depsValues, $partial, 'message');
    }

    public function testGetTypeWorks()
    {
        $deps = [
            'dep1' => '',
            'dep2' => null,
            'dep3' => 'null',
            'dep4' => 'integer',
            'dep5' => 'string',
            'dep6' => 'array',
            'dep7' => 'object',
            'dep8' => 'Illuminate\Database\Eloquent\Model'
        ];

        $this->pocket->define($deps);

        foreach ($deps as $name => $type) {
            $this->assertSame((string) $type, $this->pocket->getType($name));
        }
    }

    public function testPropertyOverloadGetsDependencyIfFound()
    {
        $this->pocket->define('dep')
                     ->set('dep', 'value');

        $actual = $this->pocket->dep;

        $this->assertEquals('value', $actual);
    }

    /**
     * @expectedException \Exception
     */
    public function testPropertyOverloadSquaksIfDependencyNotFound()
    {
        $actual = $this->pocket->dep;
    }
}
