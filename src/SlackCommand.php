<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;



class SlackCommand extends \Symfony\Component\Console\Command\Command
{
    protected static $defaultName = 'slack';

    protected function configure(): void
    {
        $this->setDescription("Send template slack messages to other slack users from the command line");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Create an object for styling
        $io = new SymfonyStyle($input, $output);
        $io->title('SLACK MESSAGE SENDER');

        //Set a variable to use as the condition for a while loop
        $exit = false;

        //Set question helper
        $helper = $this->getHelper('question');

        while (!$exit) {
            //Display main options and let the user select one
            $optionArray = array( 1 => 'Send a message', 2 => 'List templates', 3 => 'Add a template', 4 => 'Update a template', 5 => 'Delete a template', 6 => 'List users', 7 => 'Add a user', 8 => 'Show sent messages', 9 => 'Exit');
            $option = $this->choice_question($input, $output, "\nWhat would you like to do?", $optionArray, "Option %s is invalid");

            switch ($option) {
                case 'Send a message':
                    $io->section("\nSend a message");

                    //List templates and ask the user to select one
                    $templateArray =  $this->get_templates();
                    $selectedTemplate = $this->choice_question($input, $output, "What template?", $templateArray, "Template %s is invalid");

                    //List users and ask the user to select one
                    echo "What user?\n";
                    $this->list_users($input, $output);
                    $question = new Question("Select a user\n", 1);
                    $selectedKey = $helper->ask($input, $output, $question);

                    //Print the message to send
                    $userFile = new FileFinder('src/data', 'users.json');
                    $users = $userFile->find_file();

                    $selectedDisplayName = $users[$selectedKey-1]['displayName'];
                    echo "\nSending to @$selectedDisplayName:\n";

                    $placeholders = array("{name}", "{username}", "{displayName}");
                    $values = array($users[$selectedKey-1]['name'], $users[$selectedKey-1]['username'], $users[$selectedKey-1]['displayName']);
                    $messageToSend = str_replace($placeholders, $values, $selectedTemplate);
                    echo "\n$messageToSend\n";

                    //Grab the current time when the message is sent
                    $timeZone = 'America/Chicago';
                    $timestamp = time();
                    $dateTime = new \DateTime("now", new \DateTimeZone($timeZone));
                    $dateTime->setTimestamp($timestamp);
                    $messageDateTime = $dateTime->format(\DateTimeInterface::RFC2822);

                    //Ask a confirmation question if the user wants to send the message
                    $messageSendConfirmation = new ConfirmationQuestion("\n(enter 'y' to send)\n", false);

                    //If no, go back to the main menu

                    if (!$helper->ask($input, $output, $messageSendConfirmation)) {
                        $output->writeln("\n <fg=yellow>Going back to the main menu</>\n");
                        sleep( 1);
                        break;
                    }

                    //If yes, send & save the message
                    //Send the message
                    $yourname = "Heather";
                    $channel = "#aep-cookies";
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

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Message sent! Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'List templates':
                    $io->section("\nList templates");
                    //Get the list of templates
                    $templateArray = $this->get_templates();

                    //Create a green text format
                    $outputStyle = new OutputFormatterStyle('green');
                    $output->getFormatter()->setStyle('green', $outputStyle);

                    //Display the templates
                    foreach ($templateArray as $key => $value) {
                        $output->writeln( "  [<green>$key</>] $value");
                    }

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'Add a template':
                    $io->section("\nAdd a template");
                    echo "Available variables:\n* {name}\n* {username}\n* {displayName}\n";

                    //Get current templates in an array of arrays format
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file();

                    //Get a new template message from the user
                    $question = new Question("Enter your new template and press enter to save:\n", 'Hello!');
                    $newTemplate = $helper->ask($input, $output, $question);

                    //Create a new template object
                    $arrayToAdd = array('id' => count($templates) + 1, 'message' => $newTemplate);
                    $objectToAdd = (object)$arrayToAdd;

                    //Add the new array to $templates array
                    $templates[] = $objectToAdd;

                    //Replace the templates.json file with the updated file
                    $arrayUpload = new ArrayUploader('src/data/templates.json', $templates);
                    $arrayUpload->upload_array();

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Template added! Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'Update a template':
                    $io->section("\nUpdate a template");
                    //Get templates for the choice question
                    $templateArray = $this->get_templates();

                    //Ask the user to select the template to update
                    $selectedTemplate = $this->choice_question($input, $output, "What template do you want to update?", $templateArray, "Template %s is invalid");

                    //Find the key for the selected template value
                    $keyToUpdate = array_search($selectedTemplate, $templateArray);

                    //Ask the user to enter the new template message
                    $templateMessageQuestion = new Question("\nEnter your updated template and press enter to save: \n");
                    $newTemplate = $helper->ask($input, $output, $templateMessageQuestion);

                    //Update the template
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file(); #$templates is an array of arrays
                    $templates[$keyToUpdate - 1]['message'] = $newTemplate;

                    //Replace the current json file with the updated file
                    $arrayUpload = new ArrayUploader('src/data/templates.json', $templates);
                    $arrayUpload->upload_array();

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Template updated! Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'Delete a template':
                    $io->section("\nDelete a template");
                    //Get templates for the choice question
                    $templateArray = $this->get_templates();

                    //Ask the user to select a template to delete
                    $selectedTemplate = $this->choice_question($input, $output, "What template do you want to delete?", $templateArray, "Template %s is invalid");

                    //Find the key for the selected template value
                    $keyToDelete = array_search($selectedTemplate, $templateArray);
                    echo $keyToDelete;

                    //Ask for confirmation
                    $confirmation = new ConfirmationQuestion("\nAre you sure? Enter 'y'\n", false);

                    if (!$helper->ask($input, $output, $confirmation)) {
                        $output->writeln("\n <fg=yellow>Going back to the main menu</>\n");
                        sleep( 1);
                        break;
                    }

                    //Delete the selected template
                    $templateFile = new FileFinder('src/data', 'templates.json');
                    $templates = $templateFile->find_file();
                    unset($templates[$keyToDelete-1]);
                    $reindexedTemplates = array_values($templates);

                    //Replace the current json file with the updated file//
                    $arrayUpload = new ArrayUploader('src/data/templates.json', $reindexedTemplates);
                    $arrayUpload->upload_array();

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Template deleted! Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'List users':
                    $io->section("\nList users");

                    $this->list_users($input, $output);

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'Add a user':
                    $io->section("\nAdd a user");
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
                    $arrayUpload = new ArrayUploader('src/data/users.json', $users);
                    $arrayUpload->upload_array();

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>User added! Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'Show sent messages':
                    $io->section("\nShow sent messages");
                    //Get an array of sent messages
                    $messageFile = new FileFinder('src/data', 'messages.json');
                    $messages = $messageFile->find_file();

                    $newArray = array_map(static fn($arr) => array($arr['date'], $arr['message']), $messages);

                    //Print the message in a table format
                    $table = new Table($output);
                    $table->setHeaders(['Date', 'Message'])
                        ->setRows($newArray);
                    $table->render();

                    //Go back to the main menu
                    $output->writeln("\n <fg=yellow>Going back to the main menu</>\n");
                    sleep( 1);
                    break;
                case 'Exit':
                    $exit = true;
                    return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }


    protected function get_templates(): array
    {
        $templateFile = new FileFinder('src/data', 'templates.json');
        $templates = $templateFile->find_file(); #$templates is an array of arrays

        //Create an associative array
        $templateArray = array_column($templates, 'message');
        $keyArray = range(1, count($templateArray));

        return array_combine($keyArray, $templateArray);
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

    protected function list_users(InputInterface $input, OutputInterface $output){
        $helper = $this->getHelper('question');

        $userFile = new FileFinder('src/data', 'users.json');
        $users = $userFile->find_file(); #an array of arrays

        //Create an associative array
        $userArray = array_column($users, 'displayName');
        $keyArray = range(1, count($userArray));
        $updatedUserArray = array_combine($keyArray, $userArray);

        //Create a green text format
        $outputStyle = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('green', $outputStyle);

        $i = 0;

        while($i < ceil(count($updatedUserArray)/10)){
            $arr = array_slice($updatedUserArray, 10 * $i, 10, true);
            foreach ($arr as $key => $value) {
                $output->writeln("  [<green>$key</>] $value");
            }

            if ($i < floor(count($updatedUserArray)/10)) {
                $question = new Question("(enter 'm' for more, enter 's' to stop)", 'm');
                $more = $helper->ask($input, $output, $question);
                if ($more === 'm') {
                    $i++;
                } else if ($more === 's') {
                    break;
                }
            } else {
                break;
            }
        }

    }

    protected function choice_question(InputInterface $input, OutputInterface $output, String $question, array $array, String $errorMessage){
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            "$question\n",
            $array,
            1
        );
        $question->setErrorMessage("$errorMessage");

        return $helper->ask($input, $output, $question);
    }



}
