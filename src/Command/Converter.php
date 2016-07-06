<?php

namespace Cekurte\Media\Organizer\Command;

use Cekurte\Environment\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Converter extends Command
{
    const VIDEO_TO_VIDEO = 'Convert a video to another video format';
    const SONG_TO_SONG   = 'Convert a song to another song format';
    const VIDEO_TO_SONG  = 'Convert a video to a song format';

    const DATA_FROM_FILE      = 'Load data from a file';
    const DATA_FROM_DIRECTORY = 'Load data from a directory';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var string
     */
    private $mediaType;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $source;

    protected function configure()
    {
        $this
            ->setName('cekurte:mo:converter')
            ->setDescription('Convert files to another format using ffmpeg tool')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command convert one or more files to another format using ffmpeg tool to do this:

  <info>php %command.full_name% %command.name%</info>

An assistant will asks you for all arguments that are needed.
EOF
            )
        ;
    }

    private function sectionMediaType()
    {
        $this->io->section('Media type');

        $mediaType = $this->io->choice(
            'Select the type of convertion that you want to do',
            [self::VIDEO_TO_VIDEO, self::SONG_TO_SONG, self::VIDEO_TO_SONG],
            self::VIDEO_TO_VIDEO
        );

        $this->io->success($mediaType);

        $this->mediaType = $mediaType;
    }

    private function sectionFrom()
    {
        $this->io->section('From');

        $from = $this->io->choice(
            'Select the type of input data',
            [self::DATA_FROM_FILE, self::DATA_FROM_DIRECTORY],
            self::DATA_FROM_DIRECTORY
        );

        $ask = $from === self::DATA_FROM_FILE ? 'filename' : 'directory';
        $question = sprintf('Set the %s (absolute path)', $ask);

        $source = $this->io->ask($question, null, function ($input) use ($ask, $from) {
            if (!file_exists($input)) {
                throw new \RuntimeException(sprintf('You must type an existant %s.', $ask));
            }

            if ($from === self::DATA_FROM_DIRECTORY && !is_dir($input)) {
                throw new \RuntimeException('You must type an valid directory.');
            }

            if ($from === self::DATA_FROM_FILE && !is_file($input)) {
                throw new \RuntimeException('You must type an valid filename.');
            }

            return $input;
        });

        $this->io->success(sprintf('[%s] %s', $ask, $source));

        $this->from   = $from;
        $this->source = $source;
    }

    private function getFiles()
    {
        if ($this->from === self::DATA_FROM_FILE) {
            return [new \SplFileInfo($this->source)];
        }

        $files = [];

        $directoryIterator = new \DirectoryIterator($this->source);

        foreach ($directoryIterator as $current) {
            if (!$current->isDot()) {
                $files[] = $current->getFileInfo();
            }
        }

        return $files;
    }

    private function skipFile(\SplFileInfo $fileInfo)
    {
        if ($fileInfo->getExtension() === 'MTS') {
            return false;
        }

        return true;
    }

    private function runFfmpegTool(\SplFileInfo $fileInfo)
    {
        $command = sprintf(
            '%s "%s"%s %s',
            Environment::get('FFMPEG_BIN'),
            $fileInfo->getPathname(),
            empty(Environment::get('FFMPEG_ARGS')) ? '' : ' ' . Environment::get('FFMPEG_ARGS'),
            $fileInfo->getpathname() . '.mp4'
        );

        $output = [];

        exec($command, $output);

        return $output;
    }

    private function runExifTool(\SplFileInfo $fileInfo)
    {
        $command = sprintf(
            '%s "%s"',
            Environment::get('EXIFTOOL_BIN'),
            $fileInfo->getpathname()
        );

        $output = [];

        exec($command, $output);

        return $output;
    }

    private function getFileDateCreation(array $exiftoolOutput)
    {
        return new \DateTime(sprintf(
            '%s %s',
            str_replace(':', '-', substr($exiftoolOutput[18], strpos($exiftoolOutput[18], ': ') + 2, 10)),
            substr($exiftoolOutput[18], strpos($exiftoolOutput[18], ': ') + 13, 8)
        ));
    }

    private function sectionConverter()
    {
        $this->io->section('Converter');

        $files = $this->getFiles();

        foreach ($files as $fileInfo) {
            $this->io->text(sprintf('Reading the file "%s"', $fileInfo->getPathname()));

            if ($this->skipFile($fileInfo)) {
                continue;
            }

            $this->io->text('Running the "ffmpeg" tool');
            $this->runFfmpegTool($fileInfo);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Cekurte Converter');
        $this->io->newLine();

        $this->sectionMediaType();
        $this->sectionFrom();
        $this->sectionConverter();
    }
}
