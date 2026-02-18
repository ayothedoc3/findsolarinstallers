<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: LISTINGVIDEO.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Utils;

/**
 * @since 4.10.0
 */
class ListingVideo
{
    /**
     * Keys of available video services
     * @var array
     */
    public static $supportedServices = ['youtube', 'rutube'];

    /**
     * Add video by link
     * @param  string $link - Video url or embed code
     * @return array        - Video data
     */
    public static function add(string $link): array
    {
        $link = trim($link);

        preg_match('/https?\:\/\/([^\/]+)/', $link, $matches);

        if (!$matches[1]) {
            $GLOBALS['rlDebug']->logger('Unable to detect video service by the link: ' . $link);
            return [];
        }

        $video_host = str_replace('www.', '', $matches[1]);

        switch ($video_host) {
            case 'youtube.com':
            case 'youtu.be':
                return self::addYoutube($link);
                break;

            case 'rutube.ru':
                return self::addRutube($link);
                break;

            default:
                $GLOBALS['rlDebug']->logger("Unsupported video service '{$video_host}' received in link: {$link}");
                return [];
                break;
        }
    }

    /**
     * Add Youtube video by link
     * @param  string $link - Video url or embed code
     * @return array        - Video data
     */
    public static function addYoutube(string $link): array
    {
        preg_match('~(?:youtu\.be/|youtube\.com/(?:.*v=|v/|embed/|shorts/|live/))([\w-]{11})~', $link, $matches);

        if (!$video_id = $matches[1] ?? null) {
            $GLOBALS['rlDebug']->logger('Unable to retrieve youtube id from the link: ' . $link);
            return [];
        }

        $check_url = "https://www.youtube.com/oembed?format=json&url=https://www.youtube.com/watch?v=" . $video_id;
        $video = json_decode(Util::getContent($check_url), true);

        if (is_array($video) && $video['type'] == 'video') {
            return [
                'type' => 'youtube',
                'id' => $video_id,
                'title' => $video['title'],
            ];
        } else {
            $GLOBALS['rlDebug']->logger('No video data received from youtube API by the request: ' . $check_url);
            return [];
        }
    }

    /**
     * Add Rutube video by link
     * @param  string $link - Video url or embed code
     * @return array        - Video data
     */
    public static function addRutube(string $link): array
    {
        $video_id = '';

        if (0 === strpos($link, 'http')) {
            preg_match('/video\/([^\/]+)/', $link, $matches);
            $video_id = $matches[1];
        } else {
            preg_match('/embed\/([^\/]+)/', $link, $matches);
            $video_id = $matches[1];
        }

        if (!$video_id) {
            $GLOBALS['rlDebug']->logger('Unable to retrieve rutube id from the link: ' . $link);
            return [];
        }

        $check_url = "https://rutube.ru/api/video/" . $video_id;
        $video = json_decode(Util::getContent($check_url), true);

        if (is_array($video) && $video['video_url']) {
            return [
                'type' => 'rutube',
                'id' => $video_id,
                'title' => $video['title'],
            ];
        } else {
            $GLOBALS['rlDebug']->logger('No video data received from rutube API by the request: ' . $check_url);
            return [];
        }
    }
}
