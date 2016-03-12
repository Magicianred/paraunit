<?php

namespace Paraunit\Parser;

use Paraunit\Process\ProcessResultInterface;

class FailureParser extends AbstractParser implements JSONParserChainElementInterface
{
    const TAG = 'failure';
    const TITLE = 'failures';
    const PARSING_REGEX = '/(?:There (?:was|were) \d+ failures?:\n\n)((?:.|\n)+)(?=\nFAILURES)/';

    /**
     * @param ProcessResultInterface $process
     *
     * @return bool True if chain should continue
     */
    public function parsingFoundResult(ProcessResultInterface $process)
    {
        $this->storeParsedBlocks($process);

        return true;
    }
}
