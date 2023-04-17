<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SlackCommand extends \Symfony\Component\Console\Command\Command
{
    protected static $defaultName = 'slack';

    protected function configure(): void
    {
        $this->setDescription("Send template slack messages to other slack users from the command line");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Set a variable to use as the condition for a while loop
        $keepGoing = true;

        while($keepGoing) {
            $helper = $this->getHelper('question');
            $choice = array( 1 => 'Send a message', 2 => 'List templates', 3 => 'Add a template', 4 => 'Update a template', 5 => 'Delete a template', 6 => 'List users', 7 => 'Add a user', 8 => 'Show sent messages', 9 => 'Exit');
            $question = new ChoiceQuestion(
                "\nWhat would you like to do?\n",
                $choice,
                1
            );
            $question->setErrorMessage('Option %s is invalid.');

            $option = $helper->ask($input, $output, $question);
            #echo "\n$option\n";
            switch ($option) {
                case 'Send a message':
                    echo "Send a message\n\n----------------------------------------------------------------------------\n";

                    //List templates
                    //Find the file and get content of the file
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file(); #$templates is an array of arrays

                    //Create an array for the choice question
                    $templateArray = array_column($templates, 'message', 'id');

                    //Choice question
                    $helper = $this->getHelper('question');
                    $questionTemplate = new ChoiceQuestion(
                        "\nWhat template?\n",
                        $templateArray,
                        1
                    );

                    $question->setErrorMessage('Template %s is invalid.');

                    $selectedTemplate = $helper->ask($input, $output, $questionTemplate);

                    //List users
                    //Find the file and get content of the file
                    $userFile = new FileFinder('src/data', 'users.json');
                    $users = $userFile->find_file(); #an array of arrays

                    //Create an array for the choice question
                    $userArray = array_column($users, 'displayName');
                    $keyArray = range(1, count($userArray));
                    $updatedUserArray = array_combine($keyArray, $userArray);

                    //Choice question
                    $helper = $this->getHelper('question');
                    $questionUser = new ChoiceQuestion(
                        "\nWhat user?\n",
                        $updatedUserArray,
                        1
                    );
                    $question->setErrorMessage('User %s is invalid.');

                    $selectedUser = $helper->ask($input, $output, $questionUser);

                    //Prints the message to send
                    echo "Sending to @$selectedUser:\n";
                    $messageToSend = str_replace("{displayName}", $selectedUser, $selectedTemplate);
                    echo "$messageToSend\n";

                    //Grab the current time when the message is sent
                    $timeZone = 'America/Chicago';
                    $timestamp = time();
                    $dateTime = new \DateTime("now", new \DateTimeZone($timeZone));
                    $dateTime->setTimestamp($timestamp);
                    $messageDateTime = $dateTime->format(\DateTimeInterface::RFC2822);

                    $helper = $this->getHelper('question');
                    $questionSend = new ConfirmationQuestion("\n(enter 'yes' to send)\n", false);

                    if (!$helper->ask($input, $output, $questionSend)) {

                        $questionReturn= new Question("\nenter 'm' for more\n", 'm');

                        $answer = $helper->ask($input, $output, $questionReturn);

                        if($answer === 'm'){
                            break;
                        }

                    }

                    //If yes, send & save the message
                    //Send the message
                    $yourname = "Heather";
                    $process = new Process(["curl", "-X", "POST", "-H", "Content-Type: application/json", "-d", "{\"channel\": \"#accelerated-engineer-program\", \"username\": \"$yourname\", \"text\": \"$messageToSend\", \"icon_emoji\": \":ghost:\"}", 'https://hooks.slack.com/services/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']);

                    $process->run();

                    // executes after the command finishes
                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }

                    echo $process->getOutput();

                    //Save the message
                    $messageFile = new FileFinder('src/data', 'messages.json');
                    $messages = $messageFile->find_file();

                    //Create a new message object
                    $arrayToAdd = array('id' => count($messages) + 1, 'message' => $messageToSend, 'date' => $messageDateTime);
                    $objectToAdd = (object)$arrayToAdd;

                    //Add the new array to $templates array
                    $messages[] = $objectToAdd;

                    //Replace the templates.json file with the updated file
                    $json = json_encode($messages);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile('src/data/messages.json', $json);

                    break;
                case 'List templates':
//                    echo "List templates\n\n";
//                    $templateFile = new FileFinder('src/data', 'templates.json');
//                    $templates = $templateFile->find_file();
//
//                    //Create an associative array
//                    $templateArray = array_column($templates, 'message', 'id');
//
//                    foreach ($templateArray as $key => $value) {
//                        echo "  [$key] $value\n";
//                    }
                    $this->list_templates();

                    $questionReturn= new Question("\nenter 'm' for more\n", 'm');

                    $answer = $helper->ask($input, $output, $questionReturn);

                    if($answer === 'm'){
                        break;
                    }
                    #break;
                case 'Add a template':
                    echo "Add a template\n\n";
                    echo "Available variables:\n* {name}\n* {username}\n* {displayName}\n";

                    //Save the new template as a variable
                    $question = new Question("Enter your new template and press enter to save:\n", 'Hello!');
                    $newTemplate = $helper->ask($input, $output, $question);

                    //Get the list of templates
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file();

                    //Create a new template object
                    $arrayToAdd = array('id' => count($templates) + 1, 'message' => $newTemplate);
                    $objectToAdd = (object)$arrayToAdd;

                    //Add the new array to $templates array
                    $templates[] = $objectToAdd;

                    //Replace the templates.json file with the updated file
                    $json = json_encode($templates);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile('src/data/templates.json', $json);
                    break;
                case 'Update a template':
                    echo "Update a template\n\n";
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file(); #$templates is an array of arrays

                    //create an array for choiceQuestion
                    $templateArray = array_column($templates, 'message', 'id');

                    //Question to select the template to update
                    $helper = $this->getHelper('question');

                    $question = new ChoiceQuestion(
                        "\nWhat template do you want to update?\n",
                        $templateArray,
                        1
                    );

                    $question->setErrorMessage('Template % is invalid.');

                    $selectedTemplate = $helper->ask($input, $output, $question);

                    //Find the key for the selected template value
                    $keyToUpdate = array_search($selectedTemplate, $templateArray);

                    //Question to get the new template to replace the selected template
                    $questionNewTemplate = new Question("\nEnter your updated template and press enter to save: \n");
                    $newTemplate = $helper->ask($input, $output, $questionNewTemplate);

                    //Update the template
                    $templates[$keyToUpdate - 1]['message'] = $newTemplate;

                    //Replace the current json file with the updated file
                    $json = json_encode($templates);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile('src/data/templates.json', $json);

                    break;
                case 'Delete a template':
                    echo "Delete a template\n\n";

                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file();

                    //Create an associative array for choiceQuestion
                    $templateArray = array_column($templates, 'message', 'id');

                    //Question to select the template to delete
                    $helper = $this->getHelper('question');

                    $question = new ChoiceQuestion(
                        "\nWhat template do you want to delete?\n",
                        $templateArray,
                        1
                    );

                    $question->setErrorMessage('Template %s is invalid.');

                    $selectedTemplate = $helper->ask($input, $output, $question);

                    //Find the key for the selected template value
                    $keyToDelete = array_search($selectedTemplate, $templateArray);

                    $confirmation = new ConfirmationQuestion("\nAre you sure?\n", false);

                    if (!$helper->ask($input, $output, $confirmation)) {
                        echo "go back to the first interface";
                    }

                    //Delete the selected template
                    unset($templates[$keyToDelete]);

                    #print_r($templates);
                    //Replace the current json file with the updated file
                    $json = json_encode($templates);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile('src/data/templates.json', $json);
                    break;
                case 'List users':
                    echo "List users\n\n";
                    //Find the file and get content of the file
                    $userFile = new FileFinder('src/data', 'users.json');
                    $users = $userFile->find_file(); #an array of arrays

                    //Create an array for display
                    $userArray = array_column($users, 'displayName');
                    $keyArray = range(1, count($userArray));
                    $updatedUserArray = array_combine($keyArray, $userArray);

                    foreach ($updatedUserArray as $key => $value) {
                        echo "  [$key] $value\n";
                    }

                    break;
                case 'Add a user':
                    echo "Add a user\n\n";

                    $nameQuestion = new Question("\nEnter the user's name: ", "name");
                    $name = $helper->ask($input, $output, $nameQuestion);

                    $idQuestion = new Question("\nEnter the user's ID: ", "ID");
                    $userId = $helper->ask($input, $output, $idQuestion);

                    $usernameQuestion = new Question("\nEnter the user's username: ", "username");
                    $username = $helper->ask($input, $output, $usernameQuestion);

                    $displayNameQuestion = new Question("\nEnter the user's display name: ", "display name");
                    $displayName = $helper->ask($input, $output, $displayNameQuestion);

                    //Get an array of users
                    $userFile = new FileFinder('src/data', 'users.json');
                    $users = $userFile->find_file(); #an array of arrays

                    //Create a user template object
                    $arrayToAdd = array('name' => $name, 'userID' => $userId, 'username' => $username, 'displayName' => $displayName);
                    $objectToAdd = (object)$arrayToAdd;

                    //Add the new array to $templates array
                    $users[] = $objectToAdd;

                    //Replace the templates.json file with the updated file
                    $json = json_encode($users);
                    $filesystem = new Filesystem();
                    $filesystem->dumpFile('src/data/users.json', $json);

                    break;
                case 'Show sent messages':
                    echo "\nShow sent messages\n";

                    $messageFile = new FileFinder('src/data', 'messages.json');
                    $messages = $messageFile->find_file();

                    $newArray = array_map(static fn($arr) => array($arr['date'], $arr['message']), $messages);
                    print_r($newArray);


                    $table = new Table($output);
                    $table->setHeaders(['Date', 'Message'])
                        ->setRows($newArray);

                    $table->render();

                    break;
                case 'Exit':
                    $keepGoing = false;
                    return Command::SUCCESS;

            }
        }

        return Command::SUCCESS;
    }

    protected function list_templates(){
        echo "List templates\n\n";
        $templateFile = new FileFinder('src/data', 'templates.json');
        $templates = $templateFile->find_file();

        //Create an associative array
        $templateArray = array_column($templates, 'message', 'id');

        foreach ($templateArray as $key => $value) {
            echo "  [$key] $value\n";
        }
    }


}
