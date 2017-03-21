<?php

namespace Flagrow\Bazaar\Composer\Utils;

use Composer\DependencyResolver\Pool;
use Composer\IO\IOInterface;
use Composer\Json\JsonManipulator;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Flagrow\Bazaar\Exceptions\CannotWriteComposerFileException;

class ComposerFileEditor
{
    /**
     * @var JsonManipulator
     */
    protected $manipulator;

    /**
     * @var string
     */
    protected $contentBackup;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @param string $filename Path to composer.json
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->contentBackup = file_get_contents($filename);
        $this->manipulator = new JsonManipulator($this->contentBackup);
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->manipulator->getContents();
    }

    /**
     * Write to file and handle errors
     * @param string $content
     * @throws CannotWriteComposerFileException
     */
    protected function writeFile($content)
    {
        $status = file_put_contents($this->filename, $content);

        if ($status === false) {
            throw new CannotWriteComposerFileException();
        }
    }

    /**
     * Save current manipulator to file
     */
    public function saveToFile()
    {
        $this->writeFile($this->getContents());
    }

    /**
     * Restore the backup to the file
     */
    public function restoreToFile()
    {
        $this->writeFile($this->contentBackup);
    }

    /**
     * Add or update a package
     * @param string $package Package name
     * @param string $version Version constraint
     */
    public function addPackage($package, $version)
    {
        $this->manipulator->addLink('require', $package, $version);
    }

    /**
     * Remove a package
     * @param string $package Package name
     */
    public function removePackage($package)
    {
        $this->manipulator->removeSubNode('require', $package);
    }

    /**
     * Add a repository
     * @param string $name
     * @param array $config
     */
    public function addRepository($name, $config)
    {
        $this->manipulator->addRepository($name, $config);
    }

    /**
     * Get a dependency pool
     * Based on the protected InitCommand::getPool() method of Composer
     * @param IOInterface $io
     * @return Pool
     */
    public function getPool(IOInterface $io)
    {
        $json = json_decode($this->getContents(), true);

        $pool = new Pool(array_key_exists('minimum-stability', $json) ? $json['minimum-stability'] : 'stable');
        $pool->addRepository(new CompositeRepository(array_merge(
            [new PlatformRepository],
            RepositoryFactory::defaultRepos($io)
        )));

        return $pool;
    }
}
