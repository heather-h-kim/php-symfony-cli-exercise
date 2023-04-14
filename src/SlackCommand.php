<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;

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
        $choice = array( 1 => 'Send a message', 2 => 'List templates', 3 => 'Add a template', 4 => 'Update a template', 5 => 'Delete a template', 6 => 'List users', 7 => 'Add a user', 8 => 'Show sent messages', 9 => 'Exit');
        $question = new ChoiceQuestion(
            'What would you like to do?',
            // choices can also be PHP objects that implement __toString() method
            $choice,
            0
        );
        $question->setErrorMessage('Option %s is invalid.');

        $option = $helper->ask($input, $output, $question);

        switch ($option) {
            case 'Send a message':
                echo "Send a message\n\n----------------------------------------------------------------------------\n";

                #List templates

                $templateFile = new FileFinder('src/data', 'templates.json');
                $templates = $templateFile->find_file();
                $templatesArray = array_map(static fn($arr) => $arr['message'], $templates);

                $helper = $this->getHelper('question');

                $question = new ChoiceQuestion(
                    "\nWhat template?",
                    $templatesArray,
                    1
                );

               $question->setErrorMessage('Template %s is invalid.');

               $selectedTemplate = $helper->ask($input, $output, $question);


                #List users

                $userFile = new FileFinder('src/data', 'users.json');
                $users = $userFile->find_file();
                $usersArray = array_map(static fn($arr) => $arr['displayName'], $users);

                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    "\nWhat user?",
                    $usersArray,
                    1
                );
                $question->setErrorMessage('User %s is invalid.');

                $selectedUser = $helper->ask($input, $output, $question);
                echo "Sending to @$selectedUser:\n";
                echo str_replace("{displayName}", $selectedUser, $selectedTemplate);
                echo "\n(enter 'yes' to send)\n";

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
