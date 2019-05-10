<?php

namespace Cercal\IO\MediaOrganizer\Command;

use Cercal\IO\MediaOrganizer\Observer\Notification;
use Cercal\IO\MediaOrganizer\Observer\Publisher;
use Cercal\IO\MediaOrganizer\Observer\ConsoleNotifier;
use DateTimeImmutable;
use RuntimeException;
use Generator;
use Cercal\IO\MediaOrganizer\Command\Question\SourceDirectoryQuestion;
use Cekurte\Environment\Environment;
use Cercal\IO\MediaOrganizer\File\File;
use Cercal\IO\MediaOrganizer\File\LocalFileSystem;
use Cercal\IO\MediaOrganizer\File\Search;
use Cercal\IO\MediaOrganizer\File\SearchContext;
use Cercal\IO\MediaOrganizer\File\SearchForPictures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Organizer extends Command
{
	private $fileSystem;
	private $publisher;

	public function __construct()
	{
		$this->fileSystem = new LocalFileSystem();
		$this->publisher = new Publisher();

		parent::__construct();
	}

	protected function configure()
    {
        $this
            ->setName('cercal:mo:organizer')
            ->setDescription('Organize files in directories using exiftool')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command organize the files in directories (by date of creation) using exiftool to do this:

  <info>php %command.full_name% %command.name%</info>
EOF
            )
        ;
    }

	private function resolveTargetPathname(File $file): string
	{
		$result = Environment::get('ORGANIZER_TARGET_ROOT_DIR') . DIRECTORY_SEPARATOR;

		$dirnameFormat  = array_filter(explode('/', Environment::get('ORGANIZER_TARGET_DIR_NAME_FORMAT')));
		$filenameFormat = array_filter(explode(':', Environment::get('ORGANIZER_TARGET_FILE_NAME_FORMAT')));
		$filenameSeparator = Environment::get('ORGANIZER_TARGET_FILE_NAME_SEPARATOR');

		$creationTime = $file->getExifData()->getCreationTime();

		foreach ($dirnameFormat as $letter) {
			$result .= $creationTime->format($letter) . DIRECTORY_SEPARATOR;
		}

		if (!$this->fileSystem->exists($result)) {
			$this->fileSystem->mkdir($result);
		}

		foreach ($filenameFormat as $letter) {
			$result .= $creationTime->format($letter) . $filenameSeparator;
		}

		return substr($result, 0, 0 - strlen($filenameSeparator)) . '.' . strtolower($file->getExtension());
    }

	private function organize(string $sourcePathname, string $targetPathname): void
	{
		$this->publisher->publish(Notification::info(sprintf('Source File: "%s".', $sourcePathname)));
		$this->publisher->publish(Notification::info(sprintf('Target File: "%s".', $targetPathname)));

		$targetFile = new File($targetPathname);

		if (is_null($targetFile->getExifData())) {

		}

		if ($this->fileSystem->exists($targetFile)) {
			$this->publisher->publish(Notification::info('A target file with the same name already exists.'));
			$this->publisher->publish(Notification::info(('Comparing the files by using md5 checksum.')));

			$equals = $this->fileSystem->compare($sourcePathname, $targetPathname);

			if ($equals) {
				$this->publisher->publish(Notification::info('The files are equals to each other.'));
				$this->publisher->publish(Notification::info('Removing the source file.'));

				try {
					$this->fileSystem->rm($sourcePathname);

					$this->publisher->publish(Notification::success('Source file removed successfully.'));
				} catch (RuntimeException $e) {
					$this->publisher->publish(Notification::error($e->getMessage()));
				}

				return;
			} else {
				$this->publisher->publish(Notification::warning('The files are different.'));
			}
		}

		try {
			$this->fileSystem->mv($sourcePathname, $targetPathname);

			$this->publisher->publish(Notification::success('Source file moved successfully.'));
		} catch (RuntimeException $e) {
			$this->publisher->publish(Notification::error($e->getMessage()));
		}
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
		$io->title('Organizer');
		$io->newLine();

		$sourceDirectory = $io->askQuestion(new SourceDirectoryQuestion());
		$io->success($sourceDirectory);

		$this->publisher->attach(new ConsoleNotifier($io));

        $searchContext = new SearchContext($sourceDirectory, true, [
			new SearchForPictures(),
		]);

		$searchResults = (new Search($searchContext))->search();

		if (count($searchResults) == 0) {
			$io->warning('No files matched the search criteria.');

			return;
		}

		$io->progressStart(count($searchResults));

		/** @var File $file */
		foreach ($searchResults as $index => $file) {
			if ($index > 0) $io->newLine();
			$io->progressAdvance();
			$io->newLine();

			try {
				$targetPathname = $this->resolveTargetPathname($file);
			} catch (ProcessFailedException $e) {
				$commandOutput = $io->isVerbose()
					? sprintf("\n\nCommand output: \n%s", $e->getProcess()->getOutput())
					: '';

				$this->publisher->publish(Notification::error(sprintf(
					'The command "%s" failed [%s].%s',
					$e->getProcess()->getCommandLine(),
					$e->getProcess()->getExitCodeText(),
					$commandOutput
				)));

				continue;
			}


			$this->organize($file->getPathname(), $targetPathname);
		}
    }
}
