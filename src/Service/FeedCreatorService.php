<?php

namespace App\Service;

use FeedWriter\RSS2;

class FeedCreatorService
{
    private ?RSS2 $feed = null;

    public function __construct(
        string $title,
        private string $feedLink,
        string $description,
        string $feedImage,
        string $language,
        private readonly string $author,
        private readonly string $socialLink,
        private readonly string $contactEmail,
        private readonly FileManagerService $fileManagerService
    ) {
        $this->feed = new RSS2();
        $this->feed->setTitle($title);
        $this->feed->setLink($feedLink);
        $this->feed->setDescription($description);
        $this->feed->setImage($feedImage, $title, $feedLink);
        $this->feed->setChannelElement('language', $language);
        $this->feed->setDate(time());
    }

    public function addItem(
        string $title,
        string $link,
        string $description,
        string $pubDate
    ): void {
        $item = $this->feed->createNewItem();
        $item->setTitle($title);
        $item->setLink($this->feedLink . $link);
        $item->setDescription($description);
        $item->setDate($pubDate);
        $item->setAuthor(
            $this->author,
            $this->contactEmail,
            $this->socialLink
        );
        $item->setId($link, true);
        $this->feed->addItem($item);
    }

    public function write(): void
    {
        $feedContent = $this->feed->generateFeed();
        $this->fileManagerService->writeFeed($feedContent);
    }
}
