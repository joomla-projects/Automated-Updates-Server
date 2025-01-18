<?php

namespace Tests\Unit\TUF;

use App\TUF\EloquentModelStorage;
use App\TUF\ReleaseData;
use App\TUF\TufFetcher;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tuf\Metadata\StorageInterface;
use Tuf\Metadata\TargetsMetadata;

/**
 * Test class for DatabaseStorage
 *
 * @package     Joomla.UnitTest
 * @subpackage  Tuf
 * @since       5.1.0
 */
class TufFetcherTest extends TestCase
{
    public function testGetReleasesConvertsLegitResponse()
    {
        App::bind(StorageInterface::class, fn () => $this->getStorageMock([
            "Joomla_5.1.2-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.1.2 Release",
                    "version" => "5.1.2",
                    "stability" => "stable",
                ]
            ],
            "Joomla_5.2.1-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.2.1 Release",
                    "version" => "5.2.1",
                    "stability" => "stable",
                ]
            ]
        ]));

        $object = new TufFetcher();
        $result = $object->getReleases();

        $this->assertEquals([
            "5.1.2" => ReleaseData::from([
                "description" => "Joomla! 5.1.2 Release",
                "version" => "5.1.2",
                "stability" => "stable",
            ]),
            "5.2.1" => ReleaseData::from([
                "description" => "Joomla! 5.2.1 Release",
                "version" => "5.2.1",
                "stability" => "stable",
            ]),
        ], $result->toArray());
    }

    public function testGetReleasesThrowsExceptionOnEmptyTargetlist()
    {
        $this->expectExceptionMessage("Empty targetlist in metadata");

        App::bind(StorageInterface::class, fn () => $this->getStorageMock([]));

        $object = new TufFetcher();
        $object->getReleases();
    }

    public function testGetReleasesThrowsExceptionOnMissingCustom()
    {
        $this->expectExceptionMessage("Empty target custom attribute");

        App::bind(StorageInterface::class, fn () => $this->getStorageMock([
            "Joomla_5.1.2-Stable-Upgrade_Package.zip" => [
                "foobar" => "nocustom"
            ]
        ]));

        $object = new TufFetcher();
        $object->getReleases();
    }

    public function testGetLatestVersionForBranchReturnsNullForMissingBranch()
    {
        App::bind(StorageInterface::class, fn () => $this->getStorageMock([
            "Joomla_5.2.1-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.2.1 Release",
                    "version" => "5.2.1",
                    "stability" => "stable",
                ]
            ]
        ]));

        $object = new TufFetcher();
        $result = $object->getLatestVersionForBranch(6);

        $this->assertNull($result);
    }

    public function testGetLatestVersionForBranchChecksBranch()
    {
        App::bind(StorageInterface::class, fn () => $this->getStorageMock([
            "Joomla_5.2.1-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.2.1 Release",
                    "version" => "5.2.1",
                    "stability" => "stable",
                ]
            ],
            "Joomla_4.2.1-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 4.2.1 Release",
                    "version" => "4.1.2",
                    "stability" => "stable",
                ]
            ]
        ]));

        $object = new TufFetcher();
        $result = $object->getLatestVersionForBranch(4);

        $this->assertEquals("4.1.2", $result);
    }

    public function testGetLatestVersionForBranchChecksOrdering()
    {
        App::bind(StorageInterface::class, fn () => $this->getStorageMock([
            "Joomla_5.2.3-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.2.3 Release",
                    "version" => "5.2.3",
                    "stability" => "stable",
                ]
            ],
            "Joomla_5.2.1-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.2.1 Release",
                    "version" => "5.2.1",
                    "stability" => "stable",
                ]
            ],
            "Joomla_5.2.2-Stable-Upgrade_Package.zip" => [
                "custom" => [
                    "description" => "Joomla! 5.2.2 Release",
                    "version" => "5.2.2",
                    "stability" => "stable",
                ]
            ]
        ]));

        $object = new TufFetcher();
        $result = $object->getLatestVersionForBranch(5);

        $this->assertEquals("5.2.3", $result);
    }

    protected function getStorageMock(array $targets)
    {
        $targetsMock = $this->getMockBuilder(TargetsMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $targetsMock->method('getSigned')->willReturn(["targets" => $targets]);

        $storageMock = $this->getMockBuilder(EloquentModelStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (!count($targets)) {
            $storageMock->method('getTargets')->willReturn(null);
        } else {
            $storageMock->method('getTargets')->willReturn($targetsMock);
        }

        return $storageMock;
    }
}
