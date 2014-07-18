<?php namespace Adamlc\Tagver;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use vierbergenlars\SemVer\version;

class GreetCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tagver')
            ->setDescription('Automatically tag the next semantic version in git')
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                'Specify the release type (major, minor, patch or build)'
            )
            ->setHelp(sprintf(
                '%sAutomatically tag the next semantic version in git%s',
                PHP_EOL,
                PHP_EOL
            ));
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');

        //Check the type is valid
        if (! in_array($type, array('major', 'minor', 'patch', 'build'))) {
            $output->writeln('<error>You must specify the release type (major, minor, patch or build)</error>');
        }

        //Fetch the current version from git
        $process = new Process('git describe --tags --abbrev=0');
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Git repository invalid or doesn\'t contain any tags');

            return;
        }

        $oldVersion = new version($process->getOutput());

        //Incriment the new version
        $newVersion = $oldVersion->inc($type);

        //Incriment the version
        $output->writeln('<info>Tagging new version ' . $newVersion->getVersion() . ' from version ' . $oldVersion->getVersion() . '</info>');

        //Tag the version
        $process = new Process('git tag ' . $newVersion->getVersion());
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Failed to create new tag. Is your source up to date?</error>');

            return;
        }

        //Push the tags
        $output->writeln('<info>Pushing tags to Origin...</info>');

        $process = new Process('git push origin --tags');
        $process->run();

        if ($process->isSuccessful()) {
            $output->writeln('<info>Success!</info>');
        } else {
            $output->writeln('<error>Failed to push new tag to origin!</error>');
        }
    }
}
