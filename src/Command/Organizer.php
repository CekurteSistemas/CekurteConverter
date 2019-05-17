<?php

namespace Cercal\IO\MediaOrganizer\Command;

use Cercal\IO\MediaOrganizer\File\DestinationArtifact;
use Cercal\IO\MediaOrganizer\File\FileSystemOperationNotCompletedException;
use Cercal\IO\MediaOrganizer\File\Md5Tokenizer;
use Cercal\IO\MediaOrganizer\File\PictureNominator;
use Cercal\IO\MediaOrganizer\Observer\Notification;
use Cercal\IO\MediaOrganizer\Observer\Publisher;
use Cercal\IO\MediaOrganizer\Observer\ConsoleNotifier;
use RuntimeException;
use Cercal\IO\MediaOrganizer\Command\Question\SourceDirectoryQuestion;
use Cercal\IO\MediaOrganizer\File\Picture;
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
		$this->fileSystem = new LocalFileSystem(new Md5Tokenizer());
		$this->publisher = new Publisher();

		parent::__construct();
	}

	protected function configure()
    {
        $this
            ->setName('cercal:mo:organizer')
            ->setDescription('Organize files in directories using exiftool')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command organizes the files in directories (by date of creation) by using exiftool binary.

  <info>php %command.full_name% %command.name%</info>
EOF
            )
        ;
    }

	private function resolveTargetPathname(Picture $picture): DestinationArtifact
	{
		$destinationArtifact = (new PictureNominator($picture, new Md5Tokenizer()))->nominate();

		if (!$this->fileSystem->exists($destinationArtifact->getPath())) {
			$this->fileSystem->mkdir($destinationArtifact->getPath());
		}

		return $destinationArtifact;
    }

	private function organize(Picture $picture, DestinationArtifact $destinationArtifact, $filenameSuffixNumber = 0): void
	{
		$this->publisher->publish(Notification::info(sprintf('Source File: "%s".', $picture->getPathname())));
		$this->publisher->publish(Notification::info(sprintf('Target File: "%s".', $destinationArtifact)));

		if ($this->fileSystem->exists((string) $destinationArtifact)) {
			$this->publisher->publish(Notification::info('A target file with the same name already exists.'));
			$this->publisher->publish(Notification::info(('Comparing the files by using md5 checksum.')));

			$equals = $this->fileSystem->compare($picture->getPathname(), (string) $destinationArtifact);

			if ($equals) {
				$this->publisher->publish(Notification::info('The files are equals to each other.'));
				$this->publisher->publish(Notification::info('Removing the source file.'));

				$this->fileSystem->rm($picture->getPathname());

				$this->publisher->publish(Notification::success('Source file removed successfully.'));

				return;
			} else {
				$this->publisher->publish(Notification::warning('The files are different.'));
				$this->publisher->publish(Notification::info('Generating a sequential filename suffix.'));
				$this->publisher->publish(Notification::info('Trying to organize the files again.'));

				$this->organize(
					$picture,
					new DestinationArtifact(
						$destinationArtifact->getPath(),
						$destinationArtifact->getFile(),
						$destinationArtifact->getExtension(),
						sprintf('%03d', ++$filenameSuffixNumber)
					),
					$filenameSuffixNumber
				);
				return;
			}
		}

		$this->fileSystem->mv($picture->getPathname(), (string) $destinationArtifact);

		$this->publisher->publish(Notification::success('Source file moved successfully.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$executionStartTime = microtime(true);

		$io = new SymfonyStyle($input, $output);
		$io->title('Organizer');
		$io->newLine();

		$sourceDirectory = $io->askQuestion(new SourceDirectoryQuestion());
		$io->success($sourceDirectory);

		$this->publisher->attach(new ConsoleNotifier($io));

        $searchContext = new SearchContext($sourceDirectory, true, [
			new SearchForPictures(),
		]);

		$searchResults = (new Search())->search($searchContext);

		if (count($searchResults) == 0) {
			$io->warning('No files matched the search criteria.');

			return;
		}

		$io->progressStart(count($searchResults));

		/** @var Picture $file */
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

			try {
				$this->organize($file, $targetPathname);
			} catch (FileSystemOperationNotCompletedException $e) {
				$this->publisher->publish(Notification::error($e->getMessage()));
			}
		}

		$executionEndTime = microtime(true);


		$report = $this->publisher->getReport();

		$io->section('Reports');
		$io->writeln(sprintf(' > Execution time: %.2fs', ($executionEndTime - $executionStartTime)));
		$io->writeln(sprintf(' > Memory peak usage: %.3fMB', (memory_get_peak_usage() / 1024) / 1024));
		$io->newLine();
		$io->table(array_keys($report), [$report]);
    }
}
