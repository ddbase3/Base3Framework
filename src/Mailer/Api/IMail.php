<?php

namespace Mailer\Api;

interface IMail {

	public function setFrom($email, $name = null);
	public function setReplyTo($email, $name = null);

	public function addTo($email, $name = null);
	public function addCc($email, $name = null);
	public function addBcc($email, $name = null);

	public function setSubject($subject);

	public function setText($text);
	public function setHtml($html);

	public function addAttachmentString($string, $filename);
	public function addAttachmentFile($path, $filename = null);

	public function addEmbeddedImageString($string, $cid, $filename = null);
	public function addEmbeddedImageFile($path, $cid, $filename = null);

	public function getData();

}
