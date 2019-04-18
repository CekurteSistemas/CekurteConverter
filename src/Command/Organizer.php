<?php

namespace Cercal\IO\MediaOrganizer\Command;

use Cercal\IO\MediaOrganizer\Command\Style\AppStyle;
use Cercal\IO\MediaOrganizer\Observer\Notification;
use Cercal\IO\MediaOrganizer\Observer\NotificationObservable;
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

class Organizer extends Command
{
	private $fileSystem;

	public function __construct()
	{
		$this->fileSystem = new LocalFileSystem();

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

		if (!$creationTime instanceof DateTimeImmutable) {
			return '';
		}

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

	private function getFilenameSuffixNumber(): Generator
	{
		for ($i = 1; $i <= 100; $i++) {
			yield number_format($i, 3);
		}
    }

	private function organize(AppStyle $io, string $sourcePathname, string $targetPathname): void
	{
		$notification = new NotificationObservable();
		$notification->attach(new ConsoleNotifier($io));

		if ($this->fileSystem->exists(new File($targetPathname))) {
			$notification->setNotification(Notification::info('A target file with the same name already exists.'));
			$notification->setNotification(Notification::info(('Comparing the files by using md5 checksum.')));

			$equals = $this->fileSystem->compare($sourcePathname, $targetPathname);

			if ($equals) {
				$notification->setNotification(Notification::info('The files are equals to each other.'));
				$notification->setNotification(Notification::info('Removing the source file.'));

				try {
					$this->fileSystem->rm($sourcePathname);

					$notification->setNotification(Notification::success('Source file removed successfully.'));
				} catch (RuntimeException $e) {
					$notification->setNotification(Notification::error($e->getMessage()));
				}

				$io->newLine();

				return;
			} else {
				$notification->setNotification(Notification::info('The files are different.'));

				$this->organize($io, $sourcePathname, $targetPathname . '.abc');
			}
		}

		try {
			$this->fileSystem->mv($sourcePathname, $targetPathname);

			$notification->setNotification(Notification::success('Source file moved successfully.'));
		} catch (RuntimeException $e) {
			$notification->setNotification(Notification::error($e->getMessage()));
		}

		$io->newLine();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new AppStyle($input, $output);
		$io->title('Organizer');
		$io->newLine();

		$sourceDirectory = $io->askQuestion(new SourceDirectoryQuestion());
		$io->success($sourceDirectory);

        $searchContext = new SearchContext($sourceDirectory, true, [
			new SearchForPictures(),
		]);

		$searchResults = (new Search($searchContext))->search();

		if (count($searchResults) == 0) {
			$io->warning('No files matched the search criteria.');

			return;
		}

		$io->progressStart(count($searchResults));

		foreach ($searchResults as $file) {
			$targetPathname = $this->resolveTargetPathname($file);

			$io->progressAdvance();

			$io->newLine();
			$io->writeln(sprintf(' <<< Source File: "%s"', $file->getPathname()));
			$io->writeln(sprintf(' >>> Target File: "%s"', $targetPathname));

			$this->organize($io, $file->getPathname(), $targetPathname);
		}
    }
}
