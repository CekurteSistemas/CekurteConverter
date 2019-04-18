<?php

namespace Cercal\IO\MediaOrganizer\Command;

use Cekurte\Environment\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class Converter extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var string
     */
    private $extension;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var int
     */
    private $countConverted = 0;

    protected function configure()
    {
        $this
            ->setName('cercal:mo:converter')
            ->setDescription('Convert files to another format using ffmpeg tool')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command convert one or more files to another format using ffmpeg tool to do this:

  <info>php %command.full_name% %command.name%</info>

An assistant will asks you for all arguments that are needed.
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

        $this->io->section('Extension');

        $question = 'Set the extension (for example: mts)';

        $this->extension = $this->io->ask($question, null, function ($input) {
            return strtolower($input);
        });

        $this->io->success($this->extension);

        $directoryIterator = new \DirectoryIterator($source);

        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            if (strtolower($fileInfo->getExtension()) !== $this->extension) {
                continue;
            }

            $this->io->text('Running the "ffmpeg" tool.');
            $result = $this->runFfmpegTool($fileInfo);

            if ($result === true) {
                $this->countConverted++;
            }

            $this->count++;
        }

        if ($this->count === 0) {
            $this->io->note(sprintf(
                'Nothing file with extension "*.%s" was found.',
                $this->extension
            ));
        }
    }

    private function runFfmpegTool(\SplFileInfo $fileInfo)
    {
        $process = new Process(sprintf(
            '%s -i "%s"%s "%s"',
            Environment::get('FFMPEG_BIN'),
            $fileInfo->getPathname(),
            empty(Environment::get('FFMPEG_ARGS')) ? '' : ' ' . Environment::get('FFMPEG_ARGS'),
            substr($fileInfo->getpathname(), 0, 0 - (strlen($this->extension) + 1)) . '.mp4'
        ));

        $this->io->text(sprintf('Converting movie "%s".', $fileInfo->getFilename()));

        $result = false;

        $process
            ->setWorkingDirectory($fileInfo->getPath())
            ->run(function ($type, $buffer) use (&$result) {
                if (Process::OUT === $type) {
                    $result = true;
                }

                echo $buffer;
            })
        ;

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Converter');
        $this->io->newLine();

        $this->sectionDirectory();

        if ($this->countConverted > 0) {
            $this->io->success(sprintf(
                '%d of %d file(s) was converted.',
                $this->countConverted,
                $this->count
            ));
        }
    }
}
