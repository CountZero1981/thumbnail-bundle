<?php
// src/Message/SmsNotification.php

namespace Basilicom\ThumbnailBundle\MessageHandler;

use Basilicom\ThumbnailBundle\Message\ThumbnailJob;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ThumbnailConfig;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ThumbnailHandler implements MessageHandlerInterface
{
    public function __invoke(ThumbnailJob $message)
    {
        Logger::debug('BTB Received ThumbnailJob with assetId '.$message->getAssetId());

        $asset = Asset::getById($message->getAssetId(), true);

        if (! $asset instanceof Asset\Image) {
            return;
        }

        // clear placeholder
        $asset->clearThumbnail(ThumbnailConfig::getPreviewConfig()->getName());

        // system thumbnail format
        $path = $asset->getThumbnail(ThumbnailConfig::getPreviewConfig())->getFileSystemPath();

        Logger::debug('BTB Generated thumbnail [system] for asset ID '
            . $message->getAssetId()
            . ' Path:' .$path);

        // thumbnail formats via CSV asset property "thumbnailConfig":
        $thumbnailConfigs = $asset->getProperty('thumbnailConfig');
        $thumbnailConfigList = explode(',', $thumbnailConfigs);
        foreach ($thumbnailConfigList as $thumbnailConfigName) {

            $thumbnailConfig = ThumbnailConfig::getByName($thumbnailConfigName);
            if (is_object($thumbnailConfig)) {
                $asset->clearThumbnail($thumbnailConfig->getName());
                $path = $asset->getThumbnail($thumbnailConfig)->getFileSystemPath();
                Logger::debug('BTB Generated thumbnail ['.$thumbnailConfigName.'] for assetId '
                    . $message->getAssetId()
                    . ' Path:' .$path);
            }
        }

    }
}
