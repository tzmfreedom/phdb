<?php

namespace PHPSimpleDebugger;

class Command
{
    private string $commandString;

    /**
     * @param string $input
     * @param int $transaction_id
     */
    function __construct(string $input, int $transaction_id)
    {
        $this->commandString = $this->getCommandString($input, $transaction_id);
    }

    /**
     * @param string $input
     * @param int $transaction_id
     * @return string
     */
    private function getCommandString(string $input, int $transaction_id): string
    {
        $args = preg_split('/\s+/', $input);
        $command = $args[0];
        if (in_array($command, ["n", "next"], true)) {
            return "step_over -i $transaction_id";
        }
        if (in_array($command, ["c", "continue"], true)) {
            return "run -i $transaction_id";
        }
        if (in_array($command, ["s", "step"], true)) {
            return "step_into -i $transaction_id";
        }
        if (in_array($command, ["status"], true)) {
            return "status -i $transaction_id";
        }
        if (in_array($command, ["source"], true)) {
            return "source -i $transaction_id";
        }
        if (in_array($command, ["stdout"], true)) {
            return "stdout -i $transaction_id -c 1";
        }
        if (in_array($command, ["breakpoint_set"], true)) {
            $file = $args[1];
            $lineno = $args[2];
            return "breakpoint_set -i $transaction_id -t line -f $file -n $lineno";
        }
        if (in_array($command, ["breakpoint_get"], true)) {
            $id = $args[1];
            return "breakpoint_get -i $transaction_id -d $id";
        }
        if (in_array($command, ["breakpoint_list"], true)) {
            return "breakpoint_list -i $transaction_id";
        }
        if (in_array($command, ["w", "whereami"], true)) {
            return "";
        }
        if (in_array($command, ["context_get"], true)) {
            return "context_get -i $transaction_id";
        }
        $b64encoded = base64_encode($input);
        return "eval -i $transaction_id -d 1 -- ${b64encoded}";
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
