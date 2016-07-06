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
            if (!is_dir($input)) {
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

                $file = substr($file, 0, 0 - strlen($exportFileSep)) . '.' . strtolower($fileInfo->getExtension());

                if (!file_exists($dir)) {
                    $this->io->text(sprintf('Creating the directory "%s".', $dir));

                    if (mkdir($dir, 0777, true) === false) {
                        $this->io->error(sprintf(
                            'The directory "%s" can not be created, check the filesystem permissions.',
                            $dir
                        ));
                    }
                }

                $this->io->text([
                    'Moving file...',
                    sprintf('From: "%s"', $fileInfo->getPathname()),
                    sprintf('To:   "%s"', $dir . $file),
                ]);

                if (rename($fileInfo->getPathname(), $dir . $file) === false) {
                    $this->io->error('The file can not be moved, check the filesystem permissions.');
                }

                $this->countMoved++;

                $this->io->newLine();
            }

            $this->count++;
        }
    }

    private function runExifTool(\SplFileInfo $fileInfo)
    {
        $process = new Process(sprintf(
            '%s %s',
            Environment::get('EXIFTOOL_BIN'),
            $fileInfo->getpathname()
        ));

        $time = null;

        $this->io->text(sprintf('Reading metadata of file "%s".', $fileInfo->getFilename()));

        $regex = '.*?:.*?(\d{4}:\d{2}:\d{2}\s+\d{2}:\d{2}:\d{2})(-?\+?\d{2}:\d{2})?';

        $keys = [
            'Date/Time Original',
            'Content Create Date',
            'Criation Date',
            'Create Date',
            'Track Create Date',
            'Media Create Date',
        ];

        $process
            ->setWorkingDirectory($fileInfo->getPath())
            ->run(function ($type, $buffer) use (&$time, $regex, $keys) {
                if (Process::OUT === $type) {
                    foreach ($keys as $key) {
                        if (strpos($buffer, $key) !== false) {
                            $currentRegex = '/' . str_replace('/', '\/', $key) . $regex . '/';

                            if (preg_match($currentRegex, $buffer, $matches) !== false) {
                                if (isset($matches[2])) {
                                    return $time = \DateTime::createFromFormat(
                                        'Y:m:d H:i:sT',
                                        $matches[1] . $matches[2]
                                    );
                                } else {
                                    return $time = \DateTime::createFromFormat(
                                        'Y:m:d H:i:s',
                                        $matches[1]
                                    );
                                }
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

        if ($this->countMoved > 0) {
            $this->io->success(sprintf(
                '%d of %d file(s) was reorganized.',
                $this->countMoved,
                $this->count
            ));
        }
    }
}
