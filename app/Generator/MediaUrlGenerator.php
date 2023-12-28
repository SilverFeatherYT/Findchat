<?php

namespace App\Generator;

use DateTimeInterface;
use Illuminate\Support\Str;

class MediaUrlGenerator extends \Spatie\MediaLibrary\Support\UrlGenerator\BaseUrlGenerator
{
    public function getUrl(): string
    {
        $url = $this->getDisk()->url($this->getPathRelativeToRoot());

        $url = $this->versionUrl($url);

        return str_replace('\\', '/', $url);
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = []): string
    {
        $url = $this->getDisk()->temporaryUrl($this->getPathRelativeToRoot(), $expiration, $options);

        return str_replace('\\', '/', $url);
    }

    public function getBaseMediaDirectoryUrl(): string
    {
        $url = $this->getDisk()->url('/');

        return str_replace('\\', '/', $url);
    }

    public function getPath(): string
    {
        return $this->getRootOfDisk() . $this->getPathRelativeToRoot();
    }

    public function getResponsiveImagesDirectoryUrl(): string
    {
        $path = $this->pathGenerator->getPathForResponsiveImages($this->media);

        $url = Str::finish($this->getDisk()->url($path), '/');

        return str_replace('\\', '/', $url);
    }

    protected function getRootOfDisk(): string
    {
        return $this->getDisk()->path('/');
    }
}
