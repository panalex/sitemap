<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\sitemap;

/**
 * File is a helper to create the site map XML files.
 * Example:
 *
 * ```php
 * use yii2tech\sitemap\File;
 *
 * $siteMapFile = new File();
 * $siteMapFile->writeUrl(['site/index']);
 * $siteMapFile->writeUrl(['site/contact'], ['priority' => '0.4']);
 * $siteMapFile->writeUrl('http://mydomain.com/mycontroller/myaction', [
 *     'lastModified' => '2012-06-28',
 *     'changeFrequency' => 'daily',
 *     'priority' => '0.7'
 * ]);
 * ...
 * $siteMapFile->close();
 * ```
 *
 * @see BaseFile
 * @see http://www.sitemaps.org/
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class File extends BaseFile
{
    // Check frequency constants:
    const CHECK_FREQUENCY_ALWAYS = 'always';
    const CHECK_FREQUENCY_HOURLY = 'hourly';
    const CHECK_FREQUENCY_DAILY = 'daily';
    const CHECK_FREQUENCY_WEEKLY = 'weekly';
    const CHECK_FREQUENCY_MONTHLY = 'monthly';
    const CHECK_FREQUENCY_YEARLY = 'yearly';
    const CHECK_FREQUENCY_NEVER = 'never';

    /**
     * @var array default options for [[writeUrl()]].
     */
    public $defaultOptions = [];

	protected $schema = [];

    /**
     * {@inheritdoc}
     */
    protected function afterOpen()
    {
        parent::afterOpen();
        $namespaces = ($this->isNews) ? ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' : '';
        $namespaces .= ($this->hasImages) ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '';
        $namespaces .= ($this->hasVideos) ? ' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"' : '';
        $this->write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"'.$namespaces.'>');
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeClose()
    {
        $this->write('</urlset>');
        parent::beforeClose();
    }

    /**
     * Writes the URL block into the file.
     * @param string|array $url page URL or params.
     * @param array $options options list, valid options are:
     * - 'lastModified' - string|int, last modified date in format Y-m-d or timestamp.
     *   by default current date will be used.
     * - 'changeFrequency' - string, page change frequency, the following values can be passed:
     *
     *   * always
     *   * hourly
     *   * daily
     *   * weekly
     *   * monthly
     *   * yearly
     *   * never
     *
     *   by default 'daily' will be used. You may use constants defined in this class here.
     * - 'priority' - string|float URL search priority in range 0..1, by default '0.5' will be used
     * @return int the number of bytes written.
     */
    public function writeUrl($url, array $options = [])
    {
        $this->incrementEntriesCount();

        if (!is_string($url)) {
            $url = $this->getUrlManager()->createAbsoluteUrl($url);
        }

        $xmlCode = '<url>';
        $xmlCode .= "<loc>{$url}</loc>";

        $options = array_merge(
            [
                'lastModified' => date('Y-m-d'),
                'changeFrequency' => self::CHECK_FREQUENCY_DAILY,
                'priority' => '0.5',
            ],
            $this->defaultOptions,
            $options
        );
        if (ctype_digit($options['lastModified'])) {
            $options['lastModified'] = date('Y-m-d', $options['lastModified']);
        }

        $xmlCode .= "<lastmod>{$options['lastModified']}</lastmod>";
        $xmlCode .= "<changefreq>{$options['changeFrequency']}</changefreq>";
        $xmlCode .= "<priority>{$options['priority']}</priority>";

        if(isset($options['news']))
        {
            $this->isNews = true;
            $xmlCode .= '<news:news>';
            $xmlCode .= '<news:publication>';
            $xmlCode .= '<news:name>' . $options['news']['name'] . '</news:name>';
            $xmlCode .= '<news:language>' . $options['news']['language'] .'</news:language>';
            $xmlCode .= '</news:publication>';
            $xmlCode .= '<news:genres>' . $options['news']['genres'] .'</news:genres>';
            $xmlCode .= '<news:publication_date>' .  $options['news']['publicationDate']  . '</news:publication_date>';
            $xmlCode .= '<news:title><![CDATA[' . trim($options['news']['title']) . ']]></news:title>';
            $xmlCode .= '<news:keywords><![CDATA[' . trim($options['news']['keywords']) . ']]></news:keywords>';
            $xmlCode .= '</news:news>';
        }
        if(isset($options['images']) && is_array($options['images']) && count($options['images']) > 0)
        {
            $this->hasImages = true;
            foreach($options['images'] as $image) {
                $xmlCode .= '<image:image>';
                if(isset($image['location']))   $xmlCode .= '   <image:loc><![CDATA[' . $image['location'] . ']]></image:loc>';
                if(isset($image['caption']))   $xmlCode .= '   <image:caption><![CDATA[' . $image['caption'] . ']]></image:caption>';
                if(isset($image['geoLocation']))   $xmlCode .= '   <image:geo_location>' . $image['geoLocation'] . '</image:geo_location>';
                if(isset($image['title']))   $xmlCode .= '   <image:title><![CDATA[' . $image['title'] . ']]></image:title>';
                if(isset($image['license']))   $xmlCode .= '   <image:license><![CDATA[' . $image['license'] . ']]></image:license>';
                $xmlCode .= '</image:image>';
            }
        }
        if(isset($options['video']) && is_array($options['video']) && count($options['video']) > 0)
        {
            $this->hasVideos = true;
            $types = ['duration','expiration_date', 'rating', 'view_count', 'publication_date', 'family_friendly', 'tag', 'category', 'restriction',
                'gallery_loc', 'price', 'requires_subscription', 'uploader', 'platform', 'live', ];
            foreach($options['video'] as $video) {
                $xmlCode .= '<video:video>';
                $xmlCode .= '   <video:thumbnail_loc><![CDATA[' . $video['thumbnail_loc'] . ']]></video:thumbnail_loc>';
                $xmlCode .= '   <video:title><![CDATA[' . $video['title'] . ']]></video:title>';
                $xmlCode .= '   <video:description><![CDATA[' . $video['description'] . ']]></video:description>';
                if(!isset($video['player_loc']))   $xmlCode .= '   <video:content_loc><![CDATA[' . $video['content_loc'] . ']]></video:content_loc>';
                elseif(!isset($video['content_loc']))   $xmlCode .= '   <video:player_loc><![CDATA[' . $video['player_loc'] . ']]></video:player_loc>';
                foreach ($types as $type) {
                    if(isset($video[$type]))   $xmlCode .= '   <video:' .$type. '><![CDATA[' . $video[$type] . ']]></video:' .$type. '>';
                }
                $xmlCode .= '</video:video>';
            }
        }
        if(isset($options['alternate']))
        {
            if (isset($options['alternate']['url'])) $options['alternate'] = [$options['alternate']];
            $options['alternate'] = (array)$options['alternate'];
            foreach ($options['alternate'] as $alternate) {
                $xmlCode .= '<xhtml:link rel="alternate"';
                if (isset($alternate['url'])) {
                    $xmlCode .= ' href="'.$alternate['url'].'"';
                    unset($alternate['url']);
                }
                foreach ($alternate as $key => $val) {
                    $xmlCode .= ' '.$key.'="'.$val.'"';
                }
                $xmlCode .= '/>';
            }
        }

        $xmlCode .= '</url>'.PHP_EOL;
        return $this->write($xmlCode);
    }
}
