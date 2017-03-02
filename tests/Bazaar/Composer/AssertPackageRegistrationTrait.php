<?php

namespace Tests\Bazaar\Composer;

use Illuminate\Support\Arr;

trait AssertPackageRegistrationTrait
{
    public function getRegisteredPackages()
    {
        $json = json_decode($this->filesystem->get($this->getComposerWorkingDir().'/composer.json'), true);

        return Arr::get($json, 'require', []);
    }

    public function isPackageRegistered($package)
    {
        $registered = $this->getRegisteredPackages();

        return array_key_exists($package, $registered);
    }

    public function isPackageVersionRegistered($package, $version)
    {
        $registered = $this->getRegisteredPackages();

        return array_key_exists($package, $registered) && $registered[$package] === $version;
    }

    public function assertPackageRegistered($package, $version = null)
    {
        $this->assertTrue($this->isPackageRegistered($package), 'Package '.$package.' should be in composer.json');

        if (!is_null($version)) {
            $this->assertTrue($this->isPackageVersionRegistered($package, $version), 'Version '.$version.' should be in composer.json for package '.$package);
        }
    }

    public function assertPackageNotRegistered($package, $version)
    {
        $this->assertFalse($this->isPackageVersionRegistered($package, $version, 'Version '.$version.' should not be in composer.json for package '.$package));
    }
}
