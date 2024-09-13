<?php

namespace Lucifier\Framework\Message;

/**
 * Simple message builder with simple template engine
 */
abstract class Message
{
    protected bool $parse = true;
    /**
     * @var bool message preview
     */
    protected bool $preview = false;

    /**
     * @var string message type for view
     */
    protected string $type = 'send';

    /**
     * @var array array of field's names for template engine
     */
    protected $fields = [];

    /**
     * @var string message template
     */
    protected $template = '';

    /**
     * Validates data against specified fields
     *
     * @param array $data Key-value array of data for message templating
     * @return bool Returns true if all specified fields are present in the data, false otherwise
     */
    private function checkData(array $data): bool
    {
        $missingFields = array_diff($this->fields, array_keys($data));

        return empty($missingFields);
    }

    /**
     * Get message type
     *
     * @return mixed|string
     */
    public function getType(): mixed
    {
        return $this->type;
    }

    /**
     * Get message preview
     *
     * @return bool
     */
    public function getPreview(): bool
    {
        return $this->preview;
    }

    /**
     * Compile template
     * @param $data array key:value array of data for message templating
     * @return void
     */
    private function compile(array $data): void
    {
        if ($this->parse === true) {
            preg_match_all('~\{{\s*(.+?)\s*\}}~is', $this->template, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $this->template = str_replace($match[0], $data[$match[1]], $this->template);
            }
        }
    }

    /**
     * Data validation and template compilation
     * @param $data array key:value array of data for message templating
     * @return string     compilation result
     */
    public function run(array $data = []): string
    {
        if ($this->checkData($data)) {
            $this->compile($data);

            return $this->template;
        }

        return '';
    }
}