<?php

namespace Tests\Unit\TUF;

use App\Models\TufMetadata;
use App\TUF\EloquentModelStorage;
use Tests\TestCase;

class EloquentModelStorageTest extends TestCase
{
    public function testConstructorWritesColumnMetadataToInternalStorage()
    {
        $model  = $this->getModelMock(['root' => 'rootfoo']);
        $object = new EloquentModelStorage($model);

        $this->assertEquals('rootfoo', $this->getInternalStorageValue($object)['root']);
    }

    public function testConstructorIgnoresNonMetadataColumns()
    {
        $model  = $this->getModelMock(['foobar' => 'aaa']);
        $object = new EloquentModelStorage($model);

        $this->assertArrayNotHasKey('foobar', $this->getInternalStorageValue($object));
    }

    public function testReadReturnsStorageValueForExistingColumns()
    {
        $object = new EloquentModelStorage($this->getModelMock(['root' => 'foobar']));
        $this->assertEquals('foobar', $object->read('root'));
    }

    public function testReadReturnsNullForNonexistentColumns()
    {
        $object = new EloquentModelStorage($this->getModelMock([]));
        $this->assertNull($object->read('foobar'));
    }

    public function testWriteUpdatesGivenInternalStorageValue()
    {
        $object = new EloquentModelStorage($this->getModelMock(['root' => 'foo']));
        $object->write('root', 'bar');

        $this->assertEquals('bar', $this->getInternalStorageValue($object)['root']);
    }

    public function testWriteCreatesNewInternalStorageValue()
    {
        $object = new EloquentModelStorage($this->getModelMock(['root' => 'foo']));
        $object->write('targets', 'bar');

        $this->assertEquals('bar', $this->getInternalStorageValue($object)['targets']);
    }

    public function testDeleteRemovesRowFromInternalStorage()
    {
        $object = new EloquentModelStorage($this->getModelMock(['root' => 'foo']));
        $object->delete('root');

        $this->assertArrayNotHasKey('root', $this->getInternalStorageValue($object));
    }

    public function testPersistUpdatesTableObjectState()
    {
        $modelMock = $this->getModelMock(['root' => 'foo', 'targets' => 'Joomla', 'nonexistent' => 'value']);

        $modelMock
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $object = new EloquentModelStorage($modelMock);
        $this->assertTrue($object->persist());
    }

    protected function getModelMock(array $mockData)
    {
        $model = $this->getMockBuilder(TufMetadata::class)
            ->onlyMethods(['save'])
            ->getMock();

        // Write mock data to mock table
        foreach (EloquentModelStorage::METADATA_COLUMNS as $column) {
            $model->$column = (!empty($mockData[$column])) ? $mockData[$column] : null;
        }

        return $model;
    }

    protected function getInternalStorageValue($class)
    {
        $reflectionProperty = new \ReflectionProperty(EloquentModelStorage::class, 'container');

        return $reflectionProperty->getValue($class);
    }
}
