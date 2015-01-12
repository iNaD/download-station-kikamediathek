<?php

/**
 * @author Daniel Gehn <me@theinad.com>
 * @version 0.1
 * @copyright 2015 Daniel Gehn
 * @license http://opensource.org/licenses/MIT Licensed under MIT License
 */

class SynoFileHostingKIKAMediathek {
    private $Url;
    private $Username;
    private $Password;
    private $HostInfo;

    private $LogPath = '/tmp/kika-mediathek.log';
    private $LogEnabled = false;

    public function __construct($Url, $Username = '', $Password = '', $HostInfo = '') {
        $this->Url = $Url;
        $this->Username = $Username;
        $this->Password = $Password;
        $this->HostInfo = $HostInfo;

        $this->DebugLog("URL: $Url");
    }

    //This function returns download url.
    public function GetDownloadInfo() {
        $ret = FALSE;

        $this->DebugLog("GetDownloadInfo called");

        $ret = $this->Download();

        return $ret;
    }

    public function onDownloaded()
    {
    }

    public function Verify($ClearCookie = '')
    {
        $this->DebugLog("Verifying User");

        return USER_IS_PREMIUM;
    }

    //This function gets the download url
    private function Download() {
        $this->DebugLog("Getting download url $this->Url");

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->Url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        $rawXML = curl_exec($curl);

        if(!$rawXML)
        {
            $this->DebugLog("Failed to retrieve Website. Error Info: " . curl_error($curl));
            return false;
        }

        curl_close($curl);

        if(preg_match('#dataURL:\'(.*?)\'#si', $rawXML, $match) === 1)
        {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, 'http://www.kika.de/' . $match[1]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            $RawXMLData = curl_exec($curl);

            if(!$RawXMLData)
            {
                $this->DebugLog("Failed to retrieve XML. Error Info: " . curl_error($curl));
                return false;
            }

            curl_close($curl);

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
                $DownloadInfo = array();
                $DownloadInfo[DOWNLOAD_URL] = trim($bestSource['url']);

                return $DownloadInfo;
            }

            $this->DebugLog("Failed to determine best quality: " . json_encode($matches[1]));

            return FALSE;

        }

        $this->DebugLog("Couldn't identify player meta");

        return FALSE;
    }

    private function DebugLog($message)
    {
        if($this->LogEnabled === true)
        {
            file_put_contents($this->LogPath, $message . "\n", FILE_APPEND);
        }
    }
}
?>
