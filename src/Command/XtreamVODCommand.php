<?php

namespace App\Command;

use App\Service\Xtream\StreamNameSanitizer;
use App\Service\Xtream\XtreamApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'xtream:vod',
    description: 'Load VOD streams from Xtream Server and create folder structure.',
    hidden: false
)]
class XtreamVODCommand extends Command
{
    public function __construct(
        #[Autowire(param: 'app.xtream.strm_directory')]
        private readonly string $strmDirectory,
        private readonly XtreamApiClient $client,
        private readonly Filesystem $filesystem,
        private readonly StreamNameSanitizer $streamNameSanitizer
    ){
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section1 = $output->section();
        $section2 = $output->section();
        $section3 = $output->section();

        $directory = Path::makeAbsolute('Movies', $this->strmDirectory);

        $progressIndicator = new ProgressIndicator($section1);
        $progressIndicator->start('Loading VOD categories');
        $categories = $this->client->getVODCategories('/^\|DE\|/');
        $progressIndicator->setMessage('Processing VOD categories');

        $progressBar = new ProgressBar($section2);

        foreach($progressBar->iterate($categories) as $category) {
            $progressIndicator->setMessage('Loading VOD category streams: ' . $category->category_name);
            $streams = $this->client->getVODStreams($category->category_id);

            $progressIndicator->setMessage('Processing VOD category streams: ' . $category->category_name);
            $categoryProgressBar = new ProgressBar($section3);
            foreach($categoryProgressBar->iterate($streams) as $stream) {
                $safeName = $this->streamNameSanitizer->sanitize($stream->name);
                $baseName = Path::join($directory, $safeName->getFirstLetter(), $safeName->getName(), $safeName->getName());
                $this->filesystem->dumpFile(
                    Path::join($baseName . '.strm'),
                    $this->client->getStreamUrl($stream->stream_id, 'movie'). '.' . $stream->container_extension
                );
                $this->filesystem->dumpFile(
                    Path::join($baseName . '.json'),
                    json_encode($stream, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
            }
        }

        $progressIndicator->finish('VOD streams processed.');

        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}