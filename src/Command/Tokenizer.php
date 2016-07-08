<?php

namespace Cekurte\Media\Organizer\Command;

use Cekurte\Environment\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class Tokenizer extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var int
     */
    private $countMoved = 0;

    /**
     * @var int
     */
    private $countRemoved = 0;

    /**
     * @var int
     */
    private $countIgnored = 0;

    protected function configure()
    {
        $this
            ->setName('cekurte:mo:tokenizer')
            ->setDescription('Rename files in directory using md5_file')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command organize the files in directory using md5_file (function of PHP) to do this:

  <info>php %command.full_name% %command.name%</info>
EOF
            )
        ;
    }

    private function sectionDirectory()
    {
        $this->io->section('Directory');

        $question = 'Set the directory (absolute path)';

        $source = $this->io->ask($question, null, function ($input) {
            if (!is_dir($input = str_replace('\\ ', ' ', $input))) {
                throw new \RuntimeException('You must type an valid directory.');
            }

            return $input;
        });

        $this->io->success($source);

        $directoryIterator = new \DirectoryIterator($source);

        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            if (substr($fileInfo->getFilename(), 0, 1) === '.') {
                continue;
            }

            $this->renameFile($fileInfo);

            $this->count++;
        }
    }

    private function renameFile(\SplFileInfo $fileInfo)
    {
        $this->io->text('Running the "md5_file".');

        $md5Path = $fileInfo->getPath() . DIRECTORY_SEPARATOR;

        $md5Filename = sprintf(
            '%s.%s',
            md5_file($fileInfo->getPathname()),
            strtolower($fileInfo->getExtension())
        );

        $md5Pathname = $md5Path . $md5Filename;

        $this->io->text([
            'Renaming file...',
            sprintf('From: "%s"', $fileInfo->getPathname()),
            sprintf('To:   "%s"', $md5Pathname),
        ]);

        if (file_exists($md5Pathname)) {
            $this->io->text('Filename already exists.');

            if ($fileInfo->getFilename() !== $md5Filename) {
                $this->io->text(sprintf(
                    'Removing the file "%s".',
                    $fileInfo->getPathname()
                ));

                $this->io->newLine();

                if (unlink($fileInfo->getPathname()) === false) {
                    $this->io->error(sprintf(
                        'The file "%s" can not be removed, check the filesystem permissions.',
                        $fileInfo->getPathname()
                    ));
                }

                $this->countRemoved++;

                return false;
            } else {
                $this->countIgnored++;
            }
        }

        if (!file_exists($md5Pathname)) {
            if (rename($fileInfo->getPathname(), $md5Pathname) === false) {
                $this->io->error('The file can not be moved, check the filesystem permissions.');
            } else {
                $this->io->text('File moved with successfully.');
            }

            $this->io->newLine();

            $this->countMoved++;

            return true;
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Tokenizer');
        $this->io->newLine();

        $this->sectionDirectory();

        if ($this->countMoved > 0 || $this->countRemoved > 0 || $this->countIgnored > 0) {
            $this->io->success(sprintf(
                '%d of %d file(s) renamed, %d of %d file(s) removed and %d of %d file(s) ignored.',
                $this->countMoved,
                $this->count,
                $this->countRemoved,
                $this->count,
                $this->countIgnored,
                $this->count
            ));
        }
    }
}
