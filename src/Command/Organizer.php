<?php

namespace Cekurte\Media\Organizer\Command;

use Cekurte\Environment\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class Organizer extends Command
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

    protected function configure()
    {
        $this
            ->setName('cekurte:mo:organizer')
            ->setDescription('Organize files in directories using exiftool')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command organize the files in directories (by date of creation) using exiftool to do this:

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

        $exportDir  = array_filter(explode('/', Environment::get('EXIFTOOL_EXPORT_DIR')));
        $exportFile = array_filter(explode(':', Environment::get('EXIFTOOL_EXPORT_FILE')));
        $exportFileSep = Environment::get('EXIFTOOL_EXPORT_FILE_SEPARATOR');

        $directoryIterator = new \DirectoryIterator($source);

        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            if (substr($fileInfo->getFilename(), 0, 1) === '.') {
                continue;
            }

            $this->io->text('Running the "exiftool".');
            $time = $this->runExifTool($fileInfo);

            if ($time instanceof \DateTime) {
                $dir  = $source . DIRECTORY_SEPARATOR;
                $file = '';

                foreach ($exportDir as $letter) {
                    $dir .= $time->format($letter) . DIRECTORY_SEPARATOR;
                }

                foreach ($exportFile as $letter) {
                    $file .= $time->format($letter) . $exportFileSep;
                }

                $file = substr($file, 0, 0 - strlen($exportFileSep));

                if (!file_exists($dir)) {
                    $this->io->text(sprintf('Creating the directory "%s".', $dir));

                    if (mkdir($dir, 0777, true) === false) {
                        $this->io->error(sprintf(
                            'The directory "%s" can not be created, check the filesystem permissions.',
                            $dir
                        ));
                    }
                }

                $this->moveFile($fileInfo, $dir, $file);
            }

            $this->count++;
        }
    }

    private function moveFile(\SplFileInfo $fileInfo, $directory, $filename, $count = 0)
    {
        $extension = ''
            . ($count > 0 ? '_0' . $count : '')
            . '.'
            . strtolower($fileInfo->getExtension())
        ;

        $this->io->text([
            'Moving file...',
            sprintf('From: "%s"', $fileInfo->getPathname()),
            sprintf('To:   "%s"', $directory . $filename . $extension),
        ]);

        if (file_exists($directory . $filename . $extension)) {
            $this->io->text([
                'Filename already exists comparing the MD5...',
                sprintf('From: "%s"', $fromMd5 = md5_file($fileInfo->getPathname())),
                sprintf('To:   "%s"', $toMd5 = md5_file($directory . $filename . $extension)),
                sprintf('Are they equals? %s.', $fromMd5 === $toMd5 ? 'Yes' : 'No')
            ]);

            if ($fromMd5 === $toMd5) {
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
                return $this->moveFile($fileInfo, $directory, $filename, ++$count);
            }
        }

        if (rename($fileInfo->getPathname(), $directory . $filename . $extension) === false) {
            $this->io->error('The file can not be moved, check the filesystem permissions.');
        } else {
            $this->io->text('File moved with successfully.');
        }

        $this->io->newLine();

        $this->countMoved++;

        return true;
    }

    private function runExifTool(\SplFileInfo $fileInfo)
    {
        $process = new Process(sprintf(
            '%s -json -a -u -U "%s"',
            Environment::get('EXIFTOOL_BIN'),
            $fileInfo->getpathname()
        ));

        $time = null;

        $this->io->text(sprintf('Reading metadata of file "%s".', $fileInfo->getFilename()));

        $keys = [
            'Date/Time Original',
            'Content Create Date',
            'Criation Date',
            'Create Date',
            'Track Create Date',
            'Media Create Date',
            'DateTimeOriginal',
            'CreateDate',
        ];

        $process
            ->setWorkingDirectory($fileInfo->getPath())
            ->run(function ($type, $buffer) use (&$time, $keys) {
                if (Process::OUT === $type) {
                    $json = json_decode($buffer, true);

                    if (!is_null($json)) {
                        foreach ($keys as $key) {
                            if (in_array($key, array_keys($json[0]))) {
                                $value = trim($json[0][$key]);

                                $format = strlen($value) === 19
                                    ? 'Y:m:d H:i:s'
                                    : 'Y:m:d H:i:sT'
                                ;

                                return $time = \DateTime::createFromFormat(
                                    $format,
                                    $value
                                );
                            }
                        }
                    }
                }
            })
        ;

        if (!$time instanceof \DateTime) {
            $this->io->text('Metadata about date was not found.');
            $this->io->newLine();
        }

        return $time;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Organizer');
        $this->io->newLine();

        $this->sectionDirectory();

        if ($this->countMoved > 0 || $this->countRemoved > 0) {
            $this->io->success(sprintf(
                '%d of %d file(s) reorganized and %d of %d file(s) removed.',
                $this->countMoved,
                $this->count,
                $this->countRemoved,
                $this->count
            ));
        }
    }
}
