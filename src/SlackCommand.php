<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
            "\nWhat would you like to do?\n",
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
                $keyArray = range(1, count($templatesArray));

                $templatesNewKey = array_combine($keyArray, $templatesArray);

                $helper = $this->getHelper('question');

                $question = new ChoiceQuestion(
                    "\nWhat template?\n",
                    $templatesNewKey,
                    1
                );

               $question->setErrorMessage('Template %s is invalid.');

               $selectedTemplate = $helper->ask($input, $output, $question);


                #List users

                $userFile = new FileFinder('src/data', 'users.json');
                $users = $userFile->find_file();
                $usersArray = array_map(static fn($arr) => $arr['displayName'], $users);
                $keyArray = range(1, count($usersArray));
                $usersNewKey = array_combine($keyArray, $usersArray);

                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    "\nWhat user?\n",
                    $usersNewKey,
                    1
                );
                $question->setErrorMessage('User %s is invalid.');

                $selectedUser = $helper->ask($input, $output, $question);
                echo "Sending to @$selectedUser:\n";
                echo str_replace("{displayName}", $selectedUser, $selectedTemplate);
                echo "\n(enter 'yes' to send)\n";

                break;
            case 'List templates':
                echo "List templates\n\n";
                $templateFile = new FileFinder('src/data', 'templates.json');
                $templates = $templateFile->find_file();
                $templatesArray = array_map(static fn($arr) => $arr['message'], $templates);

                foreach($templatesArray as $key => $value ){
                    $newKey = $key +1;
                    echo "  [$newKey] $value";
                }
                break;
            case 'Add a template':
                echo "Add a template\n\n";
                echo "Available variables:\n* {name}\n* {username}\n* {displayName}\n";

                $question = new Question("Enter your new template and press enter to save:\n", 'Hello!');
                $newTemplate = $helper->ask($input, $output, $question);

                break;
            case 'Update a template':
                echo "Update a template\n\n";
                $templateFile = new FileFinder('src/data', 'templates.json');
                $templates = $templateFile->find_file();
                $templatesArray = array_map(static fn($arr) => $arr['message'], $templates);
                $keyArray = range(1, count($templatesArray));

                $templatesNewKey = array_combine($keyArray, $templatesArray);


                $helper = $this->getHelper('question');

                $question = new ChoiceQuestion(
                    "\nWhat template do you want to update?\n",
                    $templatesNewKey,
                    1
                );

                $question->setErrorMessage('User %s is invalid.');

                $selectedTemplate = $helper->ask($input, $output, $question);


                break;
            case 'Delete a template':
                echo "Delete a template\n\n";

                $templateFile = new FileFinder('src/data', 'templates.json');
                $templates = $templateFile->find_file();
                $templatesArray = array_map(static fn($arr) => $arr['message'], $templates);
                $keyArray = range(1, count($templatesArray));

                $templatesNewKey = array_combine($keyArray, $templatesArray);


                $helper = $this->getHelper('question');

                $question = new ChoiceQuestion(
                    "\nWhat template do you want to delete?\n",
                    $templatesNewKey,
                    1
                );

                $question->setErrorMessage('User %s is invalid.');

                $selectedTemplate = $helper->ask($input, $output, $question);

                $confirmation = new ConfirmationQuestion("\nAre you sure?\n", false);

                if(!$helper->ask($input, $output, $confirmation)){
                    return Command::SUCCESS;
                }

                echo "Delete the template";
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
