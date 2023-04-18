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
//include 'webhookUrl.php';


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

        //Set question helper
        $helper = $this->getHelper('question');

        while ($keepGoing) {
            //Display main options and let the user select one
            $option = $this->list_main_options($input, $output);

            switch ($option) {
                case 'Send a message':
                    echo "Send a message\n\n----------------------------------------------------------------------------\n";

                    //List templates
                    $templateArray =  $this->get_templates();

                    $templatesQuestion = new ChoiceQuestion(
                        "\nWhat template?\n",
                        $templateArray,
                        1
                    );

                    $templatesQuestion->setErrorMessage('Template %s is invalid.');

                    $selectedTemplate = $helper->ask($input, $output, $templatesQuestion);

                    //List users
                    $userArray = $this->get_users();

                    $userQuestion = new ChoiceQuestion(
                        "\nWhat user?\n",
                        $userArray,
                        1
                    );

                    $userQuestion->setErrorMessage('User %s is invalid.');

                    $selectedUser = $helper->ask($input, $output, $userQuestion);

                    //Print the message to send
                    echo "\nSending to @$selectedUser:\n";
                    $messageToSend = str_replace("{displayName}", $selectedUser, $selectedTemplate);
                    echo "\n$messageToSend\n";

                    //Grab the current time when the message is sent
                    $timeZone = 'America/Chicago';
                    $timestamp = time();
                    $dateTime = new \DateTime("now", new \DateTimeZone($timeZone));
                    $dateTime->setTimestamp($timestamp);
                    $messageDateTime = $dateTime->format(\DateTimeInterface::RFC2822);

                    //Ask a confirmation question if the user wants to send the message
                    $messageSendConfirmation = new ConfirmationQuestion("\n(enter 'yes' to send)\n", false);

                    //If no, go back to the main interface
                    if (!$helper->ask($input, $output, $messageSendConfirmation)) {
                        $answer = $this->ask_for_more($input, $output);
                        if($answer === 'm'){
                            break;
                        }
                    }

                    //If yes, send & save the message
                    //Send the message
                    $yourname = "Heather";
                    $channel = "#accelerated-engineer-program";
                    $urlFile = new FileFinder('src/data', 'url.json');
                    $urlArray = $urlFile->find_file();
                    $webHookUrl = $urlArray['url'];

                    $process = new Process(["curl", "-X", "POST", "-H", "Content-Type: application/json", "-d", "{\"channel\": \"$channel\", \"username\": \"$yourname\", \"text\": \"$messageToSend\", \"icon_emoji\": \":ghost:\"}", "$webHookUrl"]);

                    $process->run();

                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }

                    echo $process->getOutput();

                    //Save the message to the file
                    //Get messages array
                    $messages = $this->get_messages(); #an array of arrays

                    //Create a new message object
                    $arrayToAdd = array('id' => count($messages) + 1, 'message' => $messageToSend, 'date' => $messageDateTime);
                    $objectToAdd = (object)$arrayToAdd;

                    //Add the new array to $messages array
                    $messages[] = $objectToAdd;

                    //Replace the templates.json file with the updated file
                    $arrayUpload = new ArrayUploader('src/data/messages.json', $messages);
                    $arrayUpload->upload_array();

                    //Ask the user to go back to the main interface before going back
                    $answer = $this->ask_for_more($input, $output);
                    if($answer === 'm'){
                        break;
                    }
                    break;
                case 'List templates':
                    echo "List templates\n\n";
                    $templateArray = $this->get_templates();
                    foreach ($templateArray as $key => $value) {
                        echo "  [$key] $value\n";
                    }

                    //Ask the user to go back to the main interface before going back
                    $answer = $this->ask_for_more($input, $output);
                    if($answer === 'm'){
                        break;
                    }
                    #break;
                case 'Add a template':
                    echo "Add a template\n\n";
                    echo "Available variables:\n* {name}\n* {username}\n* {displayName}\n";

                    //Get a new template from the user
                    $question = new Question("Enter your new template and press enter to save:\n", 'Hello!');
                    $newTemplate = $helper->ask($input, $output, $question);

                    //Get current templates in an array of arrays format
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file();

                    //Create a new template object
                    $arrayToAdd = array('id' => count($templates) + 1, 'message' => $newTemplate);
                    $objectToAdd = (object)$arrayToAdd;

                    //Add the new array to $templates array
                    $templates[] = $objectToAdd;

                    //Replace the templates.json file with the updated file
                    $arrayUpload = new ArrayUploader('src/data/templates.json');
                    $arrayUpload->upload_array();

                    //Ask the user to go back to the main interface before going back
                    $answer = $this->ask_for_more($input, $output);
                    if($answer === 'm'){
                        break;
                    }
                    #break;
                case 'Update a template':
                    echo "Update a template\n\n";
                    //Get an array of templates
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file(); #$templates is an array of arrays

                    //create an array for choiceQuestion
                    $templateArray = array_column($templates, 'message', 'id');

                    //Ask the user to select the template to update
                    #$helper = $this->getHelper('question');
                    $question = new ChoiceQuestion(
                        "\nWhat template do you want to update?\n",
                        $templateArray,
                        1
                    );

                    $question->setErrorMessage('Template % is invalid.');

                    $selectedTemplate = $helper->ask($input, $output, $question);

                    //Find the key for the selected template value
                    $keyToUpdate = array_search($selectedTemplate, $templateArray);

                    //Ask the user to enter the new template message
                    $templateMessageQuestion = new Question("\nEnter your updated template and press enter to save: \n");
                    $newTemplate = $helper->ask($input, $output, $templateMessageQuestion);

                    //Update the template
                    $templates[$keyToUpdate - 1]['message'] = $newTemplate;

                    //Replace the current json file with the updated file
                    $arrayUpload = new ArrayUploader('src/data/templates.json', $templates);
                    $arrayUpload->upload_array();

                    break;
                case 'Delete a template':
                    echo "Delete a template\n\n";
                    //Get templates for the choice question
                    $templateArray = $this->get_templates();
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file();

                    //Create an associative array for choiceQuestion
                    $templateArray = array_column($templates, 'message', 'id');


                    //Question to select the template to delete

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

                    //Replace the current json file with the updated file
//                    $json = json_encode($templates);
//                    $filesystem = new Filesystem();
//                    $filesystem->dumpFile('src/data/templates.json', $json);
//
                    $arrayUpload = new ArrayUploader('src/data/templates.json', $templates);
                    $arrayUpload->upload_array();
                    break;
                case 'List users':
                    echo "List users\n\n";
                    //Find the file and get content of the file
                    $userArray = $this->get_users();
                    foreach ($userArray as $key => $value) {
                        echo "  [$key] $value\n";
                    }

                    $answer = $this->ask_for_more($input, $output);
                    if($answer === 'm'){
                        break;
                    }
                case 'Add a user':
                    echo "Add a user\n\n";
                    //Get name, ID, username, and displayname of the new user
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

                    $arrayUpload = new ArrayUploader('src/data/users.json', $users);
                    $arrayUpload->upload_array();

                    break;
                case 'Show sent messages':
                    echo "\nShow sent messages\n";
                    //Get an array of sent messages
                    $messageFile = new FileFinder('src/data', 'messages.json');
                    $messages = $messageFile->find_file();

                    $newArray = array_map(static fn($arr) => array($arr['date'], $arr['message']), $messages);
                    print_r($newArray);

                    //Print the message in a table format
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

    protected function list_main_options(InputInterface $input, OutputInterface $output){
        $helper = $this->getHelper('question');
        $choice = array( 1 => 'Send a message', 2 => 'List templates', 3 => 'Add a template', 4 => 'Update a template', 5 => 'Delete a template', 6 => 'List users', 7 => 'Add a user', 8 => 'Show sent messages', 9 => 'Exit');
        $question = new ChoiceQuestion(
            "\nWhat would you like to do?\n",
            $choice,
            1
        );
        $question->setErrorMessage('Option %s is invalid.');

        return $helper->ask($input, $output, $question);
    }


    protected function get_templates(): array
    {
        $templateFile = new FileFinder('src/data', 'templates.json');
        $templates = $templateFile->find_file(); #$templates is an array of arrays

        //Create an associative array
        return array_column($templates, 'message', 'id');
    }

    protected function get_users(): array
    {
        $userFile = new FileFinder('src/data', 'users.json');
        $users = $userFile->find_file(); #an array of arrays

        //Create an associative array
        $userArray = array_column($users, 'displayName');
        $keyArray = range(1, count($userArray));

        return array_combine($keyArray, $userArray);
    }

    protected function get_messages(): array
    {
        $messageFile = new FileFinder('src/data', 'messages.json');
        return $messageFile->find_file();
    }

    protected function ask_for_more(InputInterface $input, OutputInterface $output){
        $helper = $this->getHelper('question');
        $returnQuestion= new Question("\nenter 'm' for more\n", 'm');

        return $helper->ask($input, $output, $returnQuestion);
    }









}
