<?php

namespace AskerAkbar\Lens\Service\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PublishMigrationCommand extends Command
{
    protected static $defaultName = 'lens:publish-migration';
    protected static $defaultDescription = 'Copy the Lens migration SQL file to your migrations directory (default: database/migrations)';

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption(
                'target',
                null,
                InputOption::VALUE_OPTIONAL,
                'Target directory to copy the migration file to',
                'database/migrations'
            )
            ->addOption(
                'doctrine',
                null,
                InputOption::VALUE_NONE,
                'Export a Doctrine migration file instead of raw SQL (if Doctrine is detected)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getOption('target') ?: 'database/migrations';
        $useDoctrine = $input->getOption('doctrine') || $this->isDoctrineAvailable();

        // Find the package root directory
        $packageRoot = $this->findPackageRoot();
        if ($useDoctrine) {
            $source = $packageRoot . '/migrations/Version20240501CreateQueryLogTable.php';
            $dest = rtrim($target, '/\\') . '/Version20240501CreateQueryLogTable.php';
        } else {
            $source = $packageRoot . '/migrations/2024_05_01_000000_create_query_log_table.sql';
            $dest = rtrim($target, '/\\') . '/2024_05_01_000000_create_query_log_table.sql';
        }

        // Ensure the source file exists
        if (!file_exists($source)) {
            $output->writeln("<error>Migration file not found at: $source</error>");
            return Command::FAILURE;
        }
        if (!is_dir($target)) {
            if (!mkdir($target, 0777, true)) {
                $output->writeln("<error>Failed to create target directory: $target</error>");
                return Command::FAILURE;
            }
        }
        if (copy($source, $dest)) {
            $output->writeln("<info>Migration published to $dest</info>");
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>Failed to copy migration file.</error>');
            return Command::FAILURE;
        }
    }
    
    private function findPackageRoot(): string
    {
        // Start from the current file's directory and work our way up
        $currentDir = __DIR__;
        
        // Go up directories until we find the package root (where composer.json exists)
        while ($currentDir !== dirname($currentDir)) {
            if (file_exists($currentDir . '/composer.json')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }
        
        // Fallback: if we can't find composer.json, use the original approach
        return dirname(__DIR__, 3);
    }

    private function isDoctrineAvailable(): bool
    {
        // Check for Doctrine Migrations or ORM in vendor
        $packageRoot = $this->findPackageRoot();
        return (class_exists('Doctrine\\Migrations\\AbstractMigration'));
    }
} 