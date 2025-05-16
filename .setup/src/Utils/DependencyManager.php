<?php

namespace Setup\Utils;

class DependencyManager {
    private Logger $logger;
    private array $dependencies = [];
    private string $composerFile;
    private string $packageFile;

    public function __construct() {
        $this->logger = new Logger();
        $this->composerFile = dirname(__DIR__, 2) . '/composer.json';
        $this->packageFile = dirname(__DIR__, 2) . '/package.json';
    }

    public function loadDependencies(): void {
        $this->loadComposerDependencies();
        $this->loadNpmDependencies();
    }

    private function loadComposerDependencies(): void {
        if (!file_exists($this->composerFile)) {
            $this->logger->warning('composer.json not found');
            return;
        }

        $json = file_get_contents($this->composerFile);
        if ($json === false) {
            throw new \RuntimeException('Failed to read composer.json');
        }

        $composer = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in composer.json: ' . json_last_error_msg());
        }

        $this->dependencies['composer'] = [
            'require' => $composer['require'] ?? [],
            'require-dev' => $composer['require-dev'] ?? []
        ];

        $this->logger->info('Composer dependencies loaded');
    }

    private function loadNpmDependencies(): void {
        if (!file_exists($this->packageFile)) {
            $this->logger->warning('package.json not found');
            return;
        }

        $json = file_get_contents($this->packageFile);
        if ($json === false) {
            throw new \RuntimeException('Failed to read package.json');
        }

        $package = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in package.json: ' . json_last_error_msg());
        }

        $this->dependencies['npm'] = [
            'dependencies' => $package['dependencies'] ?? [],
            'devDependencies' => $package['devDependencies'] ?? []
        ];

        $this->logger->info('NPM dependencies loaded');
    }

    public function installDependencies(): void {
        $this->installComposerDependencies();
        $this->installNpmDependencies();
    }

    private function installComposerDependencies(): void {
        if (!isset($this->dependencies['composer'])) {
            return;
        }

        $this->logger->info('Installing Composer dependencies');
        
        $command = 'composer install';
        if (isset($this->dependencies['composer']['require-dev']) && empty($this->dependencies['composer']['require-dev'])) {
            $command .= ' --no-dev';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to install Composer dependencies');
        }

        $this->logger->info('Composer dependencies installed successfully');
    }

    private function installNpmDependencies(): void {
        if (!isset($this->dependencies['npm'])) {
            return;
        }

        $this->logger->info('Installing NPM dependencies');
        
        $command = 'npm install';
        if (isset($this->dependencies['npm']['devDependencies']) && empty($this->dependencies['npm']['devDependencies'])) {
            $command .= ' --production';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to install NPM dependencies');
        }

        $this->logger->info('NPM dependencies installed successfully');
    }

    public function updateDependencies(): void {
        $this->updateComposerDependencies();
        $this->updateNpmDependencies();
    }

    private function updateComposerDependencies(): void {
        if (!isset($this->dependencies['composer'])) {
            return;
        }

        $this->logger->info('Updating Composer dependencies');
        
        $command = 'composer update';
        if (isset($this->dependencies['composer']['require-dev']) && empty($this->dependencies['composer']['require-dev'])) {
            $command .= ' --no-dev';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to update Composer dependencies');
        }

        $this->logger->info('Composer dependencies updated successfully');
    }

    private function updateNpmDependencies(): void {
        if (!isset($this->dependencies['npm'])) {
            return;
        }

        $this->logger->info('Updating NPM dependencies');
        
        $command = 'npm update';
        if (isset($this->dependencies['npm']['devDependencies']) && empty($this->dependencies['npm']['devDependencies'])) {
            $command .= ' --production';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to update NPM dependencies');
        }

        $this->logger->info('NPM dependencies updated successfully');
    }

    public function addComposerDependency(string $package, string $version = '*', bool $dev = false): void {
        if (!isset($this->dependencies['composer'])) {
            $this->dependencies['composer'] = [
                'require' => [],
                'require-dev' => []
            ];
        }

        $key = $dev ? 'require-dev' : 'require';
        $this->dependencies['composer'][$key][$package] = $version;

        $command = "composer require {$package}:{$version}";
        if ($dev) {
            $command .= ' --dev';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException("Failed to add Composer dependency: {$package}");
        }

        $this->logger->info("Added Composer dependency: {$package}");
    }

    public function addNpmDependency(string $package, string $version = 'latest', bool $dev = false): void {
        if (!isset($this->dependencies['npm'])) {
            $this->dependencies['npm'] = [
                'dependencies' => [],
                'devDependencies' => []
            ];
        }

        $key = $dev ? 'devDependencies' : 'dependencies';
        $this->dependencies['npm'][$key][$package] = $version;

        $command = "npm install {$package}@{$version}";
        if ($dev) {
            $command .= ' --save-dev';
        } else {
            $command .= ' --save';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException("Failed to add NPM dependency: {$package}");
        }

        $this->logger->info("Added NPM dependency: {$package}");
    }

    public function removeComposerDependency(string $package, bool $dev = false): void {
        if (!isset($this->dependencies['composer'])) {
            return;
        }

        $key = $dev ? 'require-dev' : 'require';
        if (!isset($this->dependencies['composer'][$key][$package])) {
            return;
        }

        unset($this->dependencies['composer'][$key][$package]);

        $command = "composer remove {$package}";
        if ($dev) {
            $command .= ' --dev';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException("Failed to remove Composer dependency: {$package}");
        }

        $this->logger->info("Removed Composer dependency: {$package}");
    }

    public function removeNpmDependency(string $package, bool $dev = false): void {
        if (!isset($this->dependencies['npm'])) {
            return;
        }

        $key = $dev ? 'devDependencies' : 'dependencies';
        if (!isset($this->dependencies['npm'][$key][$package])) {
            return;
        }

        unset($this->dependencies['npm'][$key][$package]);

        $command = "npm uninstall {$package}";
        if ($dev) {
            $command .= ' --save-dev';
        } else {
            $command .= ' --save';
        }

        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \RuntimeException("Failed to remove NPM dependency: {$package}");
        }

        $this->logger->info("Removed NPM dependency: {$package}");
    }

    public function getDependencies(): array {
        return $this->dependencies;
    }

    public function hasComposerDependency(string $package, bool $dev = false): bool {
        if (!isset($this->dependencies['composer'])) {
            return false;
        }

        $key = $dev ? 'require-dev' : 'require';
        return isset($this->dependencies['composer'][$key][$package]);
    }

    public function hasNpmDependency(string $package, bool $dev = false): bool {
        if (!isset($this->dependencies['npm'])) {
            return false;
        }

        $key = $dev ? 'devDependencies' : 'dependencies';
        return isset($this->dependencies['npm'][$key][$package]);
    }

    public function getComposerDependencyVersion(string $package, bool $dev = false): ?string {
        if (!isset($this->dependencies['composer'])) {
            return null;
        }

        $key = $dev ? 'require-dev' : 'require';
        return $this->dependencies['composer'][$key][$package] ?? null;
    }

    public function getNpmDependencyVersion(string $package, bool $dev = false): ?string {
        if (!isset($this->dependencies['npm'])) {
            return null;
        }

        $key = $dev ? 'devDependencies' : 'dependencies';
        return $this->dependencies['npm'][$key][$package] ?? null;
    }
} 