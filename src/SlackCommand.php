<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SlackCommand extends \Symfony\Component\Console\Command\Command
{
    protected static $defaultName = 'slack';

    protected function configure(): void
    {
        $this->setDescription("Send template slack messages to other slack users from the command line");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $arr1 = range(1,9);
        $arr2 = array('Send a message', 'List templates', 'Add a template', 'Update a template', 'Delete a template', 'List users', 'Add a user', 'Show sent messages', 'Exit');
        $choice = array_combine($arr1, $arr2);
        $question = new ChoiceQuestion(
            'Please select your favorite color (defaults to red)',
            // choices can also be PHP objects that implement __toString() method
            $choice,
            0
        );
        $question->setErrorMessage('Option %s is invalid.');

        $option = $helper->ask($input, $output, $question);
        #$output->writeln('You have just selected: '.$option);
        #echo $option;

        switch ($option) {
            case 'Send a message':
                echo "You chose 1";
                break;
            case 'List templates':
                echo "You chose 2";
                break;
            case 'Add a template':
                echo "You chose 3";
                break;
            case 'Update a template':
                echo "You chose 4";
                break;
            case 'Delete a template':
                echo "You chose 5";
                break;
            case 'List users':
                echo "You chose 6";
                break;
            case 'Add a user':
                echo "You chose 7";
                break;
            case 'Show sent messages':
                echo "You chose 8";
                break;
            case 'Exit':
                echo "You chose 9";
                break;

        }


        return Command::SUCCESS;
    }
}
