<?php


final class TestRunner
{
    private string $testCasesDir;

    public function __construct(string $testCasesDir)
    {
        $this->testCasesDir = $testCasesDir;
    }

    public function run(?string $filter): int
    {
        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        $testCases = $this->discoverTestCases($filter);

        if (!$testCases) {
            if ($filter) {
                echo 'No test case name contains "' . $filter . '".' . PHP_EOL;
            } else {
                echo 'No test cases.' . PHP_EOL;
            }

            return 1;
        }

        $failures = [];

        foreach ($testCases as $testCase) {
            if (!$this->runTestCase($testCase)) {
                $failures[] = $testCase->displayName();
            }
        }

        if ($failures) {
            echo TestCase::color('31', 'FAILED test cases: ' . implode(', ', $failures)) . PHP_EOL;

            return 1;
        }

        echo TestCase::color('32', 'All test cases passed.') . PHP_EOL;

        return 0;
    }

    /**
     * @return TestCase[]
     */
    private function discoverTestCases(?string $filter): array
    {
        $files = glob($this->testCasesDir . '/*.php');
        sort($files);

        $testCases = [];

        foreach ($files as $file) {
            /** @var TestCase $prototype */
            $prototype = require $file;
            $class = get_class($prototype);

            foreach ($class::dataProvider() as $label => $data) {
                $testCase = new $class($label, $data);

                if ($filter === null || strpos($testCase->displayName(), $filter) !== false) {
                    $testCases[] = $testCase;
                }
            }
        }

        return $testCases;
    }

    private function runTestCase(TestCase $testCase): bool
    {
        try {
            return $testCase->run();
        } catch (Throwable $e) {
            echo 'FAIL: ' . $testCase->displayName() . ' - uncaught ' . $e . PHP_EOL;

            return false;
        }
    }

}
