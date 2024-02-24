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
    name: 'xtream:series',
    description: 'Load Series streams from Xtream Server and create folder structure.',
    hidden: false
)]
class XtreamSeriesCommand extends Command
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

        $directory = Path::makeAbsolute('TV', $this->strmDirectory);

        $progressIndicator = new ProgressIndicator($section1);
        $progressIndicator->start('Loading Series categories');
        $categories = $this->client->getSeriesCategories('/^\|DE\|/');
        $progressIndicator->setMessage('Processing Series categories');

        $progressBar = new ProgressBar($section2);

        foreach($progressBar->iterate($categories) as $category) {
            $progressIndicator->setMessage('Loading Series category streams: ' . $category->category_name);
            $streams = $this->client->getSeries($category->category_id);

            $progressIndicator->setMessage('Processing Series category streams: ' . $category->category_name);
            $categoryProgressBar = new ProgressBar($section3);
            foreach($categoryProgressBar->iterate($streams) as $stream) {

                $series = $this->client->getSeriesInfo($stream->series_id);
                $safeName = $this->streamNameSanitizer->sanitize($stream->name);
                $baseName = Path::join($directory, $safeName->getFirstLetter(), $safeName->getName());

                foreach($series->episodes as $season => $episodes) {
                    foreach($episodes as $episode) {
                        $episodeName = sprintf('%s S%02dE%02d', $safeName->getName(), $season, $episode->episode_num);
                        $this->filesystem->dumpFile(
                            Path::join($baseName, 'Season ' . $season, $episodeName . '.strm'),
                            $this->client->getStreamUrl($episode->id, 'series') . '.' . $episode->container_extension
                        );
                        $this->filesystem->dumpFile(
                            Path::join($baseName, 'Season ' . $season, $episodeName . '.json'),
                            json_encode($episode, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        );
                    }
                }
            }
        }

        $progressIndicator->finish('Series streams processed.');

        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}