<?php

namespace Ehann\RediSearch\Console\Command;

use Ehann\RediSearch\Console\AbstractRedisCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateCommand extends AbstractRedisCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('aggregate')
            ->setDescription('Run an aggregation query on a RediSearch index')
            ->addArgument('index', InputArgument::REQUIRED, 'Index name')
            ->addArgument('query', InputArgument::OPTIONAL, 'Search query', '*')
            ->addOption('group-by', null, InputOption::VALUE_REQUIRED, 'Group by field name')
            ->addOption('reduce', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Reduce function (func:field, e.g. avg:price, count)')
            ->addOption('sort-by', null, InputOption::VALUE_REQUIRED, 'Sort by field (field:ASC|DESC)')
            ->addOption('apply', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Apply expression (expression:alias)')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter expression')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit results (offset,count)')
            ->addOption('load', null, InputOption::VALUE_REQUIRED, 'Load fields (comma-separated)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexName = $input->getArgument('index');
        $query = $input->getArgument('query');

        $index = $this->createIndex($input, $indexName);
        $builder = $index->makeAggregateBuilder();

        // Load
        $load = $input->getOption('load');
        if ($load !== null) {
            $builder->load(explode(',', $load));
        }

        // Group by with reducers
        $groupBy = $input->getOption('group-by');
        $reducers = $input->getOption('reduce');

        if ($groupBy !== null) {
            if (empty($reducers)) {
                $builder->groupBy($groupBy);
            } else {
                $first = true;
                foreach ($reducers as $reducer) {
                    $parts = explode(':', $reducer);
                    $func = strtolower($parts[0]);
                    $field = $parts[1] ?? null;

                    if ($first) {
                        $builder->groupBy($groupBy);
                        $first = false;
                    }

                    match ($func) {
                        'avg' => $builder->avg($field),
                        'sum' => $builder->sum($field),
                        'min' => $builder->min($field),
                        'max' => $builder->max($field),
                        'count' => $builder->count(),
                        'count_distinct' => $builder->countDistinct($field),
                        'count_distinctish' => $builder->countDistinctApproximate($field),
                        'stddev' => $builder->standardDeviation($field),
                        'tolist' => $builder->toList($field),
                        'first_value' => $builder->firstValue($field),
                        default => throw new \InvalidArgumentException("Unknown reducer: $func"),
                    };
                }
            }
        }

        // Sort by
        $sortBy = $input->getOption('sort-by');
        if ($sortBy !== null) {
            $sortParts = explode(':', $sortBy);
            $builder->sortBy($sortParts[0], $sortParts[1] ?? 'ASC');
        }

        // Apply
        foreach ($input->getOption('apply') as $apply) {
            $pos = strrpos($apply, ':');
            if ($pos !== false) {
                $expression = substr($apply, 0, $pos);
                $alias = substr($apply, $pos + 1);
                $builder->apply($expression, $alias);
            }
        }

        // Filter
        $filter = $input->getOption('filter');
        if ($filter !== null) {
            $builder->filter($filter);
        }

        // Limit
        $limit = $input->getOption('limit');
        if ($limit !== null) {
            $limitParts = explode(',', $limit);
            if (count($limitParts) === 2) {
                $builder->limit((int) $limitParts[0], (int) $limitParts[1]);
            }
        }

        $result = $builder->search($query);

        $documents = $result->getDocuments();
        $count = $result->getCount();

        if ($input->getOption('json')) {
            $this->renderJson($output, [
                'count' => $count,
                'documents' => $documents,
            ]);
            return self::SUCCESS;
        }

        $output->writeln("Aggregation returned $count result(s).");

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
