<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BuddyBossPlatform\FFMpeg\Filters\Audio;

use BuddyBossPlatform\FFMpeg\Format\AudioInterface;
use BuddyBossPlatform\FFMpeg\Media\Audio;
class CustomFilter implements AudioFilterInterface
{
    /** @var string */
    private $filter;
    /** @var integer */
    private $priority;
    /**
     * A custom filter, useful if you want to build complex filters
     *
     * @param string $filter
     * @param int    $priority
     */
    public function __construct($filter, $priority = 0)
    {
        $this->filter = $filter;
        $this->priority = $priority;
    }
    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }
    /**
     * {@inheritdoc}
     */
    public function apply(Audio $audio, AudioInterface $format)
    {
        $commands = array('-af', $this->filter);
        return $commands;
    }
}
