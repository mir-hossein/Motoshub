<?php

require_once("iterators.php");
require_once("RSSItem.php");

class RssParcer
{
    /**
     *
     * @param string $path
     * @param int $limit
     * @return BaseIterator
     * @throws Exception
     */
    public static function getIterator( $path, $limit = null )
    {
        //Issa Annamoradnejad
        //added to handle http responses with content-type: gzip
        $content = @file_get_contents($path);
        $dom = new DOMDocument();
        if (!@$dom->loadxml($content)) {
            $content = @gzdecode($content);
            if (!@$dom->loadxml($content))
            {
                throw new Exception("Unable to read RSS file.");
            }
        }

        $iteratorClass = 'RSSIterator';
        $items = null;

        switch ( true )
        {
            case $dom->getElementsByTagName('feed')->item(0) !== null:
                $iteratorClass = 'AtomIterator';
                $items = $dom->getElementsByTagName("entry");

                break;

            default :
                $iteratorClass = 'RSSIterator';
                $items = $dom->getElementsByTagName("item");
        }

        return new $iteratorClass($items, $limit);
    }
}