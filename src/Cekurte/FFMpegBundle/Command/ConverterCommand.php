<?php

namespace Cekurte\FFMpegBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConverterCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cekurte:converter')
            ->setDescription('Converter File')
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Informe o nome do arquivo'
            )
            ->addOption(
               'format',
               null,
               InputOption::VALUE_OPTIONAL,
               'Se deseja converter para um outro formato, informe-o aqui, o valor default é mp3.',
                'mp3'
            )
            ->addOption(
               'directory',
               null,
               InputOption::VALUE_REQUIRED,
               'Informe o diretório onde encontram-se os arquivos que deseja converter'
            )
        ;
    }

    private function getTargetPath(InputInterface $input)
    {
        $targetPath = $input->getOption('directory') . DIRECTORY_SEPARATOR . 'mp3';

        if (!file_exists($targetPath)) {
            mkdir($targetPath);
        }

        return $targetPath;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directoryIterator = new \DirectoryIterator($input->getOption('directory'));

        foreach ($directoryIterator as $fileInfo) {

            if($fileInfo->isDot()) {
                continue;
            }

            if (substr($fileInfo->getFilename(), 0, 1) === '.') {
                continue;
            }

            if ($fileInfo->getExtension() !== 'mp4') {
                continue;
            }

            $sourceFile = ''
                . $fileInfo->getPath()
                . DIRECTORY_SEPARATOR
                . $fileInfo->getFilename()
            ;

            $targetPath = $this->getTargetPath($input);

            $targetFile = ''
                . $targetPath
                . DIRECTORY_SEPARATOR
                . $fileInfo->getFilename()
                . '.'
                . $input->getOption('format')
            ;

            // $output->writeln($text);

            exec('ffmpeg -i "' . $sourceFile . '" -acodec libmp3lame -ab 192k "' . $targetFile . '"');
        }
    }
}
