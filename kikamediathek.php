<?php

/**
 * @author Daniel Gehn <me@theinad.com>
 * @version 0.2a
 * @copyright 2015 Daniel Gehn
 * @license http://opensource.org/licenses/MIT Licensed under MIT License
 */

require_once 'provider.php';

class SynoFileHostingKIKAMediathek extends TheiNaDProvider {

    protected $LogPath = '/tmp/kika-mediathek.log';

    public function GetDownloadInfo() {
        $this->DebugLog("Getting download url $this->Url");

        $rawXML = $this->curlRequest($this->Url);

        if($rawXML === null)
        {
            return false;
        }

        if(preg_match('#dataURL:\'(.*?)\'#si', $rawXML, $match) === 1)
        {
            $RawXMLData = $this->curlRequest('http://www.kika.de/' . $match[1]);

            if($RawXMLData === null)
            {
                return false;
            }

            $this->DebugLog($RawXMLData);

            preg_match_all('#<asset>(.*?)<\/asset>#si', $RawXMLData, $matches);

            $bestSource = array(
                'bitrate'   => -1,
                'url'       => '',
            );

            foreach($matches[1] as $source)
            {
                if(preg_match("#<progressiveDownloadUrl>(.*?)<\/progressiveDownloadUrl>#si", $source, $downloadUrl) !== 1)
                {
                    continue;
                }

                $url = $downloadUrl[1];

                if(strpos($url, '.mp4') !== false)
                {
                    if(preg_match("#<bitrateVideo>(.*?)<\/bitrateVideo>#si", $source, $bitrateVideo) !== 1)
                    {
                        continue;
                    }

                    $bitrate = $bitrateVideo[1];

                    if($bestSource['bitrate'] < $bitrate)
                    {
                        $bestSource['bitrate'] = $bitrate;
                        $bestSource['url'] = $url;
                    }
                }
            }

            if($bestSource['url'] !== '')
            {
                $url = trim($bestSource['url']);
                $pathinfo = pathinfo($url);
                $filename = '';

                $matches = array();

                if(preg_match("#<channelName>(.*?)<\/channelName>#si", $RawXMLData, $matches) === 1)
                {
                    $filename .= $matches[1];
                }

                $matches = array();

                if(preg_match("#<broadcastName>(.*?)<\/broadcastName>#si", $RawXMLData, $matches) === 1)
                {
                    $filename .= ' - ' . $matches[1];
                }

                if(empty($filename))
                {
                    $filename = $pathinfo['basename'];
                }
                else
                {
                    $filename .= '.' . $pathinfo['extension'];
                }

                $DownloadInfo = array();
                $DownloadInfo[DOWNLOAD_URL] = $url;
                $DownloadInfo[DOWNLOAD_FILENAME] = $this->safeFilename($filename);

                return $DownloadInfo;
            }

            $this->DebugLog("Failed to determine best quality: " . json_encode($matches[1]));

            return FALSE;

        }

        $this->DebugLog("Couldn't identify player meta");

        return FALSE;
    }

}
?>
