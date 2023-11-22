<?php

namespace RSM\Rsmbouncemailprocessor\Utility;

use stdClass;

/**
 * This library is a wrapper around the Imap library functions included in php. This class wraps around an attachment
 * in a message, allowing developers to easily save or display attachments.
 *
 * @package Rsmbouncemailprocessor
 * @author  Robert Hafner <tedivm@tedivm.com>
 * @author  Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
class Attachment
{

    /**
     * This is the structure object for the piece of the message body that the attachment is located it.
     *
     * @var stdClass
     */
    protected stdClass $structure;

    /**
     * This is the unique identifier for the message this attachment belongs to.
     *
     * @var int
     */
    protected int $messageId;

    /**
     * mime type string
     *
     * @var string
     */
    protected string $mimeType;

    /**
     * encoding string
     *
     * @var string
     */
    protected string $encoding;

    /**
     * This is the ImapResource.
     *
     * @var resource
     */
    protected $imapStream;

    /**
     * This is the id pointing to the section of the message body that contains the attachment.
     *
     * @var string
     */
    protected string $partId;

    /**
     * This is the attachment's filename.
     *
     * @var string
     */
    protected string $filename;

    /**
     * This is the size of the attachment.
     *
     * @var int
     */
    protected int $size;

    /**
     * This stores the data of the attachment, so it doesn't have to be retrieved from the server multiple times. It is
     * only populated if the getData() function is called and should not be directly used.
     *
     * @internal
     * @var string
     */
    protected string $data;

    /**
     * This function takes in an ImapMessage, the structure object for the particular piece of the message body that the
     * attachment is located at, and the identifier for that body part. As a general rule you should not be creating
     * instances of this yourself, but rather should get them from an ImapMessage class.
     *
     * @param Mailmessage $message
     * @param stdClass $structure
     * @param null|string $partIdentifier
     */
    public function __construct(Mailmessage $message, stdClass $structure, null|string $partIdentifier = null)
    {
        $this->messageId = $message->getUid();
        $this->imapStream = $message->getImapBox()->getImapStream();
        $this->structure = $structure;

        if (isset($partIdentifier)) {
            $this->partId = $partIdentifier;
        }

        $parameters = Mailmessage::getParametersFromStructure($structure);

        if (isset($parameters['filename'])) {
            $this->setFileName($parameters['filename']);
        } elseif (isset($parameters['name'])) {
            $this->setFileName($parameters['name']);
        }

        if (isset($structure->bytes)) {
            $this->size = $structure->bytes;
        }

        $this->mimeType = Mailmessage::typeIdToString($structure->type);

        if (isset($structure->subtype)) {
            $this->mimeType .= '/' . strtolower($structure->subtype);
        }

        $this->encoding = $structure->encoding;
    }

    /**
     * This function returns the data of the attachment. Combined with getMimeType() it can be used to directly output
     * data to a browser.
     *
     * @return string
     */
    public function getData(): string
    {
        if (!isset($this->data)) {
            $messageBody = isset($this->partId) ?
                imap_fetchbody($this->imapStream, $this->messageId, $this->partId, FT_UID)
                : imap_body($this->imapStream, $this->messageId, FT_UID);
            $messageBody = Mailmessage::decode($messageBody, $this->encoding);
            $this->data = $messageBody;
        }

        return $this->data;
    }

    /**
     * This returns the filename of the attachment, or false if one isn't given.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return (isset($this->filename)) ? $this->filename : false;
    }

    /**
     * This function returns the mimetype of the attachment.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * This returns the size of the attachment.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * This function returns the object that contains the structure of this attachment.
     *
     * @return stdClass
     */
    public function getStructure(): stdClass
    {
        return $this->structure;
    }


    /**
     * This function saves the attachment to the exact specified location.
     *
     * @param string $text
     * @return void
     */
    protected function setFileName(string $text):void
    {
        $this->filename = MIME::decode($text, Mailmessage::$charset);
    }
}
