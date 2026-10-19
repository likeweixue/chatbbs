<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 简单校验器
 */
class Validator
{
    private array $data = [];
    private array $rules = [];
    private array $errors = [];
    public array $validated = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleStr) {
            $rules = explode('|', $ruleStr);
            $value = $this->data[$field] ?? null;
            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, '');
                $this->apply($field, $name, $param, $value);
            }
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
        return empty($this->errors);
    }

    private function apply(string $field, string $name, string $param, $value): void
    {
        $has = $value !== null && $value !== '';
        switch ($name) {
            case 'required':
                if (!$has) {
                    $this->add($field, '不能为空');
                }
                break;
            case 'min':
                if ($has && mb_strlen((string)$value) < (int)$param) {
                    $this->add($field, '长度不能少于 ' . $param . ' 个字符');
                }
                break;
            case 'max':
                if ($has && mb_strlen((string)$value) > (int)$param) {
                    $this->add($field, '长度不能超过 ' . $param . ' 个字符');
                }
                break;
            case 'email':
                if ($has && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, '邮箱格式不正确');
                }
                break;
            case 'url':
                if ($has && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->add($field, '链接格式不正确');
                }
                break;
            case 'numeric':
                if ($has && !is_numeric($value)) {
                    $this->add($field, '必须为数字');
                }
                break;
            case 'integer':
                if ($has && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->add($field, '必须为整数');
                }
                break;
            case 'alpha_num':
                if ($has && !preg_match('/^[a-zA-Z0-9_]+$/', (string)$value)) {
                    $this->add($field, '只能包含字母、数字和下划线');
                }
                break;
            case 'same':
                if ((string)$value !== (string)($this->data[$param] ?? '')) {
                    $this->add($field, '两次输入不一致');
                }
                break;
            case 'in':
                $list = explode(',', $param);
                if ($has && !in_array((string)$value, $list, true)) {
                    $this->add($field, '取值不合法');
                }
                break;
            case 'regex':
                if ($has && !preg_match($param, (string)$value)) {
                    $this->add($field, '格式不正确');
                }
                break;
        }
    }

    private function add(string $field, string $msg): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $msg;
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        foreach ($this->errors as $msg) {
            return (string)$msg;
        }
        return '';
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
