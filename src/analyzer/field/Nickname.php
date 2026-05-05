<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

/**
 * 昵称字段。
 *
 * 来源：用户信息 nickname。
 * 作用：检查账号名称是否完整，昵称为空或过短会影响账号识别度。
 */
class Nickname extends AbstractField
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 10;

    private const PLACEHOLDER_NAMES = [
        'test',
        'demo',
        'admin',
        'user',
        'nickname',
        'null',
        'undefined',
        'unknown',
        '用户',
        '新用户',
        '默认昵称',
        '匿名用户',
        '无名',
        '未知',
    ];

    public function key(): string
    {
        return 'nickname';
    }

    public function label(): string
    {
        return '昵称';
    }

    public function value(): string
    {
        return $this->stringValue();
    }

    public function tips(): array
    {
        $nickname = $this->value();
        if ($nickname === '') return ['昵称为空，账号识别度较弱'];

        $tips = [];
        $length = mb_strlen($nickname);

        if ($length < self::MIN_LENGTH) {
            $tips[] = '昵称过短，建议至少使用 2 个有效字符';
        }

        if ($length > self::MAX_LENGTH) {
            $tips[] = '昵称偏长，建议控制在 10 个字符以内';
        }

        if ($this->containsEmoji($nickname)) {
            $tips[] = '昵称包含表情符号，建议使用稳定的文字名称';
        }

        if ($this->isDefaultNickname($nickname)) {
            $tips[] = '昵称像系统默认名称，建议改成有明确定位的名称';
        }

        if ($this->isAllDigits($nickname)) {
            $tips[] = '昵称全是数字，容易被识别为默认或低辨识度账号';
        }

        if ($this->isAllEnglish($nickname)) {
            $tips[] = '昵称全是英文，中文平台识别度偏弱';
        }

        if ($this->isEnglishDigitMix($nickname)) {
            $tips[] = '昵称由英文和数字组合，辨识度偏弱';
        }

        if ($this->isPlaceholderName($nickname)) {
            $tips[] = '昵称像临时占位名称，建议改成真实账号定位';
        }

        if ($this->containsPhoneNumber($nickname)) {
            $tips[] = '昵称包含手机号，建议避免暴露隐私信息';
        }

        if ($this->isMostlySymbols($nickname)) {
            $tips[] = '昵称几乎全是符号，账号识别度较弱';
        }

        if ($this->hasRepeatedCharacters($nickname)) {
            $tips[] = '昵称存在过多重复字符，建议改得更具体';
        }

        if ($this->containsWhitespace($nickname)) {
            $tips[] = '昵称包含空格，建议使用连续易记的名称';
        }

        return $tips === [] ? ['昵称完整，有利于账号识别'] : $tips;
    }

    private function containsEmoji(string $nickname): bool
    {
        return preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $nickname) === 1;
    }

    private function isAllEnglish(string $nickname): bool
    {
        return preg_match('/^[A-Za-z]+$/', $nickname) === 1;
    }

    private function isAllDigits(string $nickname): bool
    {
        return preg_match('/^\p{N}+$/u', $nickname) === 1;
    }

    private function isEnglishDigitMix(string $nickname): bool
    {
        return preg_match('/^(?=.*[A-Za-z])(?=.*\p{N})[A-Za-z\p{N}]+$/u', $nickname) === 1;
    }

    private function isDefaultNickname(string $nickname): bool
    {
        return preg_match('/^(?:用户|新用户|默认用户|匿名用户|抖音用户|快手用户|小红书用户|账号|user)[-_]?\d{3,}$/iu', $nickname) === 1;
    }

    private function isPlaceholderName(string $nickname): bool
    {
        return in_array(mb_strtolower($nickname), self::PLACEHOLDER_NAMES, true);
    }

    private function containsPhoneNumber(string $nickname): bool
    {
        return preg_match('/(?<!\d)1[3-9]\d{9}(?!\d)/', $nickname) === 1;
    }

    private function isMostlySymbols(string $nickname): bool
    {
        preg_match_all('/[\p{Han}A-Za-z\p{N}]/u', $nickname, $matches);

        $meaningfulCount = count($matches[0]);
        $symbolCount = mb_strlen($nickname) - $meaningfulCount;

        return $meaningfulCount === 0 || ($symbolCount >= 2 && $symbolCount > $meaningfulCount);
    }

    private function hasRepeatedCharacters(string $nickname): bool
    {
        return preg_match('/(.)\1{3,}/u', $nickname) === 1;
    }

    private function containsWhitespace(string $nickname): bool
    {
        return preg_match('/\s/u', $nickname) === 1;
    }
}
