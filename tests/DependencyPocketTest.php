<?php
namespace Tests;

use mmghv\DependencyPocket;

class DependencyPocketTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pocket = new DependencyPocket;
    }

    public function testHasDependencyReturnsFalseIfNotExist()
    {
        $actual = $this->pocket->hasDependency('dep');

        $this->assertFalse($actual, 'should return false');
    }

    public function testHasDependencyReturnsTrueIfExists()
    {
        $this->pocket->addDependency('dep');
        $actual = $this->pocket->hasDependency('dep');

        $this->assertTrue($actual, 'should return true');
    }

    public function testHasDependencyReturnsFalseIfExistsWhenCheckingForValue()
    {
        $this->pocket->addDependency('dep');
        $actual = $this->pocket->hasDependency('dep', true);

        $this->assertFalse($actual, 'should return false');
    }

    public function testHasDependencyAlwaysReturnsTrueIfExistsAndHasValue()
    {
        $this->pocket->addDependency('dep')
                     ->setDependency('dep', 'val');

        $actual1 = $this->pocket->hasDependency('dep', false);
        $actual2 = $this->pocket->hasDependency('dep', true);

        $this->assertTrue($actual1, 'should return true');
        $this->assertTrue($actual2, 'should return true');
    }

    public function testDependencyNamesIsCaseSenstive()
    {
        $this->pocket->addDependency('dep')
                     ->addDependency('DEP');

        $actual1 = $this->pocket->hasDependency('dep');
        $actual2 = $this->pocket->hasDependency('DEP');
        $actual3 = $this->pocket->hasDependency('Dep');

        $this->assertTrue($actual1, 'should return true');
        $this->assertTrue($actual2, 'should return true');
        $this->assertFalse($actual3, 'should return false');
    }

    public function testAddDependencyWorksForPrimitiveTypesAndClassesAndNone()
    {
        $this->pocket->addDependency('dep1')
                     ->addDependency('dep2', '')
                     ->addDependency('dep3', 'integer')
                     ->addDependency('dep4', 'array')
                     ->addDependency('dep5', 'object')
                     ->addDependency('dep6', 'Illuminate\Database\Eloquent\Model');

        $this->assertTrue($this->pocket->hasDependency('dep1'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep2'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep3'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep4'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep5'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep6'), 'should have dependency');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddDependencySquawksIfNameIsEmpty()
    {
        $this->pocket->addDependency('', 'boolean');
    }

    /**
     * @expectedException \Exception
     */
    public function testAddDependencySquawksIfAlreadyExists()
    {
        $this->pocket->addDependency('dep', 'integer')
                     ->addDependency('dep', 'string');
    }

    public function testAddDependenciesAsArrayWorks()
    {
        $this->pocket->addDependencies([
            'dep1',
            'dep2' => '',
            'dep3' => 'integer',
            'dep4' => 'array',
            'dep5' => 'object',
            'dep6' => 'Illuminate\Database\Eloquent\Model',
        ]);

        $this->assertTrue($this->pocket->hasDependency('dep1'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep2'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep3'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep4'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep5'), 'should have dependency');
        $this->assertTrue($this->pocket->hasDependency('dep6'), 'should have dependency');
    }

    public function testSetDependencyWorks()
    {
        $this->pocket->addDependency('dep')
                     ->setDependency('dep', 'some value');

        $actual = $this->pocket->getDependency('dep');

        $this->assertEquals('some value', $actual);
    }

    /**
     * @expectedException \Exception
     */
    public function testSetDependencySquawksIfNotFound()
    {
        $this->pocket->setDependency('dep', 'value');
    }

    /**
     * @dataProvider provideInvalidTypes
     * @expectedException \InvalidArgumentException
     */
    public function testSetDependencySquawksIfNotTheRequiredType($type, $value)
    {
        $this->pocket->addDependency('dep', $type)
                     ->setDependency('dep', $value);
    }

    /**
     * @dataProvider provideValidTypes
     */
    public function testSetDependencyWorksIfItsTheRequiredType($type, $value)
    {
        $this->pocket->addDependency('dep', $type)
                     ->setDependency('dep', $value);
    }

    /**
     * @dataProvider provideValidTypes
     */
    public function testSetDependencyWorksWithAnyTypeIfNotSpecified($type, $value)
    {
        $this->pocket->addDependency('dep')
                     ->setDependency('dep', $value);
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

    public function testAddSetGetDependenciesAsArrayWorks()
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

        $this->pocket->addDependencies($depsTypes)
                     ->setDependencies($depsValues);

        $all = $this->pocket->getDependencies();

        $this->assertSame($depsValues, $all, 'message');

        unset($depsValues['dep3']);
        $partial = $this->pocket->getDependencies(array_keys($depsValues));

        $this->assertSame($depsValues, $partial, 'message');
    }

    public function testGetDependencyTypeWorks()
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

        $this->pocket->addDependencies($deps);

        foreach ($deps as $name => $type) {
            $this->assertSame((string) $type, $this->pocket->getDependencyType($name));
        }
    }

    public function testClosure()
    {
        $this->pocket->addDependency('c', 'closure')
            ->setDependency('c', function ($a) {
                return $a;
            });

        $c = $this->pocket->getDependency('c');
    }
}
