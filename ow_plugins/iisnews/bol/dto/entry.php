<?php


/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.bol.dto
 * @since 1.0
 */
class Entry extends OW_Entity
{
    public
    $authorId,
    $title,
    $entry,
    $timestamp,
    $isDraft,
    $privacy = 'everybody';
    /**
     * @var string
     */
    public $image = null;
    /**
     * @return int
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @return string
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function isDraft()
    {
        return $this->isDraft == 1 || $this->isDraft == 2;
    }

    public function getStatus()
    {
        return $this->isDraft;
    }

    /**
     * @param int $authorId
     * 
     * @return $this
     */
    public function setAuthorId( $authorId )
    {
        $this->authorId = $authorId;

        return $this;
    }

    /**
     * @param string $entry
     * 
     * @return $this
     */
    public function setEntry( $entry )
    {
        $this->entry = $entry;

        return $this;
    }

    /**
     * @param int $timestamp
     * 
     * @return $this
     */
    public function setTimestamp( $timestamp )
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    public function setIsDraft( $isDraft )
    {
        $this->isDraft = $isDraft;

        return $this;
    }

    public function setPrivacy( $privacy )
    {
        $this->privacy = $privacy;

        return $this;
    }

    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }


}