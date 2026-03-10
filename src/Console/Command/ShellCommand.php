<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShellCommand extends AbstractRedisCommand
{
    private ?string $defaultIndex = null;

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('shell')
            ->setDescription('Start an interactive RediSearch shell');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>RediSearch Interactive Shell</info>');
        $output->writeln('Type "help" for available commands, "exit" to quit.');
        $output->writeln('Use "use <index>" to set a default index.');
        $output->writeln('');

        $globalOptions = [
            '--host' => $input->getOption('host'),
            '--port' => $input->getOption('port'),
            '--adapter' => $input->getOption('adapter'),
        ];

        $password = $input->getOption('password');
        if ($password !== null) {
            $globalOptions['--password'] = $password;
        }

        while (true) {
            $prompt = $this->defaultIndex !== null
                ? "redisearch ({$this->defaultIndex})> "
                : 'redisearch> ';

            $line = readline($prompt);

            if ($line === false) {
                $output->writeln('');
                break;
            }

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            readline_add_history($line);

            if ($line === 'exit' || $line === 'quit') {
                $output->writeln('Goodbye.');
                break;
            }

            if ($line === 'help') {
                $this->showHelp($output);
                continue;
            }

            if (str_starts_with($line, 'use ')) {
                $this->defaultIndex = trim(substr($line, 4));
                $output->writeln("Default index set to '{$this->defaultIndex}'.");
                continue;
            }

            $tokens = $this->tokenize($line);

            if (empty($tokens)) {
                continue;
            }

            $commandName = array_shift($tokens);

            try {
                $app = $this->getApplication();
                $command = $app->find($commandName);
            } catch (\Exception $e) {
                $output->writeln("<error>Unknown command: $commandName</error>");
                continue;
            }

            $args = array_merge(['command' => $commandName], $globalOptions);

            $definition = $command->getDefinition();

            // Inject default index for commands that need an 'index' or 'name' argument
            if ($this->defaultIndex !== null) {
                if ($definition->hasArgument('index') || $definition->hasArgument('name')) {
                    $argName = $definition->hasArgument('index') ? 'index' : 'name';
                    $needsIndex = true;

                    // Check if the user already provided the index in tokens
                    foreach ($tokens as $token) {
                        if (!str_starts_with($token, '-')) {
                            $needsIndex = false;
                            break;
                        }
                    }

                    if ($needsIndex) {
                        array_unshift($tokens, $this->defaultIndex);
                    }
                }
            }

            // Parse remaining tokens as positional args and options
            $positionalArgs = [];
            $parsedOptions = [];
            $i = 0;
            while ($i < count($tokens)) {
                $token = $tokens[$i];
                if (str_starts_with($token, '--')) {
                    $eqPos = strpos($token, '=');
                    if ($eqPos !== false) {
                        $parsedOptions[substr($token, 0, $eqPos)] = substr($token, $eqPos + 1);
                    } else {
                        $optionName = $token;
                        if (isset($tokens[$i + 1]) && !str_starts_with($tokens[$i + 1], '--')) {
                            $parsedOptions[$optionName] = $tokens[$i + 1];
                            $i++;
                        } else {
                            $parsedOptions[$optionName] = true;
                        }
                    }
                } else {
                    $positionalArgs[] = $token;
                }
                $i++;
            }

            // Map positional args to argument names
            $argDefinitions = $definition->getArguments();
            $argIndex = 0;
            foreach ($argDefinitions as $argDef) {
                if ($argDef->getName() === 'command') {
                    continue;
                }
                if ($argIndex < count($positionalArgs)) {
                    if ($argDef->isArray()) {
                        $args[$argDef->getName()] = array_slice($positionalArgs, $argIndex);
                        break;
                    }
                    $args[$argDef->getName()] = $positionalArgs[$argIndex];
                    $argIndex++;
                }
            }

            $args = array_merge($args, $parsedOptions);

            try {
                $arrayInput = new ArrayInput($args);
                $arrayInput->setInteractive(false);
                $command->run($arrayInput, $output);
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }

            $output->writeln('');
        }

        return self::SUCCESS;
    }

    private function showHelp(OutputInterface $output): void
    {
        $output->writeln('<info>Available commands:</info>');
        $output->writeln('  index:create <name> <schema-file>  Create an index from JSON schema');
        $output->writeln('  index:drop <name>                  Drop an index');
        $output->writeln('  index:list                         List all indexes');
        $output->writeln('  index:info <name>                  Show index information');
        $output->writeln('  document:add <index> <id> <f=v...> Add a document');
        $output->writeln('  document:get <index> <id>          Get a document');
        $output->writeln('  document:delete <index> <id>       Delete a document');
        $output->writeln('  search <index> <query>             Search an index');
        $output->writeln('  aggregate <index> [query]          Aggregate query');
        $output->writeln('  explain <index> <query>            Explain query plan');
        $output->writeln('  profile <index> <query>            Profile a query');
        $output->writeln('');
        $output->writeln('<info>Shell commands:</info>');
        $output->writeln('  use <index>                        Set default index');
        $output->writeln('  help                               Show this help');
        $output->writeln('  exit / quit                        Exit the shell');
    }

    /**
     * Tokenize input respecting quoted strings.
     */
    private function tokenize(string $input): array
    {
        $tokens = [];
        $current = '';
        $inQuote = null;
        $len = strlen($input);

        for ($i = 0; $i < $len; $i++) {
            $char = $input[$i];

            if ($inQuote !== null) {
                if ($char === $inQuote) {
                    $inQuote = null;
                } else {
                    $current .= $char;
                }
            } elseif ($char === '"' || $char === "'") {
                $inQuote = $char;
            } elseif ($char === ' ') {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $tokens[] = $current;
        }

        return $tokens;
    }
}
