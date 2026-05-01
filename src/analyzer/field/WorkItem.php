<?php

declare(strict_types=1);

namespace plugin\weight\analyzer\field;

use JsonSerializable;

/**
 * 单条作品数据对象。
 *
 * 来源：SDK FeedItemType。
 * 作用：把作品列表里的每条作品按统一结构输出给 Work.php，
 * 包含作品基础字段、互动统计、作者信息和标签信息。
 */
class WorkItem implements JsonSerializable
{
    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $feed
     */
    public function __construct(array $feed)
    {
        $total = is_array($feed['total'] ?? null) ? $feed['total'] : [];
        $author = is_array($feed['author'] ?? null) ? $feed['author'] : [];
        $tags = is_array($feed['tags'] ?? null) ? $feed['tags'] : [];

        $this->data = [
            'platform' => $this->string($feed, 'platform'),
            'type' => $this->string($feed, 'type', 'feed'),
            'item_id' => $this->string($feed, 'item_id'),
            'desc' => $this->string($feed, 'desc'),
            'create_time' => $this->integer($feed, 'create_time'),
            'duration' => $this->integer($feed, 'duration'),
            'cover_url' => $this->string($feed, 'cover_url'),
            'video_url' => $this->string($feed, 'video_url'),
            'share_url' => $this->string($feed, 'share_url'),
            'width' => $this->integer($feed, 'width'),
            'height' => $this->integer($feed, 'height'),
            'is_top' => (bool) ($feed['is_top'] ?? false),
            'total' => [
                'like_count' => $this->integer($total, 'like_count'),
                'comment_count' => $this->integer($total, 'comment_count'),
                'share_count' => $this->integer($total, 'share_count'),
                'collect_count' => $this->integer($total, 'collect_count'),
                'play_count' => $this->integer($total, 'play_count'),
            ],
            'author' => [
                'user_id' => $this->string($author, 'user_id'),
                'sec_user_id' => $this->string($author, 'sec_user_id'),
                'display_id' => $this->string($author, 'display_id'),
                'nickname' => $this->string($author, 'nickname'),
                'avatar_url' => $this->string($author, 'avatar_url'),
            ],
            'tags' => $this->tags($tags),
        ];
    }

    /**
     * @return array{like_count:int,comment_count:int,share_count:int,collect_count:int,play_count:int}
     */
    public function total(): array
    {
        return $this->data['total'];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string,mixed> $data
     */
    private function string(array $data, string $key, string $default = ''): string
    {
        return trim((string) ($data[$key] ?? $default));
    }

    /**
     * @param array<string,mixed> $data
     */
    private function integer(array $data, string $key): int
    {
        return max(0, (int) ($data[$key] ?? 0));
    }

    /**
     * @param array<int,mixed> $tags
     * @return list<array{tag_id:string,tag_name:string,level:int}>
     */
    private function tags(array $tags): array
    {
        $items = [];
        foreach ($tags as $tag) {
            if (!is_array($tag)) {
                continue;
            }
            $items[] = [
                'tag_id' => $this->string($tag, 'tag_id'),
                'tag_name' => $this->string($tag, 'tag_name'),
                'level' => $this->integer($tag, 'level'),
            ];
        }
        return $items;
    }
}
