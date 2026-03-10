<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('search')
            ->setDescription('Search a RediSearch index')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name')
            ->addArgument('query', InputArgument::REQUIRED, 'Search query')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit results (offset,count)', '0,10')
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, 'Sort by field (field:ASC|DESC)')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Return only these fields (comma-separated)')
            ->addOption('highlight', null, InputOption::VALUE_REQUIRED, 'Highlight fields (comma-separated)')
            ->addOption('scores', null, InputOption::VALUE_NONE, 'Include relevance scores')
            ->addOption('verbatim', null, InputOption::VALUE_NONE, 'Disable stemming')
            ->addOption('language', null, InputOption::VALUE_REQUIRED, 'Stemming language')
            ->addOption('dialect', null, InputOption::VALUE_REQUIRED, 'Query dialect version')
            ->addOption('numeric-filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Numeric filter (field:min:max)')
            ->addOption('tag-filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tag filter (field:val1,val2)')
            ->addOption('geo-filter', null, InputOption::VALUE_REQUIRED, 'Geo filter (field:lon:lat:radius:unit)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('index');
        $query = $input->getArgument('query');

        $index = $this->createIndex($input, $indexName);

        $builder = $index;

        // Limit
        $limit = $input->getOption('limit');
        $parts = explode(',', $limit);
        if (count($parts) === 2) {
            $builder = $builder->limit((int) $parts[0], (int) $parts[1]);
        }

        // Sort
        $sort = $input->getOption('sort');
        if ($sort !== null) {
            $sortParts = explode(':', $sort);
            $builder = $builder->sortBy($sortParts[0], $sortParts[1] ?? 'ASC');
        }

        // Return fields
        $fields = $input->getOption('fields');
        if ($fields !== null) {
            $builder = $builder->return(explode(',', $fields));
        }

        // Highlight
        $highlight = $input->getOption('highlight');
        if ($highlight !== null) {
            $builder = $builder->highlight(explode(',', $highlight));
        }

        // Scores
        if ($input->getOption('scores')) {
            $builder = $builder->withScores();
        }

        // Verbatim
        if ($input->getOption('verbatim')) {
            $builder = $builder->verbatim();
        }

        // Language
        $language = $input->getOption('language');
        if ($language !== null) {
            $builder = $builder->language($language);
        }

        // Dialect
        $dialect = $input->getOption('dialect');
        if ($dialect !== null) {
            $builder = $builder->dialect((int) $dialect);
        }

        // Numeric filters
        foreach ($input->getOption('numeric-filter') as $nf) {
            $nfParts = explode(':', $nf);
            if (count($nfParts) >= 3) {
                $builder = $builder->numericFilter($nfParts[0], (float) $nfParts[1], (float) $nfParts[2]);
            }
        }

        // Tag filters
        foreach ($input->getOption('tag-filter') as $tf) {
            $pos = strpos($tf, ':');
            if ($pos !== false) {
                $field = substr($tf, 0, $pos);
                $values = explode(',', substr($tf, $pos + 1));
                $builder = $builder->tagFilter($field, $values);
            }
        }

        // Geo filter
        $geoFilter = $input->getOption('geo-filter');
        if ($geoFilter !== null) {
            $gfParts = explode(':', $geoFilter);
            if (count($gfParts) >= 5) {
                $builder = $builder->geoFilter(
                    $gfParts[0],
                    (float) $gfParts[1],
                    (float) $gfParts[2],
                    (float) $gfParts[3],
                    $gfParts[4]
                );
            }
        }

        $result = $builder->search($query, true);

        $documents = $result->getDocuments();
        $count = $result->getCount();

        if ($input->getOption('json')) {
            $this->renderJson($output, [
                'count' => $count,
                'documents' => $documents,
            ]);
            return self::SUCCESS;
        }

        $output->writeln("Found $count result(s).");

        if (empty($documents)) {
            return self::SUCCESS;
        }

        $first = $documents[0];
        $headers = array_keys(is_array($first) ? $first : (array) $first);

        $rows = [];
        foreach ($documents as $doc) {
            $row = [];
            $docArray = is_array($doc) ? $doc : (array) $doc;
            foreach ($headers as $header) {
                $val = $docArray[$header] ?? '';
                $row[] = is_array($val) ? json_encode($val) : (string) $val;
            }
            $rows[] = $row;
        }

        $this->renderTable($output, $headers, $rows);

        return self::SUCCESS;
    }
}
