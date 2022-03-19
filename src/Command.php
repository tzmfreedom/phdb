<?php

namespace PHPSimpleDebugger;

class Command
{
    private string $commandString;

    /**
     * @param string $input
     * @param int $transactionID
     */
    function __construct(string $input, int $transactionID)
    {
        $this->commandString = $this->getCommandString($input, $transactionID);
    }

    /**
     * @param string $input
     * @param int $transactionID
     * @return string
     */
    private function getCommandString(string $input, int $transactionID): string
    {
        if ($input === '') {
            return '';
        }
        $args = preg_split('/\s+/', $input);
        $command = $args[0];
        if (in_array($command, ["n", "next"], true)) {
            return "step_over -i $transactionID";
        }
        if (in_array($command, ["c", "continue"], true)) {
            return "run -i $transactionID";
        }
        if (in_array($command, ["s", "step"], true)) {
            return "step_into -i $transactionID";
        }
        if (in_array($command, ["f", "finish"], true)) {
            return "step_out -i $transactionID";
        }
//        if (in_array($command, ["status"], true)) {
//            return "status -i $transactionID";
//        }
        if (in_array($command, ["source"], true)) {
            return "source -i $transactionID";
        }
        if (in_array($command, ["stdout"], true)) {
            return "stdout -i $transactionID -c 1";
        }
        if (in_array($command, ['b', 'break'], true)) {
            if ($args[1] === 'exception') {
                $exception = $args[2];
                return "breakpoint_set -i $transactionID -t exception -x $exception";
            }
            if ($args[1] === 'call') {
                $function = $args[2];
                return "breakpoint_set -i $transactionID -t call -m $function";
            }
            if ($args[1] === 'return') {
                $function = $args[2];
                return "breakpoint_set -i $transactionID -t return -m $function";
            }
            [$file, $lineno] = preg_split('/:/', $args[1]);
            return "breakpoint_set -i $transactionID -t line -f $file -n $lineno";
        }
        if (in_array($command, ['breakpoint_set'], true)) {
            $expression = base64_encode($args[1]);
            return "breakpoint_set -i $transactionID -t exception -x Exception";
        }
        if (in_array($command, ['delete'], true)) {
            $id = $args[1];
            return "breakpoint_remove -i $transactionID -d $id";
        }
        if (in_array($command, ['info'], true)) {
            $subCommand = $args[1];
            if ($subCommand === 'break') {
                return "breakpoint_list -i $transactionID";
            }
            return '';
        }
        if (in_array($command, ["bt", "backtrace"], true)) {
            return "stack_get -i $transactionID";
        }
        if (in_array($command, ["l", "local"], true)) {
            return "context_get -i $transactionID -c 0";
        }
        if (in_array($command, ["sg", "super_global"], true)) {
            return "context_get -i $transactionID -c 1";
        }
        if (in_array($command, ["cn", "constants"], true)) {
            return "context_get -i $transactionID -c 2";
        }
        $b64encoded = base64_encode($input);
        return "eval -i $transactionID -d 1 -- ${b64encoded}";
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->commandString !== '';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->commandString;
    }
}
