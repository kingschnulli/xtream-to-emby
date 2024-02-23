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
    name: 'xtream:m3u',
    description: 'Load Live TV streams from Xtream Server and create m3u file.',
    hidden: false
)]
class XtreamM3UCommand extends Command
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

        $directory = Path::makeAbsolute('M3U', $this->strmDirectory);
        $filename = Path::makeAbsolute('xtream.m3u', $directory);

        if ($this->filesystem->exists($filename)) {
            $this->filesystem->remove($filename);
        }

        $this->filesystem->appendToFile($filename, '#EXTM3U' . PHP_EOL);

        $progressIndicator = new ProgressIndicator($section1);
        $progressIndicator->start('Loading LiveTV categories');
        $categories = $this->client->getLiveCategories('/^DE /');
        $progressIndicator->setMessage('Processing LiveTV categories');

        $progressBar = new ProgressBar($section2);

        foreach($progressBar->iterate($categories) as $category) {
            $progressIndicator->setMessage('Loading LiveTV category streams: ' . $category->category_name);
            $streams = $this->client->getLiveStreams($category->category_id);

            $progressIndicator->setMessage('Processing LiveTV category streams: ' . $category->category_name);
            $categoryProgressBar = new ProgressBar($section3);
            foreach($categoryProgressBar->iterate($streams) as $stream) {
                $line = '#EXTINF:-1 tvg-id="' . $stream->epg_channel_id . '" tvg-name="' . $stream->name . '" tvg-logo="' . $stream->stream_icon . '" group-title="' . $category->category_name . '",' . $stream->name . PHP_EOL;
                $line .= $this->client->getStreamUrl($stream->stream_id) . PHP_EOL;

                $this->filesystem->appendToFile($filename, $line);
            }
        }

        $progressIndicator->finish('LiveTV streams processed.');

        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}